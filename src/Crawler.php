<?php
namespace Pmaxs\Crawler;

/**
 * Class Crawler
 */
class Crawler extends Objectt implements CrawlerInterface
{
    /**
     * @var int
     */
    protected $tasksIndex = 0;

    /**
     * @var array
     */
    protected $tasks = [];

    /**
     * @var array
     */
    protected $tasksActive = [];

    /**
     * @var array
     */
    protected $sockets2tasks = [];

    /**
     * @var float
     */
    protected $startTime;

    /**
     * Constructor.
     *
     * @param array $options
     * - rps
     * - all Request options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        foreach ($this->tasks as $task) {
            unset($task);
        }
        unset($this->sockets2tasks);
        unset($this->tasksActive);
        unset($this->tasks);
    }

    /**
     * @inheritdoc
     */
    public function request(Request $request, $callback)
    {
        $crawler = $this;

        $request->setOptions(array_merge($this->getOptions(), $request->getOptions()));

        $this->tasksIndex++;

        $this->tasks[$this->tasksIndex] = new Task(
            $this->tasksIndex,
            $request,
            new Response($request->url),
            function (Task $task) use ($crawler, $callback) {
                if ($callback && is_callable($callback)) {
                    call_user_func_array($callback, [$task->getRequest(), $task->getResponse()]);
                }

                $crawler->remove($task);
            }
        );

        return $this->tasks[$this->tasksIndex];
    }

    /**
     * @inheritdoc
     */
    public function remove(Task $task)
    {
        $task->close();

        $taskId = $task->getId();
        $taskSocketId = $task->getSocketId();

        if (isset($this->sockets2tasks[$taskSocketId])) {
            unset($this->sockets2tasks[$taskSocketId]);
        }
        if (isset($this->tasksActive[$taskId])) {
            unset($this->tasksActive[$taskId]);
        }
        if (isset($this->tasks[$taskId])) {
            unset($this->tasks[$taskId]);
        }
        unset($task);
    }

    /**
     * @inheritdoc
     */
    public function process()
    {
        while (true) {
            $this->process_start();
            $this->process_process();
            $this->process_check();

            if (empty($this->tasks)) {
                break;
            }

            usleep(1000);
        }

        return true;
    }

    /**
     * Starts processing.
     * Moves tasks from queue to active queue, starts tasks.
     *
     * @return bool
     */
    protected function process_start()
    {
        $time = self::getTime();
        $concurrency = $this->getOption('concurrency');
        $rps = $this->getOption('rps');

        $c = [];
        if (!empty($concurrency)) {
            $c[] = $concurrency - count($this->tasksActive);
        }
        if (!empty($rps)) {
            $c[] = empty($this->startTime) ? $rps : min($rps, floor($rps * ($time - $this->startTime)));
        }
        $c = !empty($c) ? min($c) : count($this->tasks);
        if ($c <= 0) {
            return;
        }

        $this->startTime = $time;

        $i = 0;
        foreach ($this->tasks as $task) {
            if (!$task->start())
                continue;

            $this->sockets2tasks[$task->getSocketId()] = $task;
            $this->tasksActive[$task->getId()] = $task;

            if (++$i >= $c) break;
        }

        return true;
    }

    /**
     * Processes tasks.
     * Sends requests and receives responses.
     */
    protected function process_process()
    {
        if (empty($this->tasksActive)) {
            return;
        }

        $read = [];
        $write = [];
        $except = [];

        foreach ($this->tasksActive as $task) {
            $except[] = $task->getSocket();
            if (!$task->isWriteDone()) {
                $write[] = $task->getSocket();
            } elseif (!$task->isReadDone()) {
                $read[] = $task->getSocket();
            }
        }

        $n = socket_select($read, $write, $except, 0);
        if (!$n) {
            return;
        }

        foreach ($except as $socket) {
            $task = $this->socket2task($socket);
            if (!empty($task)) {
                $task->error('except');
            }
        }
        foreach ($write as $socket) {
            $task = $this->socket2task($socket);
            if (!empty($task)) {
                $task->write();
            }
        }
        foreach ($read as $socket) {
            $task = $this->socket2task($socket);
            if (!empty($task)) {
                $task->read();
            }
        }
    }

    /**
     * Checkc tasks if they are valid and  not retired.
     */
    protected function process_check()
    {
        $time = self::getTime();
        foreach ($this->tasksActive as $task) {
            $task->check($time);
        }
    }

    /**
     * Returns task by socket.
     *
     * @param resource $socket
     * @return Task
     */
    protected function socket2task($socket)
    {
        $socket_id = (string)$socket;

        if (empty($socket_id) || empty($this->sockets2tasks[$socket_id]) || !$this->sockets2tasks[$socket_id] instanceof Task) {
            return null;
        } else {
            return $this->sockets2tasks[$socket_id];
        }
    }
}

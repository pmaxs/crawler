<?php

namespace Pmaxs\Crawler;

class Crawler extends Objectt implements CrawlerInterface
{
    protected ?int $tasksIndex = 0;
    /** @var array|Task[] $tasks */
    protected ?array $tasks = [];
    /** @var array|Task[] $tasksActive */
    protected ?array $tasksActive = [];
    /** @var array|Task[] $sockets2tasks */
    protected ?array $sockets2tasks = [];
    protected ?float $startTime = null;

    /**
     * @param array $options
     * - rps
     * - all Request options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    public function __destruct()
    {
        foreach ($this->tasks as $task) {
            unset($task);
        }
        unset($this->sockets2tasks);
        unset($this->tasksActive);
        unset($this->tasks);
    }

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
                $crawler->remove($task);

                if (is_callable($callback)) {
                    call_user_func_array($callback, [$task->getRequest(), $task->getResponse()]);
                }

                unset($task);
            }
        );

        return $this->tasks[$this->tasksIndex];
    }

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
    }

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
            if (!$task->start()) {
                continue;
            }

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
     * Checks tasks if they are valid and  not retired.
     */
    protected function process_check()
    {
        $time = self::getTime();
        foreach ($this->tasksActive as $task) {
            $task->check($time);
        }
    }
    
    protected function socket2task($socket): ?Task
    {
        if (!$socket instanceof \Socket) {
            return null;
        }

        return $this->sockets2tasks[spl_object_id($socket)] ?? null;
    }
}

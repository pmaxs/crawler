<?php
namespace Pmaxs\Crawler;

if (\substr(\PHP_OS, 0, 3) == 'WIN') {
    \define('INPROGRESS', 10035);
} else {
    \define('INPROGRESS', 115);
}

/**
 * Class Task
 */
class Task extends Objectt
{
    /**
     * @var int
     */
    const STATUS_NEW = 0;

    /**
     * @var int
     */
    const STATUS_ACTIVE = 1;

    /**
     * @var int
     */
    const STATUS_CLOSED = 2;

    /**
     * @var int
     */
    const TIME_LIMIT = 30;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $status = self::STATUS_NEW;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var string
     */
    protected $socketId;

    /**
     * @var float
     */
    protected $timeFinishLimit;

    /**
     * @var boolean
     */
    protected $connectDone = false;

    /**
     * @var boolean
     */
    protected $writeDone = false;

    /**
     * @var boolean
     */
    protected $readDone = false;

    /**
     * Constructor
     *
     * @param int $id
     * @param Request $request
     * @param Response $response
     * @param callable $callback
     * @param array $options
     */
    public function __construct($id, Request $request, Response $response, $callback, array $options = array())
    {
        parent::__construct($options);

        $this->id = $id;
        $this->request = $request;
        $this->response = $response;
        $this->callback = $callback;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if ($this->status == self::STATUS_NEW) $this->error('Desctruct new');
        else $this->close();

        unset($this->request, $this->response, $this->socket, $this->callback);
    }

    /**
     * Returns task id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns response.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Returns socket.
     *
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Returns socket id.
     *
     * @return string
     */
    public function getSocketId()
    {
        return $this->socketId;
    }

    /**
     * Returns whether connection was done.
     *
     * @return bool
     */
    public function isConnectDone()
    {
        return $this->connectDone;
    }

    /**
     * Returns whether write operation was done.
     *
     * @return bool
     */
    public function isWriteDone()
    {
        return $this->writeDone;
    }

    /**
     * Returns whether read operation was done.
     *
     * @return bool
     */
    public function isReadDone()
    {
        return $this->readDone;
    }

    /**
     * Starts task.
     * Creates socket, connects to remote host.
     *
     * @return bool
     */
    public function start()
    {
        if ($this->status != self::STATUS_NEW) return false;
        $this->status = self::STATUS_ACTIVE;

        $request = $this->request;
        $response = $this->response;

        $response->timeStart = self::getTime();

        if (empty($timeLimit)) $timeLimit = $request->getOption('time_limit');
        if (empty($timeLimit)) $timeLimit = self::TIME_LIMIT;

        $this->timeFinishLimit = $response->timeStart + $timeLimit;

        $remoteIp = '';
        $remotePort = '';

        if (($proxy = $request->getOption('proxy'))) {
            $proxy_parts = \explode(':', $proxy);
            $remoteIp = $proxy_parts[0];
            if (!empty($proxy_parts[1])) $remotePort = $proxy_parts[1];
        } else {
            $url_parts = \parse_url($request->url);
            if (!empty($url_parts['host'])) $remoteIp = $url_parts['host'];
            if (!empty($url_parts['port'])) $remotePort = $url_parts['port'];
        }

        if (empty($remoteIp)) {
            $this->error('Empty remoteIp');
            return false;
        }

        if (!\preg_match('~[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}~', $remoteIp)) {
            $remoteIp = \gethostbyname($remoteIp);
            if (empty($remoteIp)) {
                $this->error('Empty remoteIp');
                return false;
            }
        }

        $remotePort = (int)$remotePort;
        if (empty($remotePort)) $remotePort = 80;

        $response->remoteIp = $remoteIp;
        $response->remotePort = $remotePort;

        $this->socket = \socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->socketId = (string)$this->socket;
        if (!$this->socket) {
            $this->error('socket_create');
            return false;
        }

        $r = \socket_set_nonblock($this->socket);
        if (!$r) {
            $this->error('socket_set_nonblock');
            return false;
        }

        //\socket_set_option($this->socket, \SOL_SOCKET, \SO_KEEPALIVE, 0);
        //\socket_set_option($this->socket, \SOL_SOCKET, \SO_RCVTIMEO, array('sec'=>1, 'usec'=>0));
        //\socket_set_option($this->socket, \SOL_SOCKET, \SO_SNDTIMEO, array('sec'=>1, 'usec'=>0));

        $r = \socket_connect($this->socket, $remoteIp, $remotePort);
        if (!($r || \socket_last_error($this->socket) == INPROGRESS)) {
            $this->error('socket_connect');
            return false;
        }

        $this->connectDone = true;

        return true;
    }

    /**
     * Writes request to remote host.
     *
     * @return bool
     */
    public function write()
    {
        if ($this->status != self::STATUS_ACTIVE) return false;
        if ($this->writeDone) return false;

        $request = $this->request;
        $response = $this->response;

        $response->timeWrite = self::getTime();

        $url_parts = \parse_url($request->url);

        $http_protocol = $request->getOption('proxy') ? '1.0' : '1.1';

        $input = $request->method . ' ';
        $input .= ''
            //.$url_parts['host']
            . (isset($url_parts['path']) ? $url_parts['path'] : '/')
            . (isset($url_parts['query']) ? '?' . $url_parts['query'] : '')
            . ' HTTP/' . $http_protocol . "\r\n";

        $input .= 'Host: ' . $url_parts['host'] . "\r\n";
        if ($http_protocol == '1.1') $input .= 'Connection: close' . "\r\n";
        if (($tmp = $request->getOption('user_agent'))) $input .= 'User-Agent: ' . $tmp . "\r\n";
        if (($tmp = $request->getOption('referer'))) $input .= 'Referer: ' . $tmp . "\r\n";
        if (($tmp = $request->getOption('cookie'))) $input .= 'Cookie: ' . $tmp . "\r\n";

        if (!empty($request->headers)) {
            if (\is_scalar($request->headers)) $input .= $request->headers;
            else foreach ($request->headers as $k => $v) $input .= $k . ': ' . $v . "\r\n";
        }

        if ($request->method == 'POST') {
            if (!empty($request->post)) {
                if (\is_scalar($request->post)) $post = $request->post;
                else $post = \http_build_query($request->post);
            } else {
                $post = '';
            }

            $input .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
            $input .= 'Content-Length: ' . \strlen($post) . "\r\n";
            $input .= "\r\n";
            $input .= $post;
        } else {
            $input .= "\r\n";
        }

        $r = \socket_write($this->socket, $input);
        if (!$r) {
            $this->error('socket_write');
            return false;
        }

        $this->writeDone = true;

        return true;
    }

    /**
     * Reads response from remote host.
     */
    public function read()
    {
        if ($this->status != self::STATUS_ACTIVE) return false;
        if (!$this->writeDone) return false;
        if ($this->readDone) return false;

        $response = $this->response;
        $response->timeRead = self::getTime();

        do {
            $tmp = \socket_read($this->socket, 4096);
        } while ($this->_read($tmp));
    }

    /**
     * Reads and processes response from remote host (part of read()).
     *
     * @param $output
     * @return bool
     */
    protected function _read($output)
    {
        $close = false;

        if ($output === false) {
            if (($errorno = \socket_last_error($this->socket)) && $errorno != INPROGRESS) {
                $this->error('reading from socket');
                return false;
            }
        }

        $request = $this->request;
        $response = $this->response;

        $response->output .= $output;

        if ($output === "") {
            $close = true;
        } elseif (\substr($response->output, -7) === "\x0D\x0A\x30\x0D\x0A\x0D\x0A") {
            $response->output = substr($response->output, 0, -7);
            $close = true;
        }

        if (!isset($response->header)) {
            $pos1 = \strpos($response->output, "\n\n");
            $pos2 = \strpos($response->output, "\r\n\r\n");

            if ($close || $pos1 !== false || $pos2 !== false) {
                if ($pos1 !== false && $pos2 !== false) {
                    if ($pos1 < $pos2) {
                        $pos = $pos1;
                        $posc = 2;
                    } else {
                        $pos = $pos2;
                        $posc = 4;
                    }
                } elseif ($pos1 !== false) {
                    $pos = $pos1;
                    $posc = 2;
                } elseif ($pos2 !== false) {
                    $pos = $pos2;
                    $posc = 4;
                } else {
                    $pos = \strlen($response->output);
                    $posc = 0;
                }

                $response->header = \substr($response->output, 0, $pos);
                $header_parts = \preg_split('~[\\n\\r]+(?!\\s)~s', $response->header, -1, \PREG_SPLIT_NO_EMPTY);

                // code
                if (empty($header_parts[0]) || !\preg_match('~http/(\\d+\\.\\d+) (\\d+)~i', $header_parts[0], $matches)) {
                    $this->error('Incorrect response status');
                    return false;
                }

                $response->code = $matches[2];

                unset($header_parts[0]);

                foreach ($header_parts as $header_part) {
                    $header_part = \explode(':', $header_part, 2);
                    $header_part[0] = \trim($header_part[0]);
                    $header_part[1] = (isset($header_part[1]) ? \trim($header_part[1]) : null);

                    $response->headers[$header_part[0]] = $header_part[1];

                    if (\strtolower($header_part[0]) == 'content-length') $response->headerContentLength = (int)$header_part[1];
                }

                if ($request->getOption('header_only')) {
                    $close = true;
                } else {
                    if (\strlen($response->output) <= $pos + $posc) $response->body = "";
                    else $response->body = \substr($response->output, $pos + $posc);
                }
            }
        } else {
            $response->body .= $output;
        }

        if (
            !$close
            && isset($response->headerContentLength)
            && $response->headerContentLength <= strlen($response->body)
        ) {
            $close = true;
        }

        if ($close) {
            $this->readDone = true;
            $this->close();
            return false;
        }

        return true;
    }

    /**
     * Closes task.
     *
     * @return bool
     */
    public function close()
    {
        if ($this->status == self::STATUS_CLOSED) return true;
        $this->status = self::STATUS_CLOSED;

        $request = $this->request;
        $response = $this->response;

        $response->timeClose = self::getTime();

        if (isset($this->socket) && \is_resource($this->socket)) {
            if ($this->connectDone) \socket_shutdown($this->socket, 2);
            \socket_close($this->socket);
        }

        $response->timeFinish = self::getTime();

        if (empty($response->result)) $response->result = 'ok';

        if ($this->callback && \is_callable($this->callback)) {
            \call_user_func_array($this->callback, [$this]);
        }

        return true;
    }

    /**
     * Closes task with error.
     *
     * @param string $error
     * @return bool
     */
    public function error($error)
    {
        if ($this->status == self::STATUS_CLOSED) return true;

        if (isset($this->socket) && \is_resource($this->socket))
            if (($errorno = \socket_last_error($this->socket)) && $errorno != INPROGRESS)
                $error .= ': ' . \socket_strerror($errorno) . ' (' . $errorno . ')';

        $response = $this->response;
        $response->result = 'error';
        $response->error = $error;

        $this->close();

        return true;
    }

    /**
     * Checks if task is valid.
     * If task is not valid, closes it with error.
     *
     * @param int $time current time
     * @return bool
     */
    public function check($time = null)
    {
        if ($this->status == self::STATUS_CLOSED) return false;

        if (!isset($this->socket) || !\is_resource($this->socket)) {
            $this->error('check socket');
            return false;
        }

        if (!isset($time)) $time = self::getTime();
        if ($time > $this->timeFinishLimit) {
            $this->error('check time_limit');
            return false;
        }

        return true;
    }
}

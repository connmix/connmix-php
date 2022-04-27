<?php

namespace Connmix\V1;

class Engine
{

    /**
     * @var \Closure
     */
    protected $onConnect;

    /**
     * @var \Closure
     */
    protected $onReceive;

    /**
     * @var \Closure
     */
    protected $onError;

    /**
     * @var string
     */
    public $host = '';

    /**
     * @var float
     */
    protected $timeout = 0.0;

    /**
     * @var \Ratchet\Client\WebSocket
     */
    public $conn;

    /**
     * @param callable $onConnect
     * @param callable $onReceive
     * @param callable $onError
     * @param string $host
     * @param float $timeout
     */
    public function __construct(callable $onConnect, callable $onReceive, callable $onError, string $host, float $timeout)
    {
        $this->onConnect = $onConnect;
        $this->onReceive = $onReceive;
        $this->onError = $onError;
        $this->host = $host;
        $this->timeout = $timeout;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $loop = \React\EventLoop\Loop::get();
        $reactConnector = new \React\Socket\Connector([
            'timeout' => $this->timeout,
        ]);
        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);
        $url = sprintf('ws://%s/ws/v1', $this->host);
        $connector($url, [], [])
            ->then(function (\Ratchet\Client\WebSocket $conn) use ($url) {
                $this->conn = $conn;

                $onConnect = $this->onConnect;
                $onReceive = $this->onReceive;
                $onError = $this->onError;

                $conn->on('message', function (\Ratchet\RFC6455\Messaging\MessageInterface $msg) use ($conn, $onReceive, $onError) {
                    try {
                        $receiveMessage = new Message($msg->getPayload());
                        $onReceive(new AsyncSyncNode($conn, $receiveMessage, new Encoder()));
                    } catch (\Throwable $e) {
                        $onError($e);
                    }
                });

                $conn->on('close', function ($code = null, $reason = null) use ($onError, $url) {
                    $onError(new \Exception(sprintf('Client connection closed (code=%d, reason=%s, url=%s)', $code, $reason, $url)));
                    \React\EventLoop\Loop::addTimer(1, [$this, 'run']);
                });

                try {
                    $onConnect(new AsyncSyncNode($conn, new Message('{}'), new Encoder()));
                } catch (\Throwable $e) {
                    $onError($e);
                }
            }, function (\Throwable $e) use ($loop) {
                $onRejected = $this->onError;
                $onRejected($e);
                \React\EventLoop\Loop::addTimer(1, [$this, 'run']);
            });
    }

    public function close()
    {
        $this->conn and $this->conn->close();
    }

}

<?php

namespace Connmix;

use Connmix\V1\SyncSyncNode as NodeV1;

class Client
{

    /**
     * @var string
     */
    protected $host = '';

    /**
     * @var float
     */
    protected $timeout = 10.0;

    /**
     * @var Nodes
     */
    protected $nodes;

    /**
     * @var Consumer[]
     */
    protected $consumers = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }

        $this->nodes = new Nodes($this->host, $this->timeout);
    }

    /**
     * @param callable $onConnect
     * @param callable $onReceive
     * @param callable $onError
     * @return void
     * @throws \Exception
     */
    public function do(callable $onConnect, callable $onReceive, callable $onError): void
    {
        $this->nodes->startSync();
        $consumer = new Consumer($this->nodes, $this->timeout);
        $this->consumers[] = $consumer;
        $consumer->then($onConnect, $onReceive, $onError);
    }

    /**
     * @return SyncNodeInterface
     * @throws \Exception
     */
    public function random(): SyncNodeInterface
    {
        $nodes = $this->nodes->items();
        $node = $nodes[array_rand($nodes)];
        switch ($this->nodes->version()) {
            case 'v1':
                $url = sprintf("ws://%s/ws/v1", $node['host']);
                return new NodeV1($url);
            default:
                throw new \Exception('Invalid API version');
        }
    }

    /**
     * @param string $host
     * @param string $version
     * @return SyncNodeInterface
     * @throws \Exception
     */
    public function node(string $host, string $version): SyncNodeInterface
    {
        switch ($version) {
            case 'v1':
                $url = sprintf("ws://%s/ws/v1", $host);
                return new NodeV1($url);
            default:
                throw new \Exception('Invalid API version');
        }
    }

    /**
     * @return void
     */
    public function close(): void
    {
        foreach ($this->consumers as $consumer) {
            $consumer->close();
        }

        $this->nodes->close();

        $loop = \React\EventLoop\Loop::get();
        $loop->stop();
    }

}

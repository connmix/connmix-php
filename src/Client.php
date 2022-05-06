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
     * @var callable
     */
    protected $onConnect;

    /**
     * @var callable
     */
    protected $onMessage;

    /**
     * @var callable
     */
    protected $onError;

    /**
     * @var Nodes
     */
    protected $nodes;

    /**
     * @var Connector
     */
    protected $connector;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @param callable $onConnect
     * @param callable $onReceive
     * @param callable $onError
     * @return void
     * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
     * @deprecated 废弃
     */
    public function do(callable $onConnect, callable $onReceive, callable $onError): void
    {
        $this->nodes = new Nodes($this->host, $this->timeout);
        $this->nodes->startSync();
        $this->connector = new Connector($this->nodes, $this->timeout);
        $this->connector->then($onConnect, $onReceive, $onError);
    }

    /**
     * @param callable $connect
     * @param callable $message
     * @param callable $error
     * @return void
     */
    public function on(callable $connect, callable $message, callable $error): void
    {
        $this->onConnect = $connect;
        $this->onMessage = $message;
        $this->onError = $error;
    }

    /**
     * @return void
     * @throws \Exception|\GuzzleHttp\Exception\GuzzleException
     */
    public function run(): void
    {
        $this->nodes = new Nodes($this->host, $this->timeout);
        $this->nodes->startSync();
        $this->connector = new Connector($this->nodes, $this->timeout);
        $this->connector->then($this->onConnect, $this->onMessage, $this->onError);
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
        $this->connector and $this->connector->close();

        $this->nodes and $this->nodes->close();

        $loop = \React\EventLoop\Loop::get();
        $loop->stop();
    }

}

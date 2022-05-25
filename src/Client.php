<?php

namespace Connmix;

use Connmix\V1\Message;
use Connmix\V1\SyncNode;
use Connmix\V1\SyncNode as NodeV1;

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
     * @var SyncNode[]
     */
    protected $cache;

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
     * @param string $message
     * @return MessageInterface
     */
    public function parse(string $params): MessageInterface
    {
        return new Message(sprintf('{"p":%s}', $params));
    }

    /**
     * @param string|null $id
     * @return SyncNodeInterface
     */
    public function node(?string $id): SyncNodeInterface
    {
        $cache = &$this->cache;
        if (isset($cache[$id])) {
            return $cache[$id];
        }

        $nodes = $this->nodes->items();
        $version = $this->nodes->version();
        if (is_null($id)) {
            $node = $nodes[array_rand($nodes)];
            $newNode = $this->newNode($node['api_server'], $version);
            $cache[$node['id']] = $newNode;
            return $newNode;
        }

        foreach ($nodes as $node) {
            if ($node['id'] == $id) {
                $newNode = $this->newNode($node['api_server'], $version);
                $cache[$id] = $newNode;
                return $newNode;
            }
        }

        // 找不到就返回一个随机节点
        return $this->node(null);
    }

    /**
     * @param string $host
     * @param string $version
     * @return SyncNodeInterface
     * @throws \Exception
     */
    protected function newNode(string $host, string $version): SyncNodeInterface
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

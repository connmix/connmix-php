<?php

namespace Connmix;

use Connmix\V1\Message;
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
     * @var NodeSynchronizer
     */
    protected $nodeSynchronizer;

    /**
     * @var SyncNodeManager
     */
    protected $syncNodeManager;

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
        $this->syncNodeManager = new SyncNodeManager();
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
        $this->nodeSynchronizer = new NodeSynchronizer($this->host, $this->timeout);
        $this->nodeSynchronizer->startSync();
        $this->connector = new Connector($this->nodeSynchronizer, $this->timeout);
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
        if (!isset($this->nodeSynchronizer)) {
            $this->nodeSynchronizer = new NodeSynchronizer($this->host, $this->timeout);
            $this->nodeSynchronizer->startSync();
        }

        $manager = $this->syncNodeManager;
        if (!is_null($id) && $manager->has($id)) {
            return $manager->get($id);
        }

        $nodes = $this->nodeSynchronizer->items();
        $version = $this->nodeSynchronizer->version();
        if (is_null($id)) {

            $randNode = $nodes[array_rand($nodes)];
            $randNodeId = $randNode['id'];
            if ($manager->has($randNodeId)) {
                return $manager->get($randNodeId);
            }

            $newNode = $this->newNode($randNode['api_server'], $version, $randNodeId, $manager);
            $manager->set($newNode, $randNodeId);
            return $newNode;
        }

        foreach ($nodes as $node) {
            if ($node['id'] == $id) {
                $newNode = $this->newNode($node['api_server'], $version, $id, $manager);
                $manager->set($newNode, $id);
                return $newNode;
            }
        }

        // 找不到就返回一个随机节点
        return $this->node(null);
    }

    /**
     * @param string $host
     * @param string $version
     * @param string $id
     * @param SyncNodeManager $manager
     * @return SyncNodeInterface
     * @throws \Exception
     */
    protected function newNode(string $host, string $version, string $id, SyncNodeManager $manager): SyncNodeInterface
    {
        switch ($version) {
            case 'v1':
                $url = sprintf("ws://%s/ws/v1", $host);
                return new NodeV1($url, $id, $manager);
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

        $this->nodeSynchronizer and $this->nodeSynchronizer->close();

        $loop = \React\EventLoop\Loop::get();
        $loop->stop();
    }

}

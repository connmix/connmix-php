<?php

namespace Connmix;

use Connmix\V1\AsyncNodeEngine as EngineV1;

class Connector
{

    /**
     * @var NodeSynchronizer
     */
    protected $nodeSynchronizer;

    /**
     * @var float
     */
    protected $timeout = 0.0;

    /**
     * @var EngineV1[]
     */
    public $engines = [];


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
     * @var int
     */
    protected $syncInterval = 60;

    /**
     * @param NodeSynchronizer $nodes
     * @param float $timeout
     * @throws \Exception
     */
    public function __construct(NodeSynchronizer $nodes, float $timeout)
    {
        $this->nodeSynchronizer = $nodes;
        $this->timeout = $timeout;
        \React\EventLoop\Loop::addTimer($this->syncInterval, $this->syncFunc());
    }

    /**
     * @param callable $onConnect
     * @param callable $onMessage
     * @param callable $onError
     * @return void
     * @throws \Exception
     */
    public function then(callable $onConnect, callable $onMessage, callable $onError): void
    {
        $this->onConnect = $onConnect;
        $this->onMessage = $onMessage;
        $this->onError = $onError;

        foreach ($this->nodeSynchronizer->items() as $node) {
            $this->addEngine($node['api_server']);
        }
    }

    /**
     * @param string $host
     * @return void
     * @throws \Exception
     */
    protected function addEngine(string $host)
    {
        switch ($this->nodeSynchronizer->version()) {
            case 'v1':
                $engine = new EngineV1($this->onConnect, $this->onMessage, $this->onError, $host, $this->timeout);
                $engine->run();
                $this->engines[] = $engine;
                break;
            default:
                throw new \Exception('Invalid API version');
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function syncFunc(): \Closure
    {
        return function () {
            // 增加
            foreach ($this->nodeSynchronizer->items() as $node) {
                $host = $node['api_server'];
                $find = false;
                foreach ($this->engines as $engine) {
                    if ($engine->host == $host) {
                        $find = true;
                        break;
                    }
                }
                if (!$find) {
                    $this->addEngine($host);
                }
            }

            // 减少
            foreach ($this->engines as $key => $engine) {
                $find = false;
                foreach ($this->nodeSynchronizer->items() as $node) {
                    if ($engine->host == $node['api_server']) {
                        $find = true;
                        break;
                    }
                }
                if (!$find) {
                    $this->engines[$key]->close();
                    unset($this->engines[$key]);
                    $this->engines = array_values($this->engines);
                }
            }

            \React\EventLoop\Loop::addTimer($this->syncInterval, $this->syncFunc());
        };
    }

    /**
     * @return void
     */
    public function close(): void
    {
        foreach ($this->engines as $engine) {
            $engine->close();
        }
    }

}

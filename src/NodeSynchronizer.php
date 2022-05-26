<?php

namespace Connmix;

use Psr\Http\Message\ResponseInterface;
use React\EventLoop\TimerInterface;

class NodeSynchronizer
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
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * @var string
     */
    protected $version = '';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var int
     */
    protected $loadInterval = 60;

    /**
     * @var TimerInterface
     */
    protected $timer;

    /**
     * @param string $host
     * @param float $timeout
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __construct(string $host, float $timeout)
    {
        $this->host = $host;
        $this->timeout = $timeout;

        $this->guzzle = new \GuzzleHttp\Client([
            'timeout' => $timeout,
        ]);

        $this->loadVersion();
        $this->loadNodes();
    }

    /**
     * @return void
     */
    public function startSync(): void
    {
        if ($this->timer) {
            return;
        }
        $func = null;
        $func = function () use (&$func) {
            try {
                $this->loadNodes();
            } catch (\Throwable $ex) {
                echo sprintf("ERROR: Failed to load nodes: %s\n", $ex->getMessage());
            }
            $this->timer = \React\EventLoop\Loop::addTimer($this->loadInterval + mt_rand(1, 10), $func);
        };
        $this->timer = \React\EventLoop\Loop::addTimer($this->loadInterval + mt_rand(1, 10), $func);
    }

    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function loadVersion(): void
    {
        $url = sprintf("%s/version", $this->host);
        $response = $this->guzzle->request('GET', $url);
        $body = static::parseBody($response);
        $api = $body['apis'];
        $this->version = array_shift($api);
    }

    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function loadNodes(): void
    {
        $url = sprintf("%s/%s/nodes", $this->host, $this->version);
        $response = $this->guzzle->request('GET', $url);
        $body = static::parseBody($response);
        $this->items = $body['nodes'];
    }

    /**
     * @param ResponseInterface $response
     * @return array
     */
    protected static function parseBody(ResponseInterface $response): array
    {
        $body = $response->getBody()->__toString();
        return json_decode($body, true);
    }

    /**
     * @return array
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return string
     */
    public function version(): string
    {
        return $this->version;
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->timer and \React\EventLoop\Loop::cancelTimer($this->timer);
    }

}

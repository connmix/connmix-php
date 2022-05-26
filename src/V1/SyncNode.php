<?php

namespace Connmix\V1;

use Connmix\AutoIncrement;
use Connmix\MessageInterface;
use Connmix\SyncNodeInterface;
use Connmix\SyncNodeManager;

class SyncNode implements SyncNodeInterface
{

    /**
     * @var \WebSocket\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var SyncNodeManager
     */
    protected $manager;

    /**
     * @var Encoder
     */
    protected $encoder;

    /**
     * @param string $url
     * @param string $id
     * @param SyncNodeManager $manager
     */
    public function __construct(string $url, string $id, SyncNodeManager $manager)
    {
        $this->client = new \WebSocket\Client($url);
        $this->id = $id;
        $this->manager = $manager;
        $this->encoder = new Encoder();
    }

    /**
     * @param int $clientId
     * @param string $func
     * @param array $args
     * @return MessageInterface
     */
    public function connCall(int $clientId, string $func, array $args): MessageInterface
    {
        return $this->send('conn.call', [
            'c' => $clientId,
            'f' => $func,
            'a' => $args,
        ]);
    }

    /**
     * @param int $clientId
     * @param string $key
     * @param $value
     * @return MessageInterface
     */
    public function setContextValue(int $clientId, string $key, $value): MessageInterface
    {
        return $this->connCall($clientId, 'set_context_value', [$key, $value]);
    }

    /**
     * @param int $clientId
     * @param array $data
     * @return MessageInterface
     */
    public function setContext(int $clientId, array $data): MessageInterface
    {
        return $this->connCall($clientId, 'set_context', [$data]);
    }

    /**
     * @param int $clientId
     * @param string ...$channels
     * @return MessageInterface
     */
    public function subscribe(int $clientId, string ...$channels): MessageInterface
    {
        return $this->connCall($clientId, 'subscribe', $channels);
    }

    /**
     * @param int $clientId
     * @param string ...$channels
     * @return MessageInterface
     */
    public function unsubscribe(int $clientId, string ...$channels): MessageInterface
    {
        return $this->connCall($clientId, 'unsubscribe', $channels);
    }

    /**
     * @param string $method
     * @param array $params
     * @return MessageInterface
     */
    public function send(string $method, array $params = []): MessageInterface
    {
        try {
            $message = $this->encoder->encode([
                'm' => $method,
                'p' => $params,
                'i' => AutoIncrement::id(),
            ]);
            $this->client->send($message);
        } catch (\Throwable $ex) {
            $this->manager->delete($this->id);
            throw $ex;
        }
        return new Message($this->client->receive());
    }

    /**
     * @param int $clientId
     * @param string $data
     * @return MessageInterface
     */
    public function meshSend(int $clientId, string $data): MessageInterface
    {
        return $this->send('mesh.send', [
            'c' => $clientId,
            'd' => $data,
        ]);
    }

    /**
     * @param string $channel
     * @param string $data
     * @return MessageInterface
     */
    public function meshPublish(string $channel, string $data): MessageInterface
    {
        return $this->send('mesh.send', [
            'c' => $channel,
            'd' => $data,
        ]);
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->client->close(1000, '');
    }

}

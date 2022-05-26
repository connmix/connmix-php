<?php

namespace Connmix\V1;

use Connmix\AutoIncrement;
use Connmix\MessageInterface;
use Connmix\AsyncNodeInterface;
use Ratchet\Client\WebSocket;

class AsyncNode implements AsyncNodeInterface
{

    /**
     * @var WebSocket
     */
    protected $conn;

    /**
     * @var MessageInterface
     */
    protected $message;

    /**
     * @var Encoder
     */
    protected $encoder;

    /**
     * @param WebSocket $conn
     * @param MessageInterface $message
     */
    public function __construct(WebSocket $conn, MessageInterface $message, Encoder $encoder)
    {
        $this->conn = $conn;
        $this->message = $message;
        $this->encoder = $encoder;
    }

    /**
     * @return MessageInterface
     */
    public function message(): MessageInterface
    {
        return $this->message;
    }

    /**
     * @param string $method
     * @param array $params
     * @return int
     */
    public function send(string $method, array $params = []): int
    {
        $id = AutoIncrement::id();
        $message = $this->encoder->encode([
            'm' => $method,
            'p' => $params,
            'i' => $id,
        ]);
        $this->conn->send($message);
        return $id;
    }

    /**
     * @param string ...$topics
     * @return int
     */
    public function pop(string ...$topics): int
    {
        return $this->send('queue.pop', $topics);
    }

    /**
     * @param int $clientId
     * @param string $func
     * @param array $args
     * @return int
     */
    public function connCall(int $clientId, string $func, array $args): int
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
     * @return int
     */
    public function setContextValue(int $clientId, string $key, $value): int
    {
        return $this->connCall($clientId, 'set_context_value', [$key, $value]);
    }

    /**
     * @param int $clientId
     * @param array $data
     * @return int
     */
    public function setContext(int $clientId, array $data): int
    {
        return $this->connCall($clientId, 'set_context', [$data]);
    }

    /**
     * @param int $clientId
     * @param string ...$channels
     * @return int
     */
    public function subscribe(int $clientId, string ...$channels): int
    {
        return $this->connCall($clientId, 'subscribe', $channels);
    }

    /**
     * @param int $clientId
     * @param string ...$channels
     * @return int
     */
    public function unsubscribe(int $clientId, string ...$channels): int
    {
        return $this->connCall($clientId, 'unsubscribe', $channels);
    }

    /**
     * @param int $clientId
     * @param string $data
     * @return int
     */
    public function meshSend(int $clientId, string $data): int
    {
        return $this->send('mesh.send', [
            'c' => $clientId,
            'd' => $data,
        ]);
    }

    /**
     * @param string $channel
     * @param string $data
     * @return int
     */
    public function meshPublish(string $channel, string $data): int
    {
        return $this->send('mesh.publish', [
            'c' => $channel,
            'd' => $data,
        ]);
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->conn->close(1000, '');
    }

}

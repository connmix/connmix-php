<?php

namespace Connmix\V1;

use Connmix\MessageInterface;

class Message implements MessageInterface
{

    /**
     * @var string
     */
    protected $raw;

    /**
     * @var array
     */
    protected $storage;

    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->raw = $message;
        $this->storage = json_decode($message, true) ?: [];
    }

    /**
     * @return string
     */
    public function payload(): string
    {
        return $this->raw;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        if ($this->event() !== '') {
            return 'event';
        }

        if (!is_null($this->error())) {
            return 'error';
        }

        if (!is_null($this->result())) {
            return 'result';
        }

        return 'unknown';
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return $this->storage['m'] ?? '';
    }

    /**
     * @return string
     */
    public function event(): string
    {
        return $this->storage['e'] ?? '';
    }

    /**
     * @return array|null
     */
    public function error(): ?array
    {
        return $this->storage['E'] ?? null;
    }

    /**
     * @return array|null
     */
    public function params(): ?array
    {
        return $this->storage['p'] ?? null;
    }

    /**
     * @return array|null
     */
    public function result(): ?array
    {
        return $this->storage['r'] ?? null;
    }

    /**
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->storage['i'] ?? null;
    }

    /**
     * @return string
     */
    public function nodeID(): string
    {
        $params = $this->params();
        if (!$params) {
            return 0;
        }
        return $params['n'] ?? 0;
    }

    /**
     * @return string
     */
    public function clientID(): string
    {
        $params = $this->params();
        if (!$params) {
            return 0;
        }
        return $params['c'] ?? 0;
    }

    /**
     * @return string
     */
    public function topic(): string
    {
        $params = $this->params();
        if (!$params) {
            return '';
        }
        return $params['t'] ?? '';
    }

    /**
     * @return array|null
     */
    public function data(): ?array
    {
        $params = $this->params();
        if (!$params) {
            return [];
        }
        return $params['d'] ?? [];
    }

    /**
     * @return bool
     */
    public function success(): bool
    {
        $result = $this->result();
        if (!$result) {
            return false;
        }
        return $result['s'] ?? false;
    }

    /**
     * @return int
     */
    public function fail(): int
    {
        $result = $this->result();
        if (!$result) {
            return false;
        }
        return $result['f'] ?? 0;
    }

    /**
     * @return int
     */
    public function total(): int
    {
        $result = $this->result();
        if (!$result) {
            return false;
        }
        return $result['t'] ?? 0;
    }

}

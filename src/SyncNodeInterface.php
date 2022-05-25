<?php

namespace Connmix;

interface SyncNodeInterface
{

    public function setContextValue(int $clientId, string $key, $value): MessageInterface;

    public function setContext(int $clientId, array $data): MessageInterface;

    public function subscribe(int $clientId, string ...$channels): MessageInterface;

    public function unsubscribe(int $clientId, string ...$channels): MessageInterface;

    public function send(string $method, array $params = []): MessageInterface;

    public function meshSend(int $clientId, string $data): MessageInterface;

    public function meshPublish(string $channel, string $data): MessageInterface;

    public function close(): void;

}

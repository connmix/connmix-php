<?php

namespace Connmix;

interface AsyncNodeInterface
{

    public function message(): MessageInterface;

    public function pop(string ...$topics): int;

    public function setContextValue(int $clientId, string $key, $value): int;

    public function setContext(int $clientId, array $data): int;

    public function subscribe(int $clientId, string ...$channels): int;

    public function unsubscribe(int $clientId, string ...$channels): int;

    public function send(string $method, array $params = []): int;

    public function meshSend(int $clientId, string $data): int;

    public function meshPublish(string $channel, string $data): int;

    public function close(): void;

}

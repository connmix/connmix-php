<?php

namespace Connmix;

interface AsyncNodeInterface
{

    public function message(): MessageInterface;

    public function queueConsume(string ...$names): int;

    public function connCall(int $clientId, string $method, array $params): int;

    public function setContextValue(int $clientId, string $key, $value): int;

    public function subscribe(int $clientId, string ...$channels): int;

    public function send(string $method, array $params = []): int;

    public function meshSend(int $clientId, string $data): int;

    public function meshPublish(string $channel, string $data): int;

    public function close(): void;

}

<?php

namespace Connmix;

interface MessageInterface
{

    public function payload(): string;

    public function type(): string;

    public function method(): string;

    public function error(): ?array;

    public function params(): ?array;

    public function result(): ?array;

    public function id(): ?int;

    public function clientID(): int;

    public function queue(): string;

    public function data(): ?array;

    public function success(): bool;

    public function fail(): int;

    public function total(): int;

}

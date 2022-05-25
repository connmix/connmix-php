<?php

namespace Connmix;

interface MessageInterface
{

    public function payload(): string;

    public function type(): string;

    public function method(): string;

    public function event(): string;

    public function error(): ?array;

    public function id(): ?int;

    public function params(): ?array;

    public function nodeID(): int;

    public function clientID(): int;

    public function topic(): string;

    public function data(): ?array;

    public function result(): ?array;

    public function success(): bool;

    public function fail(): int;

    public function total(): int;

}

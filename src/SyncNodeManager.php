<?php

namespace Connmix;

class SyncNodeManager
{

    /**
     * @var SyncNodeInterface[]
     */
    protected $nodes = [];

    /**
     * @param SyncNodeInterface $node
     * @param string $id
     * @return void
     */
    public function set(SyncNodeInterface $node, string $id): void
    {
        $this->nodes[$id] = $node;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->nodes[$id]);
    }

    /**
     * @param string $id
     * @return SyncNodeInterface|null
     */
    public function get(string $id): ?SyncNodeInterface
    {
        return $this->nodes[$id] ?? null;
    }

    /**
     * @param string $id
     * @return void
     */
    public function delete(string $id): void
    {
        $this->nodes[$id] = null;
        unset($this->nodes[$id]);
    }

}

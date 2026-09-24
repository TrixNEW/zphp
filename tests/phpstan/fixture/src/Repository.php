<?php

declare(strict_types=1);

namespace Shop;

/**
 * @template T of object
 */
interface Repository
{
    /** @return T|null */
    public function find(int $id): ?object;

    /** @param T $entity */
    public function save(object $entity): void;
}

/**
 * @implements Repository<Order>
 */
final class InMemoryOrders implements Repository
{
    /** @var array<int, Order> */
    private array $orders = [];

    public function find(int $id): ?Order
    {
        return $this->orders[$id] ?? null;
    }

    public function save(object $entity): void
    {
        $this->orders[$entity->id] = $entity;
    }

    /** @return \Generator<int, Order> */
    public function unpaid(): \Generator
    {
        foreach ($this->orders as $id => $order) {
            if ($order->status() === Status::Pending) {
                yield $id => $order;
            }
        }
    }
}

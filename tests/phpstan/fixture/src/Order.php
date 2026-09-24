<?php

declare(strict_types=1);

namespace Shop;

enum Status: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Paid => 'Paid',
            self::Shipped => 'On its way',
        };
    }
}

final readonly class LineItem
{
    public function __construct(
        public string $sku,
        public int $quantity,
        public int $unitPriceCents,
    ) {
    }

    public function totalCents(): int
    {
        return $this->quantity * $this->unitPriceCents;
    }
}

final class Order
{
    /** @var list<LineItem> */
    private array $items = [];

    public function __construct(
        public readonly int $id,
        private Status $status = Status::Pending,
    ) {
    }

    public function add(LineItem $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    public function totalCents(): int
    {
        return array_sum(array_map(fn (LineItem $item): int => $item->totalCents(), $this->items));
    }

    public function status(): Status
    {
        return $this->status;
    }

    public function markPaid(): void
    {
        if ($this->status !== Status::Pending) {
            throw new \LogicException("Order {$this->id} is already {$this->status->label()}");
        }
        $this->status = Status::Paid;
    }

    // bug: returns a string where an int is declared
    public function itemCount(): int
    {
        return (string) count($this->items);
    }
}

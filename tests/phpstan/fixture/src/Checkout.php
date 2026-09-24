<?php

declare(strict_types=1);

namespace Shop;

final class Checkout
{
    public function __construct(private InMemoryOrders $orders)
    {
    }

    public function pay(int $orderId): string
    {
        $order = $this->orders->find($orderId);
        // bug: $order may be null
        $order->markPaid();
        $this->orders->save($order);

        return sprintf('Order %d paid: %s', $orderId, $order->status()->label());
    }

    public function summary(int $orderId): string
    {
        $order = $this->orders->find($orderId);
        if ($order === null) {
            return 'missing';
        }

        // bug: method does not exist
        return $order->describe();
    }

    /** @param array<string, int> $prices */
    public function priceOf(array $prices, string $sku): int
    {
        // bug: undefined variable
        return $prices[$sku] ?? $fallback;
    }

    public function reminders(): int
    {
        $sent = 0;
        foreach ($this->orders->unpaid() as $order) {
            // bug: wrong argument type
            $this->notify($order->id, 'reminder');
            $sent++;
        }
        return $sent;
    }

    private function notify(Order $order, string $kind): void
    {
        echo "{$kind} for order {$order->id}\n";
    }
}

<?php

declare(strict_types=1);

namespace App\Temporal\Activities;

use App\Temporal\Activities\Interfaces\OrderProcessActivityInterface;
use App\Temporal\DataTransferObjects\Workflow\Data\OrderArgs;

#[ActivityInterface]
final readonly class OrderProcessActivity implements OrderProcessActivityInterface
{
    public function process(OrderArgs $args): string
    {
        // Simulate service class and Database activities here.
        return sprintf(
            'Order %s amounting %s %s for %s',
            $args->id,
            $args->amount,
            $args->fiat,
            $args->customer
        );
    }
}

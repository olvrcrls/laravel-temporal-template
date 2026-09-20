<?php

declare(strict_types=1);

namespace App\Temporal\DataTransferObjects\Workflow\Data;

use Spatie\LaravelData\Data;

final class OrderArgs extends Data
{
    public function __construct(
       public readonly string $id,
       public readonly string $amount,
       public readonly string $fiat,
       public readonly string $customer,
    ) {}
}

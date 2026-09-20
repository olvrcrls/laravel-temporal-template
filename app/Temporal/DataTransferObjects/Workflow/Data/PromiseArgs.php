<?php

declare(strict_types=1);

namespace App\Temporal\DataTransferObjects\Workflow\Data;

use Spatie\LaravelData\Data;

final class PromiseArgs extends Data
{
    public function __construct(
      public readonly string $name,
      public readonly string $salutation,
      public readonly int $delay = 1
    ) {
    }
}

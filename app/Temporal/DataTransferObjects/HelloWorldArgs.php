<?php

declare(strict_types=1);

namespace App\Temporal\DataTransferObjects;

use \Spatie\LaravelData\Data;

class HelloWorldArgs extends Data
{
    public function __construct(
        public readonly string $name
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Temporal\DataTransferObjects\Workflow\Data;

use Spatie\LaravelData\Data;

class NestedWorkflowArgs extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $optional = null,
    ) {}
}

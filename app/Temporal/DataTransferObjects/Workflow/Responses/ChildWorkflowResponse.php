<?php

declare(strict_types=1);

namespace App\Temporal\DataTransferObjects\Workflow\Responses;

use Spatie\LaravelData\Data;

class ChildWorkflowResponse extends Data
{
    public function __construct(
        public int $id
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Temporal\DataTransferObjects\Workflow\Responses;

use App\Temporal\ResponseStatus;
use Spatie\LaravelData\Data;

class HelloWorldWorkflowResponse extends Data
{
    public function __construct(
        public readonly string $result,
        public readonly ResponseStatus $status,
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
          'greet' => $this->result,
          'status' => $this->status->value,
          'metadata' => $this->metadata
        ];
    }
}

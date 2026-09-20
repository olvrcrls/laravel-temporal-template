<?php

namespace App\Temporal\Workflows\Interfaces;

use App\Temporal\DataTransferObjects\Workflow\Data\PromiseArgs;
use Generator;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface PromiseWorkflowInterface
{
    #[WorkflowMethod(name: 'PromiseWorkflow.greet')]
    public function greet(PromiseArgs $args): Generator;
}

<?php

namespace App\Temporal\Workflows\Interfaces;

use App\Temporal\DataTransferObjects\Workflow\Data\OrderArgs;
use Generator;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface ConstructorWorkflowInterface
{
    #[WorkflowMethod(name: 'ConstructorWorkflow.handle')]
    public function handle(OrderArgs $args): Generator;
}

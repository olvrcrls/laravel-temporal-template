<?php

namespace App\Temporal\Workflows\Interfaces;

use App\Temporal\DataTransferObjects\Workflow\Data\HelloWorldArgs;
use App\Temporal\DataTransferObjects\Workflow\Data\NestedWorkflowArgs;
use Generator;
use Temporal\DataConverter\Type;
use Temporal\Workflow\ReturnType;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface NestedWorkflowInterface
{
    #[WorkflowMethod(name: "NestedWorkflow")]
    #[ReturnType(Type::TYPE_OBJECT)]
    public function handle(NestedWorkflowArgs $args): Generator;
}

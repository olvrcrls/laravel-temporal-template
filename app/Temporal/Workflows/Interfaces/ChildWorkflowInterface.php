<?php

namespace App\Temporal\Workflows\Interfaces;

use App\Temporal\DataTransferObjects\Workflow\Data\ParentWorkflowArgs;
use Generator;
use Temporal\DataConverter\Type;
use Temporal\Workflow\ReturnType;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface ChildWorkflowInterface
{
    #[WorkflowMethod(name: "ChildWorkflow")]
    #[ReturnType(Type::TYPE_OBJECT)]
    public function handle(ParentWorkflowArgs $args): Generator;
}

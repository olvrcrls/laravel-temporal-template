<?php

namespace App\Temporal\Workflows\Interfaces;

use App\Temporal\DataTransferObjects\HelloWorldArgs;
use Generator;
use Temporal\DataConverter\Type;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;
use Temporal\Workflow\ReturnType;

#[WorkflowInterface]
interface HelloWorldWorkflowInterface
{
    #[WorkflowMethod(name: "HelloWorldWorkflow")]
    #[ReturnType(Type::TYPE_OBJECT)]
    public function handle(HelloWorldArgs $args): Generator;
}

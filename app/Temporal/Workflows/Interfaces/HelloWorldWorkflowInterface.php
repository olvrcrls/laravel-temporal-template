<?php

namespace App\Temporal\Workflows\Interfaces;

use Generator;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface HelloWorldWorkflowInterface
{
    #[WorkflowMethod(name: "HelloWorldWorkflow.greet")]
    public function greet(string $name): Generator;
}

<?php

namespace App\Temporal\Workflows\Interfaces;

use App\Temporal\DataTransferObjects\HelloWorldArgs;
use Generator;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface HelloWorldWorkflowInterface
{
    #[WorkflowMethod(name: "HellowWorldWorkflow")]
    public function handle(HelloWorldArgs $args): Generator;
}

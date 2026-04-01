<?php

namespace App\Temporal\Workflows\Interfaces;

use Generator;
use Temporal\Workflow\WorkflowMethod;

interface HelloWorldWorkflowInterface
{
    #[WorkflowMethod(name: "SimpleActivity.greet")]
    public function greet(): Generator;
}

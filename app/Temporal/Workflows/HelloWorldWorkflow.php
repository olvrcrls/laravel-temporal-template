<?php

namespace App\Temporal\Workflows;

use Generator;
use Temporal\Workflow\WorkflowInterface;
use App\Temporal\Workflows\Interfaces\HelloWorldWorkflowInterface;

#[WorkflowInterface]
class HelloWorldWorkflow implements HelloWorldWorkflowInterface
{
    public function __construct()
    {
        //
    }

    public function handle(): Generator
    {
        yield $this->greet();
    }

    public function greet(): Generator
    {
        yield 'Hello World';
    }
}

<?php

namespace App\Temporal\Workflows;

use App\Temporal\Activities\Interfaces\GreetingActivityInterface;
use Carbon\CarbonInterval;
use Generator;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use App\Temporal\Workflows\Interfaces\HelloWorldWorkflowInterface;

class HelloWorldWorkflow implements HelloWorldWorkflowInterface
{
    protected ActivityProxy|GreetingActivityInterface $greetingActivity;

    public function __construct()
    {
        /**
         * Activity stub implements activity interface and proxies calls to it to Temporal activity
         * invocations. Because activities are reentrant, only a single stub can be used for multiple
         * activity invocations.
         */
        $this->greetingActivity = Workflow::newActivityStub(
            GreetingActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(2))
                ->withRetryOptions(RetryOptions::new()->withMaximumAttempts(1))
        );
    }

    public function greet(string $name): Generator
    {
        return yield $this->greetingActivity->greet(name: $name);
    }
}

<?php

declare(strict_types=1);

namespace App\Temporal\Workflows;

use App\Temporal\Activities\Interfaces\GreetingActivityInterface;
use App\Temporal\DataTransferObjects\HelloWorldArgs;
use App\Temporal\DataTransferObjects\Workflow\Responses\HelloWorldWorkflowResponse;
use App\Temporal\ResponseStatus;
use Carbon\CarbonInterval;
use Generator;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\DataConverter\Type;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Workflow;
use App\Temporal\Workflows\Interfaces\HelloWorldWorkflowInterface;

final readonly class HelloWorldWorkflow implements HelloWorldWorkflowInterface
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

    public function handle(HelloWorldArgs $args): Generator
    {
        $result = yield $this->greetingActivity->greet($args->name);

        return (new HelloWorldWorkflowResponse(
            result: $result,
            status: ResponseStatus::SUCCESS,
            metadata: ['data' => $result]
        ));
    }
}

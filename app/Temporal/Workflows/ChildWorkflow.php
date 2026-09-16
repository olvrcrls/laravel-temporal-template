<?php

declare(strict_types=1);

namespace App\Temporal\Workflows;

use App\Temporal\Activities\Interfaces\GreetingActivityInterface;
use App\Temporal\DataTransferObjects\Workflow\Responses\ChildWorkflowResponse;
use App\Temporal\DataTransferObjects\Workflow\Data\ParentWorkflowArgs;
use App\Temporal\Workflows\Interfaces\ChildWorkflowInterface;
use Carbon\CarbonInterval;
use Generator;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Workflow;

final readonly class ChildWorkflow implements ChildWorkflowInterface
{

    protected ActivityProxy|GreetingActivityInterface $greetingActivity;

    public function __construct()
    {
        $this->greetingActivity = Workflow::newActivityStub(
            GreetingActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::seconds(2))
                ->withRetryOptions(RetryOptions::new()->withMaximumAttempts(1))
        );
    }

    public function handle(ParentWorkflowArgs $args): Generator
    {
        Workflow::getLogger()->info(
            sprintf('%s started!', __CLASS__)
        );

        $id = yield $this->greetingActivity->greet(name: $args->name, email: $args->email);

        return (new ChildWorkflowResponse(id: $id));
    }
}

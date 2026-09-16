<?php

declare(strict_types=1);

namespace App\Temporal\Workflows;

use App\Temporal\Activities\Interfaces\GreetingActivityInterface;
use App\Temporal\DataTransferObjects\Workflow\Responses\HelloWorldWorkflowResponse;
use App\Temporal\DataTransferObjects\Workflow\Data\ParentWorkflowArgs;
use App\Temporal\ResponseStatus;
use App\Temporal\Workflows\Interfaces\ChildWorkflowInterface;
use App\Temporal\Workflows\Interfaces\ParentWorkflowInterface;
use Carbon\CarbonInterval;
use Generator;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Workflow;

final readonly class ParentWorkflow implements ParentWorkflowInterface
{

    protected ActivityProxy|GreetingActivityInterface $greetingActivity;

    public function __construct() {
        $this->greetingActivity = Workflow::newActivityStub(
          GreetingActivityInterface::class,
          ActivityOptions::new()
            ->withStartToCloseTimeout(CarbonInterval::seconds(2))
            ->withRetryOptions(RetryOptions::new()->withMaximumAttempts(1))
        );
    }

    public function handle(ParentWorkflowArgs $args): Generator
    {
        $result = yield $this->greetingActivity->getUserByName($args->name);

        if (! $result) {
            $child = Workflow::newChildWorkflowStub(ChildWorkflowInterface::class,);
            $childResult = yield $child->handle(args: $args);

            Workflow::getLogger()->info(sprintf('%s result: %s', __CLASS__, $childResult->id));

            $result = yield $this->greetingActivity->getUser(id: $childResult->id);
        }

        return (new HelloWorldWorkflowResponse(
            result: $result,
            status: ResponseStatus::SUCCESS,
            metadata: ['data' => $result]
        ));
    }
}

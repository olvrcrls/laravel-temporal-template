<?php

declare(strict_types=1);

namespace App\Temporal\Workflows;

use App\Temporal\Activities\Interfaces\AwaitActivityInterface;
use App\Temporal\DataTransferObjects\Workflow\Data\PromiseArgs;
use App\Temporal\Workflows\Interfaces\PromiseWorkflowInterface;
use Carbon\CarbonInterval;
use Generator;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Promise;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowInit;

final class PromiseWorkflow implements PromiseWorkflowInterface
{

    private readonly ActivityProxy|AwaitActivityInterface $awaitActivity;

    public function __construct() {
        $this->awaitActivity = Workflow::newActivityStub(
            AwaitActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::hours(2))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(1)
                )
        );
    }

    public function greet(PromiseArgs $args): Generator
    {
        $activityResponse = yield $this->awaitActivity->transitionToRead($args->delay);

        $results = yield Promise::all([$activityResponse]);
        $transitioned = $results[0];

        if ($transitioned) {
            Workflow::getLogger()->info('Transitioned');
        }

        return yield $args->salutation . ', ' . $args->name;
    }
}

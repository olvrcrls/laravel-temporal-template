<?php

declare(strict_types=1);

namespace App\Temporal\Workflows;

use App\Temporal\Activities\Interfaces\OrderProcessActivityInterface;
use App\Temporal\DataTransferObjects\Workflow\Data\OrderArgs;
use App\Temporal\Workflows\Interfaces\ConstructorWorkflowInterface;
use Carbon\CarbonInterval;
use Generator;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Internal\Workflow\ActivityProxy;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowInit;

final readonly class ConstructorWorkflow implements ConstructorWorkflowInterface
{
    private readonly ActivityProxy|OrderProcessActivityInterface $orderProcessActivity;

    #[WorkflowInit]
    public function __construct(public readonly OrderArgs $args)  {
        $this->orderProcessActivity = Workflow::newActivityStub(
            OrderProcessActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::hours(2))
                ->withRetryOptions(RetryOptions::new()->withMaximumAttempts(1))
        );
    }

    public function handle(OrderArgs $args): Generator
    {
        return yield $this->orderProcessActivity->process($args);
    }
}

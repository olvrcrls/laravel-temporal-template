<?php

declare(strict_types=1);

namespace App\Temporal\Workflows;

use App\Temporal\DataTransferObjects\Workflow\Data\NestedWorkflowArgs;
use App\Temporal\Workflows\Interfaces\NestedWorkflowInterface;
use App\Temporal\Workflows\Interfaces\ParentWorkflowInterface;
use Generator;
use Temporal\Workflow;

final readonly class NestedWorkflow implements NestedWorkflowInterface
{


    public function handle(NestedWorkflowArgs $args): Generator
    {
        Workflow::getLogger()->info(
          sprintf('%s starts!', __CLASS__)
        );

        $child = Workflow::newChildWorkflowStub(ParentWorkflowInterface::class);
        $childResult = yield $child->handle(args: $args);

        Workflow::getLogger()->info(
            sprintf('%s workflow\'s child workflow result', __CLASS__),
            ['result' => $childResult]
        );

        return $childResult;
    }
}

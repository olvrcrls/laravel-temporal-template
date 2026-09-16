<?php

use App\Temporal\DataTransferObjects\Workflow\Data\HelloWorldArgs;
use App\Temporal\DataTransferObjects\Workflow\Data\NestedWorkflowArgs;
use App\Temporal\DataTransferObjects\Workflow\Data\ParentWorkflowArgs;
use App\Temporal\Workflows\Interfaces\HelloWorldWorkflowInterface;
use App\Temporal\Workflows\Interfaces\NestedWorkflowInterface;
use App\Temporal\Workflows\Interfaces\ParentWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Keepsuit\LaravelTemporal\Facade\Temporal;
use Temporal\Common\RetryOptions;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('workflow:hello {--count=1}', function ($count) {
    // Limit the number of workflows to 1000
    if ($count < 1 || $count > 1000) {
        $count = 1;
    }

    do {
        $workflow = Temporal::newWorkflow()
            ->withWorkflowExecutionTimeout(CarbonInterval::hours(12))
            ->withRetryOptions(
                RetryOptions::new()
                    ->withMaximumAttempts(1)
            )
            ->build(HelloWorldWorkflowInterface::class);

        $run = Temporal::workflowClient()
            ->start(
                $workflow,
                new HelloWorldArgs(
                    name: fake()->unique()->name(),
                    email: fake()->unique()->safeEmail(),
                )
            );

        $this->info("Hello World workflow started! Run ID: " . $run->getExecution()->getRunID());

        $this->info(sprintf('Result: %s', json_encode($run->getResult())));
    } while (--$count > 0);
})->purpose('Launch a simple Hello World workflow');

Artisan::command('workflow:parent_child {--count=1}', function ($count) {
    if ($count < 1 || $count > 1000) {
        $count = 1;
    }

    do {
        $workflow = Temporal::newWorkflow()
            ->withWorkflowExecutionTimeout(CarbonInterval::hours(12))
            ->withRetryOptions(
                RetryOptions::new()
                    ->withMaximumAttempts(1)
            )
            ->build(ParentWorkflowInterface::class);

        $run = Temporal::workflowClient()
            ->start(
                $workflow,
                new ParentWorkflowArgs(
                    name: fake()->unique()->name(),
                    email: fake()->unique()->safeEmail(),
                )
            );

        $this->info("Parent workflow started! Run ID: " . $run->getExecution()->getRunID());

        $this->info(sprintf('Result: %s', json_encode($run->getResult())));
    } while (--$count > 0);
})->purpose('Launch a parent-child workflow');

Artisan::command('workflow:nested {--count=1}', function ($count) {
   if ($count < 1 || $count > 1000) {
       $count = 1;
   }

    do {
        $workflow = Temporal::newWorkflow()
            ->withWorkflowExecutionTimeout(CarbonInterval::hours(12))
            ->withRetryOptions(
                RetryOptions::new()
                    ->withMaximumAttempts(1)
            )
            ->build(NestedWorkflowInterface::class);

        $run = Temporal::workflowClient()
            ->start(
                $workflow,
                new NestedWorkflowArgs(
                    name: fake()->unique()->name(),
                    email: fake()->unique()->safeEmail(),
                )
            );

        $this->info("Nested workflow started! Run ID: " . $run->getExecution()->getRunID());

        $this->info(sprintf('Result: %s', json_encode($run->getResult())));
    } while (--$count > 0);
});

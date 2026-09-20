<?php

use App\Temporal\DataTransferObjects\Workflow\Data\OrderArgs;
use App\Temporal\DataTransferObjects\Workflow\Data\PromiseArgs;
use App\Temporal\DataTransferObjects\Workflow\Data\HelloWorldArgs;
use App\Temporal\DataTransferObjects\Workflow\Data\NestedWorkflowArgs;
use App\Temporal\DataTransferObjects\Workflow\Data\ParentWorkflowArgs;
use App\Temporal\Workflows\Interfaces\ConstructorWorkflowInterface;
use App\Temporal\Workflows\Interfaces\PromiseWorkflowInterface;
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
    } while (--$count > 0);
})->purpose('Launch a simple Hello World workflow');

Artisan::command('workflow:parent_child {--count=1}', function ($count) {
    if ($count < 1 || $count > 1000) {
        $count = 1;
    }

    do {
        $workflow = Temporal::newWorkflow()
            ->withWorkflowExecutionTimeout(CarbonInterval::hours(12))
            ->withWorkflowRunTimeout(CarbonInterval::minutes(1))
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
    } while (--$count > 0);
})->purpose('Launch a parent-child workflow');

Artisan::command('workflow:nested {--count=1}', function ($count) {
   if ($count < 1 || $count > 1000) {
       $count = 1;
   }

    do {
        $email = fake()->unique()->safeEmail();
        $workflow = Temporal::newWorkflow()
            ->withWorkflowExecutionTimeout(CarbonInterval::hours(12))
            ->withRetryOptions(
                RetryOptions::new()
                    ->withMaximumAttempts(1)
            )
            ->withWorkflowId(
                sprintf(
                    'NestedWorkflow-%s',
                    $email
                )
            )
            ->withTaskQueue('laravelTemporal')
            ->build(NestedWorkflowInterface::class);

        $run = Temporal::workflowClient()
            ->start(
                $workflow,
                new NestedWorkflowArgs(
                    name: fake()->unique()->name(),
                    email: $email,
                )
            );

        $this->info("Nested workflow started! Run ID: " . $run->getExecution()->getRunID());
    } while (--$count > 0);
});

Artisan::command('workflow:cron', function () {
    $email = fake()->unique()->safeEmail();
    $workflow = Temporal::newWorkflow()
        ->withCronSchedule('* * * * *')
        ->withWorkflowExecutionTimeout(CarbonInterval::hours(12))
        ->withWorkflowRunTimeout(CarbonInterval::minute())
        ->withRetryOptions(
            RetryOptions::new()
                ->withMaximumAttempts(1)
        )
        ->withWorkflowId(
            sprintf(
                'Cron-NestedWorkflow-%s',
                $email
            )
        )
        ->withTaskQueue('laravelTemporal')
        ->build(NestedWorkflowInterface::class);

    $run = Temporal::workflowClient()
        ->start(
            $workflow,
            new NestedWorkflowArgs(
                name: fake()->unique()->name(),
                email: $email,
            )
        );

    $this->info("Nested workflow started! Run ID: " . $run->getExecution()->getRunID());
});

Artisan::command('workflow:promise', function () {
    $name = fake()->unique()->name();
    $salutation = Arr::random(['Hi', 'Hey', 'Hello', 'Greetings']);

    $workflow = Temporal::newWorkflow()
        ->withWorkflowExecutionTimeout(CarbonInterval::hours(12))
        ->withWorkflowRunTimeout(CarbonInterval::minutes(10))
        ->withRetryOptions(
            RetryOptions::new()
                ->withMaximumAttempts(1)
                ->withMaximumInterval(CarbonInterval::seconds(5))
                ->withBackoffCoefficient(1.0)
        )
        ->withWorkflowId(
            sprintf(
                'PromiseWorkflow-%s',
                $name
            )
        )
        ->withMemo(['memo' => $name])
        ->build(PromiseWorkflowInterface::class);

    $run = Temporal::workflowClient()
        ->start(
            $workflow,
            new PromiseArgs(
                name: $name,
                salutation: $salutation,
                delay: mt_rand(1,5)
            ),
        );

    $this->info("Promise workflow started! Run ID: " . $run->getExecution()->getRunID());
});

Artisan::command('workflow:constructor', function () {
    $uuid = fake()->uuid();
    $amount = fake()->randomNumber();
    $customer = fake()->unique()->firstName();
    $fiat = fake()->currencyCode();

    $args = new OrderArgs(
        id: $uuid,
        amount: $amount,
        fiat: $fiat,
        customer: $customer,
    );

    $workflow = Temporal::newWorkflow()
        ->withWorkflowExecutionTimeout(CarbonInterval::hour())
        ->withWorkflowRunTimeout(CarbonInterval::hour())
        ->withRetryOptions(
            RetryOptions::new()
                ->withMaximumAttempts(1)
                ->withMaximumInterval(CarbonInterval::seconds(5))
                ->withBackoffCoefficient(1.0)
        )
        ->withMemo($args->toArray())
        ->withWorkflowId(
            sprintf(
                'ConstructorWorkflow-%s-Order-%s',
                $fiat,
                $uuid
            )
        )
        ->build(ConstructorWorkflowInterface::class);

    $run = Temporal::workflowClient()
        ->start($workflow, $args);

    $this->info("Constructor workflow started! Run ID: " . $run->getExecution()->getRunID());
});

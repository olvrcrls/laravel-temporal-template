<?php

use App\Temporal\DataTransferObjects\HelloWorldArgs;
use App\Temporal\Workflows\Interfaces\HelloWorldWorkflowInterface;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Keepsuit\LaravelTemporal\Facade\Temporal;
use Temporal\Client\WorkflowOptions;
use Temporal\Common\RetryOptions;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('workflow:hello', function () {
    $workflow = Temporal::newWorkflow()
        ->withWorkflowExecutionTimeout(CarbonInterval::hour(12))
        ->withRetryOptions(
            RetryOptions::new()
                ->withMaximumAttempts(1)
        )
        ->build(HelloWorldWorkflowInterface::class);

    $run = Temporal::workflowClient()
        ->start($workflow, new HelloWorldArgs(name: fake()->unique()->name()));

    $this->info("Hello World workflow started! Run ID: " . $run->getExecution()->getRunID());

    $this->info(sprintf('Result: %s', json_encode($run->getResult())));
})->purpose('Launch a simple Hello World workflow');

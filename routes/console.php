<?php

use App\Temporal\DataTransferObjects\HelloWorldArgs;
use App\Temporal\Workflows\Interfaces\HelloWorldWorkflowInterface;
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
    } while (--$count > 0);
})->purpose('Launch a simple Hello World workflow');

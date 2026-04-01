<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Temporal\Workflows\HelloWorldWorkflow;
use Keepsuit\LaravelTemporal\Facade\Temporal;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('workflow:hello', function () {
    for ($i = 0; $i < 10; $i++) {
        $workflow = Temporal::newWorkflow()
            ->build(HelloWorldWorkflow::class);

        $run = Temporal::workflowClient()->start($workflow);

        $this->info("Hello World workflow {$i} started! Run ID: " . $run->getExecution()->getRunID());
    }

})->purpose('Launch a simple Hello World workflow');

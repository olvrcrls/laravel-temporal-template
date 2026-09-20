<?php

declare(strict_types=1);

namespace App\Temporal\Activities;

use App\Temporal\Activities\Interfaces\AwaitActivityInterface;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Temporal\Activity\ActivityMethod;

final readonly class AwaitActivity implements AwaitActivityInterface
{
    #[ActivityMethod(name: 'transitionToRead')]
    public function transitionToRead(int $delay = 1): bool
    {
        Log::info('Transitioning to read');

        Sleep::for(CarbonInterval::minutes(abs($delay)));

        Log::info('Transitioned to true');
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Temporal\Activities\Interfaces;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'GreetingActivity.')]
interface GreetingActivityInterface
{
    #[ActivityMethod(name: 'greet')]
    public function greet(string $name): string;
}

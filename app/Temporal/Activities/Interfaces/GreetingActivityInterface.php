<?php

namespace App\Temporal\Activities\Interfaces;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'GreetingActivity.')]
interface GreetingActivityInterface
{
    #[ActivityMethod(name: 'greet')]
    public function greet(string $name): string;
}

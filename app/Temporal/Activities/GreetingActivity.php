<?php

declare(strict_types=1);

namespace App\Temporal\Activities;

use App\Temporal\Activities\Interfaces\GreetingActivityInterface;
use Temporal\Activity\ActivityMethod;

class GreetingActivity implements GreetingActivityInterface
{

    #[ActivityMethod(name: 'greet')]
    public function greet(string $name): string
    {
        return sprintf('Hello, %s, this is a workflow!', $name);
    }
}

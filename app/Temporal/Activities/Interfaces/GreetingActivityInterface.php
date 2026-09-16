<?php

declare(strict_types=1);

namespace App\Temporal\Activities\Interfaces;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'GreetingActivity.')]
interface GreetingActivityInterface
{
    #[ActivityMethod(name: 'createAndGreet')]
    public function greet(string $name, string $email): int;

    #[ActivityMethod(name: 'getUser')]
    public function getUser(int $id): string;

    #[ActivityMethod(name: 'getUserByName')]
    public function getUserByName(string $name): ?int;
}

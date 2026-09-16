<?php

declare(strict_types=1);

namespace App\Temporal\Activities;

use App\Models\User;
use App\Temporal\Activities\Interfaces\GreetingActivityInterface;
use Temporal\Activity\ActivityMethod;

class GreetingActivity implements GreetingActivityInterface
{

    #[ActivityMethod(name: 'createAndGreet')]
    public function greet(string $name, string $email): int
    {
        $user = User::query()
            ->createOrFirst(
                [
                    'name' => $name,
                    'email' => $email
                ],
                [
                    'password' => 'password',
                ]
            );

        return $user->id;
    }

    #[ActivityMethod(name: 'getUser')]
    public function getUser(int $id): string
    {
        $user = User::query()->findOrFail($id);

        return sprintf(
            'Hello, %s, this is a workflow!',
            $user->name
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Temporal\Activities\Interfaces;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\DataConverter\Type;
use Temporal\Workflow\ReturnType;

#[ActivityInterface(prefix: 'AwaitActivity.')]
interface AwaitActivityInterface
{
    #[ActivityMethod(name: 'transitionToRead')]
    #[ReturnType(Type::TYPE_BOOL)]
    public function transitionToRead(int $delay = 1): bool;
}

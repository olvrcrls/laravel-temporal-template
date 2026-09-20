<?php

namespace App\Temporal\Activities\Interfaces;

use App\Temporal\DataTransferObjects\Workflow\Data\OrderArgs;
use Temporal\Activity\ActivityInterface;
use Temporal\DataConverter\Type;
use Temporal\Workflow\ReturnType;

#[ActivityInterface(prefix: 'OrderProcessActivity.')]
interface OrderProcessActivityInterface
{
    #[ActivityMethod(name: 'process')]
    #[ReturnType(Type::TYPE_STRING)]
    public function process(OrderArgs $args): string;
}

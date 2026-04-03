<?php

namespace App\Temporal;

enum ResponseStatus: string
{
    case SUCCESS = 'success';
    case FAILURE = 'failure';
}

<?php

namespace App\Enums\Auto;

enum AutoStepStatus: string
{
    case PENDING = 'pending';
    case READY = 'ready';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case BLOCKED = 'blocked';
    case AWAITING_ACTION = 'awaiting_action';
    case ERROR = 'error';
    case SKIPPED = 'skipped';
}

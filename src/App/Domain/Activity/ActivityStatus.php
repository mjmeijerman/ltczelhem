<?php

namespace LtcZelhem\App\Domain\Activity;

enum ActivityStatus: string
{
    case REGISTRATION_OPEN = 'registration_open';
    case REGISTRATION_CLOSED = 'registration_closed';
    case IN_PROGRESS = 'in_progress';
    case FINISHED = 'finished';
}

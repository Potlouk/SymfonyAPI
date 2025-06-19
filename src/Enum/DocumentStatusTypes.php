<?php

namespace App\Enum;

enum DocumentStatusTypes {
    case IN_PROGRESS;
    case SUBMITTED;
    case DEFAULT;
    case EXPIRED;
}
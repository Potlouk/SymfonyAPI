<?php

namespace App\Enum;

enum OperationTypes {
    case CREATED;
    case EDITED;
    case SHARED;
    case SUBMITTED;
}
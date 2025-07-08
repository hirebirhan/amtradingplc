<?php

namespace App\Services\Dashboard\Enums;

enum ChartRange: string
{
    case TODAY = 'today';
    case YESTERDAY = 'yesterday';
    case WEEK = 'week';
    case MONTH = 'month';
    case THIS_MONTH = 'this_month';
    case YEAR = 'year';
} 
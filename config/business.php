<?php

return [
    /*
     | Apply the tenant's timezone to date/time calculations per request.
     |
     | Disabled by default: the app currently runs on UTC and enabling this shifts
     | day boundaries (and how existing timestamps are attributed) by the business
     | offset. Turn it on with BUSINESS_TIMEZONE_ENABLED=true only after reviewing the
     | effect on historical reports.
     */
    'apply_timezone' => env('BUSINESS_TIMEZONE_ENABLED', false),
];

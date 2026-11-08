<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Review day timezone
    |--------------------------------------------------------------------------
    |
    | App time stays UTC, and reviewed_at is stored in UTC. This zone is only
    | the calendar day used to count today's X bookmark reviews. Use an IANA
    | identifier so Eastern Daylight Time is not a fixed UTC offset.
    |
    */

    'review_timezone' => 'America/New_York',

];

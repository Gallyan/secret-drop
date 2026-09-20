<?php

use Illuminate\Support\Facades\Schedule;

// Lock TTL matches the task frequency: a killed run frees the lock by the next tick
Schedule::command('secrets:clean')->hourly()->withoutOverlapping(60);
Schedule::command('secrets:clean-blobs')->everySixHours()->withoutOverlapping(360);
Schedule::command('cache:prune-expired')->hourly()->withoutOverlapping(60);
Schedule::command('stats:prune')->daily()->withoutOverlapping(1440);
Schedule::command('storage:check')->hourly()->withoutOverlapping(60);

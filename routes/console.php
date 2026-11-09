<?php

use Illuminate\Support\Facades\Schedule;

// Release stock held by online orders that were never paid.
Schedule::command('orders:expire-unpaid')->everyTenMinutes()->withoutOverlapping();

// Housekeeping.
Schedule::command('queue:prune-failed --hours=168')->daily();

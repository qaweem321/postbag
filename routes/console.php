<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('postbag:send-scheduled')->everyMinute()->withoutOverlapping();

<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('news:discover')->everyFourHours();
Schedule::command('news:purge-archived')->daily();
Schedule::command('news:preference-digest')->dailyAt('02:30');
Schedule::command('news:tune-search-terms')->dailyAt('02:45');

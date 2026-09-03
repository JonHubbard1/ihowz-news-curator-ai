<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('news:discover')->everyFourHours();
Schedule::command('news:purge-archived')->daily();

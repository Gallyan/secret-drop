<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('secrets:clean')->hourly();

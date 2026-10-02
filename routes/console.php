<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Notifications: publications whose time came, broken chains, then the e-mails.
 * On the server: * * * * * cd /path && php artisan schedule:run
 */
Schedule::command('notifications:announce')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('notifications:announce --chain-breaks')->dailyAt('00:30')->withoutOverlapping();
Schedule::command('notifications:send instant')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('notifications:send daily')->dailyAt('18:00')->withoutOverlapping();
Schedule::command('notifications:send weekly')->weeklyOn(1, '18:00')->withoutOverlapping();

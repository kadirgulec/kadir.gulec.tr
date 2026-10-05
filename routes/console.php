<?php

use App\Models\ContactMessage;
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

// The server has no Supervisor: a short-lived worker drains the database queue every minute.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=55')->everyMinute()->withoutOverlapping();

// Contact messages older than ContactMessage::KEEP_MONTHS.
Schedule::command('model:prune', ['--model' => [ContactMessage::class]])->dailyAt('03:00');

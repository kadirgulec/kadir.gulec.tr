<?php

use App\Models\ContactMessage;
use App\Models\PageView;
use Illuminate\Support\Facades\Schedule;

/*
 * Notifications: publications whose time came, broken chains, then the e-mails.
 * On the server (Hestia cron, every minute): /usr/bin/php8.4 ~/web/kadir.gulec.tr/public_html/artisan schedule:run >> /dev/null 2>&1
 */
Schedule::command('notifications:announce')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('notifications:announce --chain-breaks')->dailyAt('00:30')->withoutOverlapping();
Schedule::command('notifications:send instant')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('notifications:send daily')->dailyAt('18:00')->withoutOverlapping();
Schedule::command('notifications:send weekly')->weeklyOn(1, '18:00')->withoutOverlapping();

// Reminders to Kadir alone: weekly and monthly chains running out of days, daily chains still unmarked.
Schedule::command('chains:remind')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('chains:remind --evening')->dailyAt('20:00')->withoutOverlapping();

// The draft review of last month, numbers frozen, with a note to Kadir.
Schedule::command('reviews:create')->monthlyOn(1, '00:15')->withoutOverlapping();

// The server has no Supervisor: a short-lived worker drains the database queue every minute.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=55')->everyMinute()->withoutOverlapping();

// Contact messages older than ContactMessage::KEEP_MONTHS, page views older than PageView::KEEP_MONTHS.
Schedule::command('model:prune', ['--model' => [ContactMessage::class, PageView::class]])->dailyAt('03:00');

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Configuration;
use App\Telegram\TelegramBotKernel;
use App\Models\TaskSubmission;
use App\Observers\TaskSubmissionObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Nutgram::class, function () {
            $token = config('nutgram.token');
            if (empty($token)) {
                return new Nutgram(new Configuration(token: 'placeholder'));
            }
            return new Nutgram(new Configuration(token: $token));
        });
    }

    public function boot(): void
    {
        TaskSubmission::observe(TaskSubmissionObserver::class);

        if (!empty(config('nutgram.token')) && config('nutgram.token') !== 'changeme') {
            $bot = app(Nutgram::class);
            app(TelegramBotKernel::class)->register($bot);
        }
    }
}

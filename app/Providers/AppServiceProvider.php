<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SergiX44\Nutgram\Nutgram;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Observers\TaskObserver;
use App\Observers\TaskSubmissionObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Nutgram::class, function () {
            $token = config('nutgram.token') ?: 'placeholder';
            return new Nutgram($token);
        });
    }

    public function boot(): void
    {
        Task::observe(TaskObserver::class);
        TaskSubmission::observe(TaskSubmissionObserver::class);
    }
}

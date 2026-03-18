<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Configuration;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Observers\TaskObserver;
use App\Observers\TaskSubmissionObserver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Nutgram::class, function () {
            $token = config('nutgram.token') ?: 'placeholder';
            $config = new Configuration(cache: Cache::store('redis'));
            return new Nutgram($token, $config);
        });
    }

    public function boot(): void
    {
        Task::observe(TaskObserver::class);
        TaskSubmission::observe(TaskSubmissionObserver::class);

        DatePicker::configureUsing(
            fn (DatePicker $c) => $c->displayFormat('d/m/Y')->native(false)
        );
        DateTimePicker::configureUsing(
            fn (DateTimePicker $c) => $c->displayFormat('d/m/Y H:i')->native(false)
        );
    }
}

<?php

namespace App\Providers;

use App\Database\Connectors\PostgresConnector;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsContributor;
use App\Models\Article;
use App\Models\SystemSetting;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('db.connector.pgsql', PostgresConnector::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRequestLimits();
        Livewire::addPersistentMiddleware([EnsureUserIsAdmin::class, EnsureUserIsContributor::class]);
        $this->shareAdminLayoutData();
    }

    protected function configureRequestLimits(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            $email = $request->input('email');

            return [
                Limit::perMinute(20)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('identity:'.hash('sha256', (is_string($email) ? strtolower($email) : '').'|'.$request->ip())),
            ];
        });

        RateLimiter::for('registration', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-recovery', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('newsletter', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Share small, real-time CMS indicators with the admin chrome.
     */
    protected function shareAdminLayoutData(): void
    {
        View::composer('layouts.admin', function ($view): void {
            $reviewNotificationsEnabled = false;
            $moderationBadgeCount = 0;

            try {
                $settings = SystemSetting::current();
                $reviewNotificationsEnabled = $settings->review_notifications;

                if ($reviewNotificationsEnabled) {
                    $moderationBadgeCount = Article::query()
                        ->where('status', 'pending_review')
                        ->count();
                }
            } catch (QueryException) {
                // During first install the settings/articles tables may not exist yet.
            }

            $view->with([
                'adminReviewNotificationsEnabled' => $reviewNotificationsEnabled,
                'adminModerationBadgeCount' => $moderationBadgeCount,
            ]);
        });
    }
}

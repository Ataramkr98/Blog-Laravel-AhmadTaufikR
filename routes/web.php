<?php

use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GlossaryController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;

// User-facing pages
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/learn', [ArticleController::class, 'learn'])->name('learn');
Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/courses', [CourseController::class, 'index'])->name('courses');
Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/terms', 'terms')->name('terms');
Route::get('/certificates/{certificate:certificate_number}', [CertificateController::class, 'show'])->name('certificates.show');
Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:newsletter')->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Auth pages (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/join-free', [RegisterController::class, 'showRegistrationForm'])->name('join-free');
    Route::post('/join-free', [RegisterController::class, 'register'])->middleware('throttle:registration')->name('register');
    Route::get('/sign-in', [LoginController::class, 'showLoginForm'])->name('sign-in');
    Route::post('/sign-in', [LoginController::class, 'login'])->middleware('throttle:login')->name('login');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:password-recovery')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->middleware('throttle:password-recovery')->name('password.update');
});

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/my-courses', [CourseController::class, 'dashboard'])->name('courses.dashboard');
    Route::post('/courses/{course:slug}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::get('/courses/{course:slug}/lessons/{lesson}', [CourseController::class, 'lesson'])->name('courses.lessons.show');
    Route::post('/courses/{course:slug}/lessons/{lesson}/complete', [CourseController::class, 'completeLesson'])->name('courses.lessons.complete');
    Route::get('/courses/{course:slug}/exam', [CourseController::class, 'exam'])->name('courses.exam');
});

// Contributor pages (admin and writers)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'contributor'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware('admin')->group(function () {
        Route::view('/content-moderation', 'admin.moderation')->name('moderation');
        Route::view('/courses', 'admin.courses')->name('courses');
        Route::get('/glossary', [GlossaryController::class, 'index'])->name('glossary');
        Route::view('/users', 'admin.users')->name('users');
        Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

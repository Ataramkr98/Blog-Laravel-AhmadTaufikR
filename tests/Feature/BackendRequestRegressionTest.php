<?php

use App\Livewire\AdminArticleManager;
use App\Livewire\AdminGlossary;
use App\Livewire\ArticleQuiz;
use App\Models\Article;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

test('registration accepts an omitted optional academic level', function () {
    $this->post(route('register'), [
        'name' => 'Nadia Pratiwi',
        'email' => 'nadia.registration@example.test',
        'password' => 'LearningBiology123!',
    ])->assertRedirect(route('home'))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', ['email' => 'nadia.registration@example.test', 'academic_level' => null, 'role' => 'pembaca']);
});

test('web endpoints return structured validation errors when json is requested', function () {
    $this->postJson(route('login'), ['email' => 'invalid', 'password' => ['unexpected']])
        ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
});

test('malformed email input produces validation json instead of a rate limiter exception', function () {
    $this->postJson(route('login'), ['email' => ['unexpected'], 'password' => 'password'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('incorrect credentials return the same validation error structure for json clients', function () {
    $this->postJson(route('login'), ['email' => 'missing@example.test', 'password' => 'incorrect'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('article quiz identifiers cannot be changed to read unpublished quiz material', function () {
    $published = Article::factory()->create();
    $draft = Article::factory()->draft()->create();

    Livewire::test(ArticleQuiz::class, ['articleId' => $published->id])->set('articleId', $draft->id);
})->throws(CannotUpdateLockedPropertyException::class);

test('login attempts are rate limited', function () {
    RateLimiter::clear('login-test');
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login'), ['email' => 'limited@example.test', 'password' => 'incorrect']);
    }

    $this->postJson(route('login'), ['email' => 'limited@example.test', 'password' => 'incorrect'])
        ->assertTooManyRequests()->assertHeader('Retry-After');
});

test('revoked administrators cannot mutate glossary through an already mounted component', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $component = Livewire::actingAs($admin)->test(AdminGlossary::class)
        ->set('term', 'Mikrobioma')->set('definition', 'Komunitas mikroorganisme di suatu habitat.');
    $admin->update(['role' => 'pembaca']);

    $component->call('saveGlossary')->assertForbidden();
    $this->assertDatabaseMissing('glossaries', ['term' => 'Mikrobioma']);
});

test('revoked contributors cannot create articles through an already mounted component', function () {
    $writer = User::factory()->create(['role' => 'penulis']);
    $component = Livewire::actingAs($writer)->test(AdminArticleManager::class);
    $writer->update(['role' => 'pembaca']);

    $component->call('openCreateModal')->assertForbidden();
});

test('maintenance lets a fresh authenticated admin session reach the dashboard', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    SystemSetting::current()->update(['maintenance_mode' => true]);
    $handler = new ArraySessionHandler(120);
    config(['session.driver' => 'maintenance-test']);
    app('session')->extend('maintenance-test', fn () => $handler);
    app()->forgetInstance('session.store');
    $session = app('session')->driver();
    $session->start();
    $session->put(auth()->guard()->getName(), $admin->id);
    $session->save();
    $sessionId = $session->getId();
    Auth::forgetGuards();
    app('session')->forgetDrivers();
    app()->forgetInstance('session.store');

    $this->withCookie(config('session.cookie'), $sessionId)->get(route('admin.dashboard'))->assertOk();
});

test('maintenance allows password reset token pages', function () {
    SystemSetting::current()->update(['maintenance_mode' => true]);

    $this->get(route('password.reset', ['token' => 'audit-token', 'email' => 'reader@example.test']))->assertOk();
    $this->getJson(route('home'))->assertServiceUnavailable()->assertJsonStructure(['message']);
});

test('expired google oauth state returns a useful error instead of a server exception', function () {
    config(['services.google.client_id' => 'client', 'services.google.client_secret' => 'secret', 'services.google.redirect' => route('auth.google.callback')]);
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);
    $socialite = Mockery::mock(SocialiteFactory::class);
    $socialite->shouldReceive('driver')->with('google')->once()->andReturn($provider);
    app()->instance(SocialiteFactory::class, $socialite);

    $this->get(route('auth.google.callback'))->assertRedirect(route('sign-in'))->assertSessionHasErrors('google');
    $this->assertGuest();
});

test('google callback rejects an unverified email before linking a local account', function () {
    $existing = User::factory()->create(['email' => 'unverified@example.test']);
    config(['services.google.client_id' => 'client', 'services.google.client_secret' => 'secret', 'services.google.redirect' => route('auth.google.callback')]);
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn((new SocialiteUser)->setRaw(['email_verified' => false])->map([
        'id' => 'unverified-google', 'email' => $existing->email, 'name' => 'Google Reader', 'avatar' => null,
    ]));
    $socialite = Mockery::mock(SocialiteFactory::class);
    $socialite->shouldReceive('driver')->with('google')->once()->andReturn($provider);
    app()->instance(SocialiteFactory::class, $socialite);

    $this->get(route('auth.google.callback'))->assertRedirect(route('sign-in'))->assertSessionHasErrors('google');
    expect($existing->fresh()->google_id)->toBeNull();
    $this->assertGuest();
});

test('google callback never replaces a different google identity linked to an email', function () {
    $existing = User::factory()->create(['email' => 'linked@example.test', 'google_id' => 'original-google']);
    config(['services.google.client_id' => 'client', 'services.google.client_secret' => 'secret', 'services.google.redirect' => route('auth.google.callback')]);
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn((new SocialiteUser)->setRaw(['email_verified' => true])->map([
        'id' => 'different-google', 'email' => $existing->email, 'name' => 'Google Reader', 'avatar' => null,
    ]));
    $socialite = Mockery::mock(SocialiteFactory::class);
    $socialite->shouldReceive('driver')->with('google')->once()->andReturn($provider);
    app()->instance(SocialiteFactory::class, $socialite);

    $this->get(route('auth.google.callback'))->assertRedirect(route('sign-in'))->assertSessionHasErrors('google');
    expect($existing->fresh()->google_id)->toBe('original-google');
    $this->assertGuest();
});

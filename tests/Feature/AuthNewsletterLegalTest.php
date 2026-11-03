<?php

use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;

test('google login redirects with the configured oauth client and callback', function () {
    config()->set('services.google', [
        'client_id' => 'google-client-id',
        'client_secret' => 'google-client-secret',
        'redirect' => route('auth.google.callback'),
    ]);

    $this->get(route('auth.google.redirect'))
        ->assertRedirect()
        ->assertRedirectContains('accounts.google.com/o/oauth2/auth')
        ->assertRedirectContains('client_id=google-client-id')
        ->assertRedirectContains(urlencode(route('auth.google.callback')));
});

test('google login returns to sign in when oauth credentials are missing', function () {
    config()->set('services.google.client_id', null);
    config()->set('services.google.client_secret', null);

    $this->get(route('auth.google.redirect'))
        ->assertRedirect(route('sign-in'))
        ->assertSessionHasErrors('google');

    $this->followingRedirects()
        ->get(route('auth.google.redirect'))
        ->assertOk()
        ->assertSee('Login Google belum dikonfigurasi.');
});

test('google callback creates a reader account and links existing accounts by email', function () {
    config()->set('services.google', [
        'client_id' => 'google-client-id',
        'client_secret' => 'google-client-secret',
        'redirect' => route('auth.google.callback'),
    ]);

    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->twice()->andReturn(
        (new SocialiteUser)->setRaw(['email_verified' => true])->map([
            'id' => 'google-123',
            'name' => 'Nadia Sains',
            'email' => 'nadia@example.test',
            'avatar' => 'https://example.test/avatar.png',
        ]),
        (new SocialiteUser)->setRaw(['email_verified' => true])->map([
            'id' => 'google-123',
            'name' => 'Nadia Sains',
            'email' => 'nadia@example.test',
            'avatar' => 'https://example.test/avatar-2.png',
        ]),
    );

    $socialite = Mockery::mock(SocialiteFactory::class);
    $socialite->shouldReceive('driver')->with('google')->twice()->andReturn($provider);
    app()->instance(SocialiteFactory::class, $socialite);

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('home'));

    $user = User::query()->where('email', 'nadia@example.test')->firstOrFail();

    expect($user)
        ->role->toBe('pembaca')
        ->google_id->toBe('google-123')
        ->avatar_url->toBe('https://example.test/avatar.png');

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('home'));

    expect(User::query()->where('email', 'nadia@example.test')->count())->toBe(1)
        ->and($user->fresh()->google_id)->toBe('google-123');
});

test('reader can request and complete password reset', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'reader@example.test']);

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Reset Password');

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);

    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('sign-in'));

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

test('newsletter subscription can be created and unsubscribed', function () {
    $this->post(route('newsletter.subscribe'), ['email' => 'bio@example.test'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $subscription = NewsletterSubscription::query()->where('email', 'bio@example.test')->firstOrFail();

    expect($subscription->status)->toBe('subscribed')
        ->and($subscription->unsubscribe_token)->not->toBeEmpty();

    $this->get(route('newsletter.unsubscribe', $subscription->unsubscribe_token))
        ->assertOk()
        ->assertSee('Berhasil berhenti berlangganan');

    expect($subscription->fresh()->status)->toBe('unsubscribed');
});

test('legal pages are available', function () {
    $this->get(route('privacy'))->assertOk()->assertSee('Kebijakan Privasi');
    $this->get(route('terms'))->assertOk()->assertSee('Syarat dan Ketentuan');
});

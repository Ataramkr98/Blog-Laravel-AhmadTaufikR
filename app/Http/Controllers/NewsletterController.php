<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        NewsletterSubscription::query()->updateOrCreate(
            ['email' => str($validated['email'])->lower()->toString()],
            [
                'user_id' => $request->user()?->id,
                'status' => 'subscribed',
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ],
        );

        return back()->with('success', 'Terima kasih! Anda sudah berlangganan newsletter BioLearn.');
    }

    public function unsubscribe(string $token): View
    {
        $subscription = NewsletterSubscription::query()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        $subscription->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        return view('newsletter.unsubscribed', compact('subscription'));
    }
}

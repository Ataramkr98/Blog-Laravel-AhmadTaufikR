@props([
    'user' => null,
    'loading' => 'lazy',
])

<span {{ $attributes->class(['inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-emerald-100 text-[10px] font-bold uppercase text-emerald-700']) }}>
    @if (filled($user?->avatar_url))
        <img
            src="{{ $user->avatar_url }}"
            alt="Foto profil {{ $user->name }}"
            class="h-full w-full object-cover"
            loading="{{ $loading }}"
            decoding="async"
            onerror="this.hidden = true; this.nextElementSibling.hidden = false"
        >
        <span hidden aria-label="{{ $user->name }}">{{ $user->initials() }}</span>
    @else
        <span aria-hidden="true">{{ $user?->initials() ?? 'BL' }}</span>
        <span class="sr-only">{{ $user?->name ?? 'BioLearn Editor' }}</span>
    @endif
</span>

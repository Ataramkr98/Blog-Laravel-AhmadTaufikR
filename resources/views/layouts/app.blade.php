<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BioLearn — Belajar Biologi Interaktif')</title>
    <meta name="description" content="@yield('meta_description', 'Jelajahi artikel biologi, glosarium interaktif, kuis, dan kursus untuk SMA dan perguruan tinggi.')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', 'BioLearn — Belajar Biologi Interaktif')">
    <meta property="og:description" content="@yield('meta_description', 'Artikel biologi, glosarium, kuis, dan kursus interaktif.')">
    <meta property="og:image" content="@yield('og_image', asset('favicon.svg'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="BioLearn">
    <meta name="twitter:card" content="@yield('twitter_card', 'summary')">
    <meta name="twitter:title" content="@yield('title', 'BioLearn — Belajar Biologi Interaktif')">
    <meta name="twitter:description" content="@yield('meta_description', 'Belajar biologi bersama BioLearn.')">
    <meta name="twitter:image" content="@yield('og_image', asset('favicon.svg'))">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @stack('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased flex flex-col">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[110] focus:rounded-xl focus:bg-white focus:p-4">Lewati ke konten utama</a>
    <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur-md" x-data="siteNavigation" @keydown.escape.window="if(open) close()">
        <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" aria-label="BioLearn beranda" class="flex shrink-0 items-center gap-2 font-mono text-xl font-bold tracking-tight">
                <img src="{{ asset('favicon.svg') }}" alt="" class="h-8 w-8">Bio<span class="-ml-2 text-emerald-700">Learn</span>
            </a>
            <nav aria-label="Navigasi utama" class="hidden items-center gap-5 text-sm font-medium lg:flex">
                @foreach (['learn' => 'Learn', 'articles.index' => 'Articles', 'courses' => 'Courses', 'about' => 'About'] as $route => $label)
                    <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif class="inline-flex min-h-11 items-center border-b-2 {{ request()->routeIs($route) ? 'border-emerald-600 text-emerald-700' : 'border-transparent text-gray-600 hover:text-emerald-700' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                @guest
                    <a href="{{ route('sign-in') }}" class="inline-flex min-h-11 items-center px-2 text-xs font-semibold text-gray-700 sm:text-sm">Sign In</a>
                    <a href="{{ route('join-free') }}" class="hidden rounded-xl bg-emerald-700 px-4 py-3 text-xs font-semibold text-white hover:bg-emerald-800 sm:inline-flex">Join Free</a>
                @else
                    <div class="relative" x-data="{ accountOpen: false }" @keydown.escape.stop="accountOpen = false; $refs.accountButton.focus()" @click.outside="accountOpen = false">
                        <button type="button" x-ref="accountButton" @click="accountOpen = !accountOpen" :aria-expanded="accountOpen" aria-controls="account-menu" aria-label="Buka menu akun" class="flex items-center gap-2 rounded-xl px-2">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-bold text-white">{{ auth()->user()->initials() }}</span>
                            <span class="hidden max-w-36 truncate text-xs sm:inline">{{ auth()->user()->name }}</span><span aria-hidden="true">⌄</span>
                        </button>
                        <div id="account-menu" x-cloak x-show="accountOpen" class="absolute right-0 top-full z-50 mt-2 w-56 rounded-2xl border border-gray-200 bg-white p-2 text-sm shadow-xl">
                            <a href="{{ route('courses.dashboard') }}" class="block rounded-xl px-4 py-3 hover:bg-emerald-50">Kursus Saya</a>
                            @if(auth()->user()->isAdmin() || auth()->user()->isPenulis())<a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-4 py-3 hover:bg-emerald-50">Dashboard</a>@endif
                            <form action="{{ route('logout') }}" method="POST" data-success-message="Anda berhasil keluar.">@csrf<button type="submit" class="w-full rounded-xl px-4 py-3 text-left text-red-700 hover:bg-red-50">Keluar (Logout)</button></form>
                        </div>
                    </div>
                @endguest
                <button type="button" x-ref="menuButton" @click="open = !open" :aria-expanded="open" aria-controls="mobile-navigation" aria-label="Buka navigasi" class="flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 lg:hidden"><span aria-hidden="true" x-text="open ? '×' : '☰'"></span></button>
            </div>
        </div>
        <nav id="mobile-navigation" aria-label="Navigasi mobile" x-cloak x-show="open" class="border-t border-gray-100 px-4 py-3 lg:hidden">
            @foreach (['learn' => 'Learn', 'articles.index' => 'Articles', 'courses' => 'Courses', 'about' => 'About'] as $route => $label)<a href="{{ route($route) }}" class="block rounded-xl px-4 py-3 text-sm hover:bg-emerald-50">{{ $label }}</a>@endforeach
            @guest<a href="{{ route('join-free') }}" class="block rounded-xl px-4 py-3 text-sm font-semibold text-emerald-700">Daftar Gratis</a>@endguest
        </nav>
        <div class="border-t border-gray-100">
            <nav aria-label="Pilih jenjang belajar" x-data="{ academicZone: @js(request('level', 'all')) }" @zone-changed.window="academicZone = $event.detail" class="mx-auto flex max-w-7xl items-center justify-center gap-2 px-4 py-1 lg:justify-end">
                <span class="mr-2 text-xs text-gray-600">Jelajahi zona</span>
                @foreach (['sma' => 'SMA', 'pt' => 'Perguruan Tinggi'] as $level => $label)
                    <a href="{{ route('articles.index', ['search' => request('search'), 'level' => $level]) }}" :class="academicZone === @js($level) ? 'bg-emerald-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-emerald-100'" :aria-current="academicZone === @js($level) ? 'true' : null" class="inline-flex min-h-9 items-center rounded-full px-4 text-xs font-semibold">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </header>
    <main id="main-content" tabindex="-1" class="min-w-0 flex-1">
        @foreach (['success' => 'emerald', 'error' => 'red'] as $type => $color)
            @if(session($type))
                <div role="{{ $type === 'error' ? 'alert' : 'status' }}" class="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8"><div class="flex items-center justify-between gap-3 rounded-2xl border p-4 {{ $type === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800' }}"><span class="text-sm">{{ session($type) }}</span><button type="button" aria-label="Tutup pemberitahuan" onclick="this.parentElement.parentElement.remove()" class="px-3">×</button></div></div>
            @endif
        @endforeach
        @yield('content')
    </main>
    <footer class="border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="font-mono text-xl font-bold">Bio<span class="text-emerald-700">Learn</span></p><p class="mt-4 text-sm leading-relaxed text-gray-600">Belajar biologi melalui artikel, glosarium, dan kursus interaktif untuk SMA dan perguruan tinggi.</p></div>
                <nav aria-label="Sumber belajar"><h2 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-600">Sumber Belajar</h2>@foreach (['learn' => 'Mulai Belajar', 'articles.index' => 'Science Articles', 'courses' => 'Katalog Courses'] as $route => $label)<a href="{{ route($route) }}" class="block py-2 text-sm text-gray-600 hover:text-emerald-700">{{ $label }}</a>@endforeach</nav>
                <nav aria-label="Tentang platform"><h2 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-600">BioLearn</h2>@foreach (['about' => 'About Us', 'privacy' => 'Privacy Policy', 'terms' => 'Terms'] as $route => $label)<a href="{{ route($route) }}" class="block py-2 text-sm text-gray-600 hover:text-emerald-700">{{ $label }}</a>@endforeach</nav>
                <div class="min-w-0"><h2 class="mb-3 text-xs font-bold uppercase tracking-wider text-gray-600">Newsletter</h2><p class="mb-4 text-sm leading-relaxed text-gray-600">Daftarkan email untuk pembaruan materi BioLearn.</p>
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="space-y-3" data-success-message="Email Anda berhasil didaftarkan untuk newsletter.">@csrf<label for="newsletter-email" class="sr-only">Email newsletter</label><input id="newsletter-email" type="email" name="email" autocomplete="email" placeholder="Alamat email Anda" required class="w-full rounded-xl border-gray-300 text-sm"><button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-800"><span data-submit-spinner class="loading-spinner" aria-hidden="true"></span>Subscribe</button></form>
                </div>
            </div>
            <p class="mt-10 border-t border-gray-100 pt-6 text-xs leading-relaxed text-gray-600">&copy; {{ date('Y') }} Biology Learn. Made for Future Scientists.</p>
        </div>
    </footer>
    <x-request-status />
    @livewireScriptConfig
</body>
</html>

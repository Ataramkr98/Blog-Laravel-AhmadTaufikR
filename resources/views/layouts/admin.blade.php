<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Dashboard - Biology Learn')</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased min-h-screen flex" x-data="siteNavigation" @keydown.escape.window="if(open) close()" @resize.window="mobile = window.innerWidth < 1024; if(!mobile) open = false">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:z-[110] focus:bg-white focus:p-4">Lewati ke konten utama</a>
    <div x-cloak x-show="open" @click="close()" class="fixed inset-0 z-40 bg-gray-900/60 lg:hidden" aria-hidden="true"></div>

    <aside id="admin-navigation" aria-label="Menu administrasi" :inert="mobile && !open" :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" x-trap.inert.noscroll="open" class="fixed inset-y-0 left-0 w-64 overflow-y-auto bg-white border-r border-gray-200 flex flex-col justify-between z-50 -translate-x-full lg:translate-x-0 transition-transform">
        <button type="button" @click="close()" aria-label="Tutup navigasi" class="absolute right-2 top-2 w-11 rounded-xl bg-white lg:hidden">×</button>
        
        <div>
            <div class="h-16 px-6 border-b border-gray-100 flex items-center space-x-2">
                <svg class="w-8 h-8 text-emerald-700 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
                <span class="text-lg font-bold tracking-tight text-gray-900 font-mono">BioLearn <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">CMS</span></span>
            </div>

            <nav class="p-4 space-y-1">
                
                <a href="{{ route('admin.dashboard') }}" class="group flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-950' }}">
                    <span class="flex items-center space-x-3">
                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('admin.dashboard') ? 'text-emerald-700' : 'text-gray-500 group-hover:text-gray-900' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
                        </svg>
                        <span>Dashboard</span>
                    </span>
                </a>

                <a href="{{ route('admin.articles') }}" class="group flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.articles') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-950' }}">
                    <span class="flex items-center space-x-3">
                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('admin.articles') ? 'text-emerald-700' : 'text-gray-500 group-hover:text-gray-900' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 012 2v6a2 2 0 01-2 2h-2a2 2 0 01-2-2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8h4m-4 4h6" />
                        </svg>
                        <span>Kelola Artikel</span>
                    </span>
                </a>

                @if(auth()->user()?->isAdmin())
                
                <a href="{{ route('admin.moderation') }}" class="group flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.moderation') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-950' }}">
                    <span class="flex items-center space-x-3">
                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('admin.moderation') ? 'text-emerald-700' : 'text-gray-500 group-hover:text-gray-900' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Moderasi Konten</span>
                    </span>
                    @if(($adminReviewNotificationsEnabled ?? false) && ($adminModerationBadgeCount ?? 0) > 0)
                        <span data-testid="moderation-badge" class="bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">{{ $adminModerationBadgeCount }}</span>
                    @endif
                </a>

                <a href="{{ route('admin.glossary') }}" class="group flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.glossary') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-950' }}">
                    <span class="flex items-center space-x-3">
                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('admin.glossary') ? 'text-emerald-700' : 'text-gray-500 group-hover:text-gray-900' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Glossary Kamus</span>
                    </span>
                </a>

                <a href="{{ route('admin.courses') }}" class="group flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.courses') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-950' }}">
                    <span class="flex items-center space-x-3">
                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('admin.courses') ? 'text-emerald-700' : 'text-gray-500 group-hover:text-gray-900' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25A8.966 8.966 0 0118 3.75c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        <span>Kelola Kursus</span>
                    </span>
                </a>

                <a href="{{ route('admin.users') }}" class="group flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.users') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-950' }}">
                    <span>Manajemen Pengguna</span>
                </a>

                <a href="{{ route('admin.settings') }}" class="group flex items-center justify-between px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.settings') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-950' }}">
                    <span class="flex items-center space-x-3">
                        <svg class="w-5 h-5 transition-colors {{ request()->routeIs('admin.settings') ? 'text-emerald-700' : 'text-gray-500 group-hover:text-gray-900' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0" />
                        </svg>
                        <span>Pengaturan</span>
                    </span>
                </a>
                @endif
            </nav>
        </div>

        <div class="p-4 border-t border-gray-100 bg-gray-50/50 flex flex-col space-y-2">
            <a href="{{ route('admin.profile') }}" class="flex items-center space-x-3 p-2 rounded-xl hover:bg-gray-100 transition-all">
                <span class="w-9 h-9 rounded-full bg-emerald-700 text-white flex items-center justify-center text-xs font-bold font-mono">
                    {{ auth()->user()->initials() }}
                </span>
                <div class="flex-grow min-w-0">
                    <p class="text-xs font-bold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-gray-500 truncate capitalize">{{ auth()->user()->role }}</p>
                </div>
                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="w-full">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium text-red-600 hover:bg-red-50 transition-colors flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Log Out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="min-w-0 w-full lg:pl-64 flex flex-col min-h-screen">
        
        <header class="sticky top-0 z-30 flex min-h-16 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 sm:px-8">
            <button type="button" x-ref="menuButton" @click="open = !open" :aria-expanded="open" aria-controls="admin-navigation" aria-label="Buka navigasi admin" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-200 lg:hidden">☰</button>
            <form action="{{ route('admin.articles') }}" method="GET" class="flex min-w-0 max-w-lg flex-1 items-center gap-2">
                <label for="admin-search" class="sr-only">Cari artikel di CMS</label>
                <input id="admin-search" type="search" name="search" value="{{ request('search') }}" maxlength="200" placeholder="Cari artikel…" class="w-full rounded-xl border-gray-300 bg-gray-50 text-sm">
                <button type="submit" class="rounded-xl bg-gray-900 px-3 text-xs font-semibold text-white">Cari</button>
            </form>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" aria-label="Kunjungi BioLearn di tab baru" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl border border-gray-200 px-3 text-xs text-gray-700"><span aria-hidden="true">↗</span><span class="hidden sm:inline">Kunjungi BioLearn</span></a>
        </header>

        <main id="main-content" tabindex="-1" class="min-w-0 flex-grow p-4 sm:p-8">
            @if(session('success'))
                <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-sm">
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                    <button aria-label="Tutup pemberitahuan" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-800">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-sm">
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                    <button aria-label="Tutup pemberitahuan" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-800">&times;</button>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <x-request-status />
    @livewireScriptConfig
</body>
</html>

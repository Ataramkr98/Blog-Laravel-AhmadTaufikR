@extends('layouts.app')
@section('title', 'Sign In - BioLearn')
@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-16">
    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm md:grid md:grid-cols-5">
        <aside class="relative hidden flex-col justify-between bg-emerald-950 p-10 text-white md:col-span-2 md:flex">
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-300">Ruang belajar Anda</p>
            <div class="my-14 space-y-5"><span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl border border-emerald-700 bg-emerald-900 text-3xl" aria-hidden="true">✳</span><h2 class="text-3xl font-bold leading-tight">Lanjutkan rasa ingin tahu Anda.</h2><p class="text-sm leading-relaxed text-emerald-100">Baca materi, pahami istilah ilmiah, lalu uji pemahaman melalui kuis dan kursus terstruktur.</p></div>
            <p class="border-t border-emerald-800 pt-5 text-xs leading-relaxed text-emerald-200">Progres belajar dan sertifikat kursus tersimpan di akun Anda.</p>
        </aside>
        <div class="p-6 sm:p-10 md:col-span-3">
            <h1 class="text-2xl font-bold tracking-tight">Selamat datang kembali</h1>
            <p class="mt-2 text-sm text-gray-600">Belum punya akun? <a href="{{ route('join-free') }}" class="font-semibold text-emerald-700 underline underline-offset-4">Daftar Gratis</a></p>
            @error('google')<div role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-sm text-red-800">{{ $message }}</div>@enderror
            <form action="{{ route('login') }}" method="POST" class="mt-8 space-y-5" data-success-message="Selamat datang! Akun Anda berhasil masuk.">
                @csrf
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="mb-3 text-xs leading-relaxed text-emerald-900">Jelajahi pengalaman belajar dengan akun demo yang sudah berisi progres kursus.</p>
                    <button type="button" data-demo-login data-email="{{ config('demo.email') }}" data-password="{{ config('demo.password') }}" class="w-full rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-800">Login Akun Demo</button>
                </div>
                <div><label for="login-email" class="mb-2 block text-sm font-semibold">Alamat email</label><input id="login-email" type="email" name="email" required value="{{ old('email') }}" autocomplete="username" placeholder="nama@domain.com" class="w-full rounded-xl border-gray-300 text-sm" @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror>@error('email')<p id="login-email-error" class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                <div x-data="{ visible: false }"><div class="mb-2 flex flex-wrap items-center justify-between gap-2"><label for="login-password" class="text-sm font-semibold">Password</label><a href="{{ route('password.request') }}" class="text-xs font-semibold text-emerald-700 underline underline-offset-4">Lupa Password?</a></div><div class="relative"><input id="login-password" :type="visible ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" class="w-full rounded-xl border-gray-300 pr-24 text-sm"><button type="button" @click="visible = !visible" :aria-pressed="visible" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-gray-600" x-text="visible ? 'Sembunyikan' : 'Lihat'" aria-label="Tampilkan password">Lihat</button></div>@error('password')<p class="mt-2 text-xs text-red-700">{{ $message }}</p>@enderror</div>
                <label class="flex min-h-11 items-center gap-3 text-xs text-gray-700"><input type="checkbox" name="remember" class="h-4 w-4 accent-emerald-700">Ingat login saya di perangkat ini</label>
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white hover:bg-gray-800"><span data-submit-spinner class="loading-spinner" aria-hidden="true"></span>Masuk Sistem</button>
            </form>
            <div class="my-6 flex items-center gap-3 text-xs text-gray-500"><span class="h-px flex-1 bg-gray-200"></span>atau<span class="h-px flex-1 bg-gray-200"></span></div>
            <a href="{{ route('auth.google.redirect') }}" class="flex min-h-11 items-center justify-center gap-3 rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold hover:bg-gray-50"><span aria-hidden="true" class="font-bold text-blue-700">G</span>Masuk dengan Google</a>
        </div>
    </div>
</div>
@endsection

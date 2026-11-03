@extends('layouts.app')

@section('title', 'Buat Password Baru - BioLearn')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-extrabold text-gray-900">Buat Password Baru</h1>
        <form action="{{ route('password.update') }}" method="POST" data-success-message="Password berhasil diperbarui. Silakan masuk dengan password baru." class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="block">
                <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Email</span>
                <input type="email" name="email" required value="{{ old('email', $email) }}" class="mt-1 w-full rounded-xl border-gray-200 text-sm">
            </label>
            <label class="block">
                <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Password baru</span>
                <input type="password" name="password" required class="mt-1 w-full rounded-xl border-gray-200 text-sm">
            </label>
            <label class="block">
                <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Konfirmasi password</span>
                <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-xl border-gray-200 text-sm">
            </label>
            @if ($errors->any())
                <div class="rounded-2xl bg-red-50 p-4 text-xs text-red-700">{{ $errors->first() }}</div>
            @endif
            <button type="submit" class="w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Simpan Password</button>
        </form>
    </div>
</div>
@endsection

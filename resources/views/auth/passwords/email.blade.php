@extends('layouts.app')

@section('title', 'Reset Password - BioLearn')

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
    <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-extrabold text-gray-900">Reset Password</h1>
        <p class="mt-2 text-sm text-gray-500">Masukkan email akun Anda. Kami akan mengirim tautan reset password.</p>

        @if (session('status'))
            <div class="mt-5 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" class="mt-6 space-y-4" data-success-message="Permintaan reset password berhasil diproses. Periksa email Anda.">
            @csrf
            <label class="block">
                <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Email</span>
                <input type="email" name="email" required value="{{ old('email') }}" class="mt-1 w-full rounded-xl border-gray-200 text-sm">
            </label>
            @error('email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Kirim Link Reset</button>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Newsletter Unsubscribed - BioLearn')

@section('content')
<div class="max-w-xl mx-auto px-4 py-16 text-center">
    <div class="rounded-3xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-extrabold text-gray-900">Berhasil berhenti berlangganan</h1>
        <p class="mt-3 text-sm text-gray-500">{{ $subscription->email }} tidak akan menerima newsletter BioLearn berikutnya.</p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-700">Kembali ke Beranda</a>
    </div>
</div>
@endsection

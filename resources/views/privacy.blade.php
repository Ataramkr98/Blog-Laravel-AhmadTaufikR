@extends('layouts.app')

@section('title', 'Kebijakan Privasi - BioLearn')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <article class="rounded-3xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-3xl font-extrabold text-gray-900">Kebijakan Privasi</h1>
        <p class="mt-4 text-sm leading-7 text-gray-600">BioLearn menyimpan data akun, progres kursus, hasil ujian, sertifikat, dan langganan newsletter untuk menjalankan layanan belajar. Data tidak dijual kepada pihak ketiga.</p>
        <h2 class="mt-8 text-lg font-bold text-gray-900">Data yang diproses</h2>
        <p class="mt-2 text-sm leading-7 text-gray-600">Nama, email, role, login Google jika digunakan, aktivitas enrollment, lesson progress, final exam attempts, dan certificate number.</p>
        <h2 class="mt-8 text-lg font-bold text-gray-900">Kontrol pengguna</h2>
        <p class="mt-2 text-sm leading-7 text-gray-600">Pengguna dapat berhenti dari newsletter melalui tautan unsubscribe. Permintaan koreksi data akun dapat diajukan ke pengelola BioLearn.</p>
    </article>
</div>
@endsection

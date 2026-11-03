@extends('layouts.admin')

@section('title', 'System Settings - BioLearn Admin')

@section('content')
<div class="space-y-8" x-data="{
    maintenanceMode: {{ $settings['maintenanceMode'] ? 'true' : 'false' }},
    reviewNotifications: {{ $settings['reviewNotifications'] ? 'true' : 'false' }},
    glossaryCacheMinutes: {{ $settings['glossaryCacheMinutes'] }}
}">

    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 font-sans">System Settings</h1>
        <p class="text-xs text-gray-500 font-sans mt-0.5">Atur parameter fungsionalitas platform BioLearn, notifikasi, dan keamanan server.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-200 p-5 sm:p-8 shadow-sm space-y-8">
            <h3 class="text-sm font-bold text-gray-900 border-b border-gray-50 pb-3">General Settings</h3>

            <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <input type="hidden" name="maintenanceMode" :value="maintenanceMode ? 1 : 0">
                <input type="hidden" name="reviewNotifications" :value="reviewNotifications ? 1 : 0">

                <div class="flex items-center justify-between gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-800">Maintenance Mode (Mode Pemeliharaan)</label>
                        <p class="text-[10px] text-gray-500 max-w-sm">Aktifkan untuk memblokir sementara akses publik dan menampilkan halaman maintenance. Admin tetap dapat mengakses.</p>
                    </div>
                    
                    <button
                        type="button"
                        role="switch" aria-label="Maintenance Mode" :aria-checked="maintenanceMode" @click="maintenanceMode = !maintenanceMode"
                        :class="maintenanceMode ? 'bg-emerald-700' : 'bg-gray-200'"
                        class="relative inline-flex items-center h-11 w-16 px-1 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                    >
                        <span
                            :class="maintenanceMode ? 'translate-x-7' : 'translate-x-0'"
                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-sm ring-0 transition duration-200"
                        ></span>
                    </button>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-gray-800">Review Notifications</label>
                        <p class="text-[10px] text-gray-500 max-w-sm">Tampilkan badge jumlah artikel pending review di dashboard admin.</p>
                    </div>
                    
                    <button
                        type="button"
                        role="switch" aria-label="Review Notifications" :aria-checked="reviewNotifications" @click="reviewNotifications = !reviewNotifications"
                        :class="reviewNotifications ? 'bg-emerald-700' : 'bg-gray-200'"
                        class="relative inline-flex items-center h-11 w-16 px-1 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                    >
                        <span
                            :class="reviewNotifications ? 'translate-x-7' : 'translate-x-0'"
                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-sm ring-0 transition duration-200"
                        ></span>
                    </button>
                </div>

                <div class="space-y-1 pt-4 border-t border-gray-50">
                    <label for="form-settings.blade-1" class="text-[9px] font-bold text-gray-500 uppercase tracking-wider font-mono">Glossary Cache Timeout (Minutes)</label>
                    <input id="form-settings.blade-1" type="number" name="glossaryCacheMinutes" x-model="glossaryCacheMinutes" class="w-full sm:w-48 px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 font-mono">
                    <p class="text-[9px] text-gray-500">Durasi penyimpanan cache untuk mempercepat pemuatan nama latin di glossary.</p>
                    @error('glossaryCacheMinutes')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-6 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="bg-emerald-700 hover:bg-emerald-700 text-white text-xs font-bold px-6 py-2.5 rounded-xl transition-all shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>
@endsection

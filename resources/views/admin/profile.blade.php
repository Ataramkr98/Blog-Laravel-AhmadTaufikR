@extends('layouts.admin')

@section('title', 'Admin Profile - BioLearn Admin')

@section('content')
<div class="space-y-8">

    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 font-sans">Admin Profile Settings</h1>
        <p class="text-xs text-gray-500 font-sans mt-0.5">Kelola identitas publik Anda, kredensial pengajar akademis, dan kata sandi masuk.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="bg-white rounded-3xl border border-gray-200 p-6 shadow-sm flex flex-col items-center text-center space-y-4">
            
            <div class="relative group">
                <span class="w-32 h-32 rounded-full bg-emerald-700 text-white flex items-center justify-center text-3xl font-bold font-mono border-4 border-emerald-100 p-1">
                    {{ auth()->user()->initials() }}
                </span>
            </div>
            
            <div class="space-y-1">
                <h3 class="text-base font-bold text-gray-900 font-sans">{{ auth()->user()->name }}</h3>
                <span class="inline-block bg-emerald-50 text-emerald-700 text-[10px] font-bold tracking-wider px-2.5 py-0.5 rounded-full font-mono uppercase">{{ auth()->user()->role }}</span>
                <p class="text-[10px] text-gray-500 font-sans">{{ auth()->user()->email }}</p>
            </div>

            <div class="pt-4 border-t border-gray-100 w-full text-xs text-gray-500 text-left space-y-2 font-sans">
                <div class="flex justify-between">
                    <span>ID Anggota:</span>
                    <span class="font-bold text-gray-800 font-mono">#BL-{{ str_pad(auth()->id(), 5, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Bergabung Sejak:</span>
                    <span class="font-bold text-gray-800 font-mono">{{ auth()->user()->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-3xl border border-gray-200 p-5 sm:p-8 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-gray-900 border-b border-gray-50 pb-3">Informasi Akun</h3>
                
                <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-4" data-success-message="Pengaturan akun berhasil diperbarui.">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="form-profile.blade-1" class="text-[9px] font-bold text-gray-500 uppercase tracking-wider font-mono">Nama Lengkap</label>
                            <input id="form-profile.blade-1" type="text" name="name" value="{{ old('name', auth()->user()->name) }}" required class="w-full px-3 py-2 border @error('name') border-red-500 @else border-gray-200 @enderror rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 font-semibold">
                            @error('name')
                                <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="form-profile.blade-2" class="text-[9px] font-bold text-gray-500 uppercase tracking-wider font-mono">Gelar / Jabatan</label>
                            <input id="form-profile.blade-2" type="text" value="{{ strtoupper(auth()->user()->role) }}" disabled class="w-full px-3 py-2 border border-gray-100 bg-gray-50 text-gray-500 rounded-xl text-xs font-semibold cursor-not-allowed">
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="form-profile.blade-3" class="text-[9px] font-bold text-gray-500 uppercase tracking-wider font-mono">Alamat Email Kerja</label>
                        <input id="form-profile.blade-3" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required class="w-full px-3 py-2 border @error('email') border-red-500 @else border-gray-200 @enderror rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 font-semibold">
                        @error('email')
                            <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-emerald-700 hover:bg-emerald-700 text-white text-xs font-bold px-6 py-2.5 rounded-xl transition-all shadow-md">
                            Perbarui Profil
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-3xl border border-gray-200 p-5 sm:p-8 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-gray-900 border-b border-gray-50 pb-3">Atur Ulang Kata Sandi</h3>
                
                <form action="{{ route('admin.profile.password') }}" method="POST" class="space-y-4" data-success-message="Pengaturan akun berhasil diperbarui.">
                    @csrf
                    @method('PUT')
                    
                    <div class="space-y-1">
                        <label for="form-profile.blade-4" class="text-[9px] font-bold text-gray-500 uppercase tracking-wider font-mono">Kata Sandi Lama</label>
                        <input id="form-profile.blade-4" type="password" name="current_password" required placeholder="••••••••" class="w-full px-3 py-2 border @error('current_password') border-red-500 @else border-gray-200 @enderror rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                        @error('current_password')
                            <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="form-profile.blade-5" class="text-[9px] font-bold text-gray-500 uppercase tracking-wider font-mono">Kata Sandi Baru</label>
                            <input id="form-profile.blade-5" type="password" name="password" required placeholder="Minimal 8 karakter" class="w-full px-3 py-2 border @error('password') border-red-500 @else border-gray-200 @enderror rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                            @error('password')
                                <p class="text-[10px] text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="form-profile.blade-6" class="text-[9px] font-bold text-gray-500 uppercase tracking-wider font-mono">Konfirmasi Kata Sandi Baru</label>
                            <input id="form-profile.blade-6" type="password" name="password_confirmation" required placeholder="Minimal 8 karakter" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-emerald-700 hover:bg-emerald-700 text-white text-xs font-bold px-6 py-2.5 rounded-xl transition-all shadow-sm">
                            Ubah Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection

<div class="space-y-6">
    <x-livewire-feedback />
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">Manajemen Pengguna</h1>
        <p class="mt-1 text-xs text-gray-500">Kelola hak akses Admin, Penulis, dan Pembaca.</p>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <input aria-label="Cari nama atau email pengguna" wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nama atau email..." class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm">
        @error('role') <p class="mt-3 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Pengguna</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-4 font-bold text-gray-900">{{ $user->name }}</td>
                            <td class="px-5 py-4 text-gray-500">{{ $user->email }}</td>
                            <td class="px-5 py-4">
                                <select
                                    aria-label="Hak akses {{ $user->name }}" wire:loading.attr="disabled" wire:change="changeRole({{ $user->id }}, $event.target.value)"
                                    class="rounded-lg border border-gray-200 px-3 py-2"
                                    @disabled($user->id === auth()->id())
                                >
                                    <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                    <option value="penulis" @selected($user->role === 'penulis')>Penulis</option>
                                    <option value="pembaca" @selected($user->role === 'pembaca')>Pembaca</option>
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-10 text-center text-sm text-gray-600">Tidak ada pengguna yang cocok. Coba nama atau email lain.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 p-4">{{ $users->links() }}</div>
    </div>
</div>

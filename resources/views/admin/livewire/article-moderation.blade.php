<div class="space-y-6">
    <x-livewire-feedback />
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">Moderasi Artikel</h1>
        <p class="mt-1 text-xs text-gray-500">Setujui, minta revisi, tolak, atau arsipkan artikel.</p>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-4">
        <label for="field-article-moderation.blade-reviewNotes" class="text-xs font-bold uppercase tracking-wider text-gray-500">Catatan review</label>
        <textarea id="field-article-moderation.blade-reviewNotes" wire:model="reviewNotes" rows="3" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" placeholder="Wajib untuk revisi atau penolakan."></textarea>
        @error('reviewNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    @if($articles->count() > 0)
    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Artikel</th>
                        <th class="px-5 py-3">Penulis</th>
                        <th class="px-5 py-3">Zona</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($articles as $article)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-bold text-gray-900">{{ $article->title }}</p>
                                <p class="mt-1 text-[10px] text-gray-500">{{ $article->category?->name }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $article->author?->name ?? 'Tidak diketahui' }}</td>
                            <td class="px-5 py-4 uppercase">{{ $article->level }}</td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="requestRevision({{ $article->id }})" class="rounded-lg border border-amber-200 px-3 py-2 font-bold text-amber-700">Minta Revisi</button>
                                    <button type="button" wire:click="reject({{ $article->id }})" class="rounded-lg border border-red-200 px-3 py-2 font-bold text-red-700">Tolak</button>
                                    <button type="button" wire:click="approve({{ $article->id }})" class="rounded-lg bg-emerald-700 px-3 py-2 font-bold text-white">Terbitkan</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-gray-500">Tidak ada artikel menunggu review.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $articles->links() }}</div>
    </div>
    @endif

    @if($archivableArticles->count() > 0)
    <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
            <h2 class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Arsip Artikel</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                <thead class="bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3">Artikel</th>
                        <th class="px-5 py-3">Penulis</th>
                        <th class="px-5 py-3">Zona</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($archivableArticles as $article)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-bold text-gray-900">{{ $article->title }}</p>
                                <p class="mt-1 text-[10px] text-gray-500">{{ $article->category?->name }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $article->author?->name ?? 'Tidak diketahui' }}</td>
                            <td class="px-5 py-4 uppercase">{{ $article->level }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-[10px] font-bold
                                    @if($article->status === 'published') bg-emerald-50 text-emerald-700
                                    @elseif($article->status === 'rejected') bg-red-50 text-red-700
                                    @else bg-gray-100 text-gray-700
                                    @endif">
                                    {{ match($article->status) {
                                        'published' => 'Diterbitkan',
                                        'rejected' => 'Ditolak',
                                        'draft' => 'Draf',
                                        default => $article->status,
                                    } }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end">
                                    <button type="button" wire:click="archive({{ $article->id }})" class="rounded-lg border border-gray-200 px-3 py-2 font-bold text-gray-700 hover:bg-gray-50">Arsipkan</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">Tidak ada artikel yang dapat diarsipkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 p-4">{{ $archivableArticles->links() }}</div>
    </div>
    @endif
</div>

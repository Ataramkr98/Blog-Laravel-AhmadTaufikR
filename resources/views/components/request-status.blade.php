<div x-data class="print:hidden">
    <div x-cloak x-show="$store.ui.loading" role="status" aria-live="polite" class="fixed bottom-4 left-4 z-[100] flex items-center gap-3 rounded-2xl bg-gray-900 px-4 py-3 text-sm text-white shadow-lg">
        <span class="loading-spinner" aria-hidden="true"></span> Memproses permintaan…
    </div>
    <div x-cloak x-show="!$store.ui.online" role="status" class="fixed inset-x-4 bottom-20 z-[100] mx-auto max-w-lg rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">Koneksi terputus. Sambungkan kembali perangkat untuk menyimpan perubahan.</div>
    <div x-cloak x-show="$store.ui.message" :role="$store.ui.kind === 'error' ? 'alert' : 'status'" aria-live="polite" class="fixed inset-x-4 top-20 z-[100] mx-auto flex max-w-xl items-start gap-3 rounded-2xl border bg-white p-4 text-sm shadow-xl" :class="$store.ui.kind === 'error' ? 'border-red-300 text-red-800' : 'border-emerald-300 text-emerald-800'">
        <span class="flex-1" x-text="$store.ui.message"></span>
        <button type="button" aria-label="Tutup pemberitahuan" @click="$store.ui.message = ''" class="shrink-0 rounded-lg px-3">×</button>
    </div>
</div>

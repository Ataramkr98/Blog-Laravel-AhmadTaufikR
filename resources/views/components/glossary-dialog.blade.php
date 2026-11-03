<dialog x-ref="glossaryDialog" aria-label="Definisi glosarium" @click="if($event.target === $el) $el.close()" class="w-[calc(100%-2rem)] max-w-md rounded-3xl border border-gray-200 bg-white p-6 text-gray-900 shadow-2xl">
    <div class="flex items-start justify-between gap-4">
        <div><h3 class="font-serif text-xl font-bold italic" x-text="selectedGlossary?.term"></h3><p class="mt-1 text-xs font-bold text-emerald-800" x-text="selectedGlossary?.commonName"></p></div>
        <button type="button" @click="$refs.glossaryDialog.close()" class="shrink-0 rounded-xl px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100" aria-label="Tutup definisi">Tutup</button>
    </div>
    <p class="mt-5 text-sm leading-relaxed text-gray-700" x-text="selectedGlossary?.definition"></p>
</dialog>

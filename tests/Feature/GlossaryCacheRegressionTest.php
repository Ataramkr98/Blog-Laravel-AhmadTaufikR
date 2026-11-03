<?php

use App\Livewire\AdminGlossary;
use App\Livewire\GlossarySearch;
use App\Models\Glossary;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('glossary search uses the configured cache duration', function () {
    SystemSetting::current()->update(['glossary_cache_minutes' => 2]);
    $term = Glossary::factory()->create(['term' => 'ATP', 'definition' => 'Definisi awal ATP.']);
    $search = Livewire::test(GlossarySearch::class)->set('query', 'ATP')->assertSee('Definisi awal ATP.');
    DB::table('glossaries')->where('id', $term->id)->update(['definition' => 'Definisi ATP diperbarui.']);

    $search->call('$refresh')->assertSee('Definisi awal ATP.')->assertDontSee('Definisi ATP diperbarui.');
    $this->travel(3)->minutes();
    $search->call('$refresh')->assertSee('Definisi ATP diperbarui.');
});

test('editing glossary through the admin immediately invalidates cached search results', function () {
    $term = Glossary::factory()->create(['term' => 'ATP', 'definition' => 'Definisi awal ATP.']);
    $search = Livewire::test(GlossarySearch::class)->set('query', 'ATP')->assertSee('Definisi awal ATP.');
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)->test(AdminGlossary::class)->call('editGlossary', $term->id)
        ->set('definition', 'Definisi terkoreksi ATP.')->call('saveGlossary')->assertHasNoErrors();

    $search->call('$refresh')->assertSee('Definisi terkoreksi ATP.')->assertDontSee('Definisi awal ATP.');
});

test('glossary cache works across requests when cached PHP classes are disabled', function () {
    $path = sys_get_temp_dir().'/biolearn-glossary-'.bin2hex(random_bytes(8));
    config(['cache.default' => 'glossary-regression', 'cache.serializable_classes' => false,
        'cache.stores.glossary-regression' => ['driver' => 'file', 'path' => $path]]);
    Glossary::factory()->create(['term' => 'ATP', 'definition' => 'Energi untuk sel hidup.']);

    try {
        Livewire::test(GlossarySearch::class)->set('query', 'ATP')->assertSee('Energi untuk sel hidup.');
        Livewire::test(GlossarySearch::class)->set('query', 'ATP')->assertSee('Energi untuk sel hidup.');
    } finally {
        Cache::store('glossary-regression')->flush();
        @rmdir($path);
    }
});

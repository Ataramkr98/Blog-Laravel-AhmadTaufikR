<?php

namespace App\Livewire;

use App\Models\Glossary;
use App\Models\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class GlossarySearch extends Component
{
    public ?string $query = '';

    public ?string $level = 'all';

    public int $limit = 8;

    /**
     * @return Collection<int, Glossary>
     */
    public function glossaries(): Collection
    {
        $minutes = SystemSetting::current()->glossary_cache_minutes;
        $limit = min(50, max(1, $this->limit));
        $key = 'glossary_search:v2:'.Cache::get('glossary_revision', 'initial').':'.hash('sha256', json_encode([
            $this->query, $this->level, $limit, $minutes,
        ], JSON_THROW_ON_ERROR));

        $rows = Cache::remember($key, now()->addMinutes($minutes), fn () => Glossary::query()
            ->when($this->level !== 'all' && $this->level !== null, fn ($query) => $query->level($this->level))
            ->when($this->query !== '' && $this->query !== null, fn ($query) => $query->search($this->query))
            ->orderBy('level')
            ->orderBy('term')
            ->limit($limit)
            ->get()->map(fn (Glossary $glossary) => $glossary->getAttributes())->all());

        return Glossary::hydrate($rows);
    }

    public function render(): View
    {
        return view('livewire.glossary-search', [
            'glossaries' => $this->glossaries(),
        ]);
    }
}

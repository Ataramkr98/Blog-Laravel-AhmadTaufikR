<?php

namespace App\Livewire;

use App\Models\Glossary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AdminGlossary extends Component
{
    use WithPagination;

    public ?string $search = '';

    public ?string $level = 'all';

    public bool $modalOpen = false;

    public string $mode = 'create';

    public ?int $glossaryId = null;

    public string $term = '';

    public string $commonName = '';

    public string $definition = '';

    public string $glossaryLevel = 'sma';

    public string $glossaryCategory = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    /**
     * @return LengthAwarePaginator<int, Glossary>
     */
    public function glossaries(): LengthAwarePaginator
    {
        return Glossary::query()
            ->when($this->level !== 'all' && $this->level !== null, fn ($query) => $query->level($this->level))
            ->when($this->search !== '' && $this->search !== null, function ($query): void {
                $query->where(function ($query): void {
                    $query->whereLike('term', "%{$this->search}%")
                        ->orWhereLike('common_name', "%{$this->search}%")
                        ->orWhereLike('definition', "%{$this->search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->mode = 'create';
        $this->modalOpen = true;
    }

    public function editGlossary(int $id): void
    {
        $glossary = Glossary::query()->findOrFail($id);

        $this->glossaryId = $glossary->id;
        $this->mode = 'edit';
        $this->term = $glossary->term;
        $this->commonName = $glossary->common_name ?? '';
        $this->definition = $glossary->definition;
        $this->glossaryLevel = $glossary->level;
        $this->glossaryCategory = $glossary->category ?? '';
        $this->modalOpen = true;
    }

    public function saveGlossary(): void
    {
        $this->validate([
            'term' => 'required|string|max:255',
            'commonName' => 'nullable|string|max:255',
            'definition' => 'required|string',
            'glossaryLevel' => 'required|in:sma,pt',
            'glossaryCategory' => 'nullable|string|max:255',
        ]);

        $payload = [
            'term' => $this->term,
            'common_name' => $this->commonName,
            'definition' => $this->definition,
            'level' => $this->glossaryLevel,
            'category' => $this->glossaryCategory ?: null,
        ];

        if ($this->mode === 'edit' && $this->glossaryId) {
            Glossary::query()->findOrFail($this->glossaryId)->update($payload);
        } else {
            Glossary::query()->create($payload);
        }

        $this->resetForm();
        $this->modalOpen = false;
        $this->dispatch('notify', message: 'Istilah glosarium berhasil disimpan.');
    }

    public function deleteGlossary(int $id): void
    {
        Glossary::query()->findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Istilah glosarium berhasil dihapus.');
    }

    private function resetForm(): void
    {
        $this->glossaryId = null;
        $this->mode = 'create';
        $this->term = '';
        $this->commonName = '';
        $this->definition = '';
        $this->glossaryLevel = 'sma';
        $this->glossaryCategory = '';
    }

    public function render(): View
    {
        return view('admin.livewire.glossary-manager', [
            'glossaries' => $this->glossaries(),
        ]);
    }
}

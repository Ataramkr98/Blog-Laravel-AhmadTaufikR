<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ArticlesFilter extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public ?string $search = '';

    #[Url(except: 'all')]
    public ?string $level = 'all';

    #[Url(except: 'all')]
    public ?string $category = 'all';

    #[Url(except: 6)]
    public int $perPage = 6;

    public function mount(): void
    {
        $this->search ??= '';
        $this->level ??= 'all';
        $this->category ??= 'all';
        $this->validate();
    }

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'level' => ['nullable', Rule::in(['all', 'sma', 'pt'])],
            'category' => ['nullable', 'string', 'max:255'],
            'perPage' => ['required', 'integer', Rule::in([4, 6, 9])],
        ];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'level', 'category', 'perPage'], true)) {
            $this->validateOnly($property);
            if ($property === 'level') {
                $this->category = 'all';
            }
            $this->resetPage();
        }
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return Category::query()
            ->when($this->level !== 'all' && $this->level !== null, fn ($query) => $query->where('level', $this->level))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, Article>
     */
    public function articles()
    {
        return Article::query()
            ->published()
            ->with(['category', 'author', 'tags'])
            ->when($this->search !== '' && $this->search !== null, function ($query): void {
                $query->where(function ($query): void {
                    $query->whereLike('title', "%{$this->search}%")
                        ->orWhereLike('excerpt', "%{$this->search}%")
                        ->orWhereLike('content', "%{$this->search}%")
                        ->orWhereHas('tags', function ($query): void {
                            $query->whereLike('name', "%{$this->search}%");
                        })
                        ->orWhereHas('glossaries', function ($query): void {
                            $query->where(function ($query): void {
                                $query->whereLike('term', "%{$this->search}%")
                                    ->orWhereLike('common_name', "%{$this->search}%");
                            });
                        });
                });
            })
            ->when($this->level !== 'all' && $this->level !== null, fn ($query) => $query->where('level', $this->level))
            ->when($this->category !== 'all' && $this->category !== null, function ($query): void {
                $query->whereHas('category', fn ($query) => $query->where('slug', $this->category));
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($this->perPage)
            ->withQueryString();
    }

    public function render(): View
    {
        return view('livewire.articles-filter', [
            'articles' => $this->articles(),
            'categories' => $this->categories(),
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AdminUserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function changeRole(int $userId, string $role): void
    {
        $this->ensureAdmin();
        $validated = validator(
            ['role' => $role],
            ['role' => ['required', Rule::in(['admin', 'penulis', 'pembaca'])]],
        )->validate();

        $user = User::query()->findOrFail($userId);

        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            $this->addError('role', 'Admin tidak dapat menurunkan role akun yang sedang digunakan.');

            return;
        }

        if (
            $user->isAdmin()
            && $validated['role'] !== 'admin'
            && User::query()->where('role', 'admin')->count() <= 1
        ) {
            $this->addError('role', 'Sistem harus memiliki minimal satu Admin.');

            return;
        }

        $user->update(['role' => $validated['role']]);
        $this->dispatch('notify', message: 'Hak akses pengguna berhasil diperbarui.');
    }

    public function render(): View
    {
        $this->ensureAdmin();

        return view('admin.livewire.user-manager', [
            'users' => User::query()
                ->when($this->search !== '', function ($query): void {
                    $query->where(function ($query): void {
                        $query->whereLike('name', "%{$this->search}%")
                            ->orWhereLike('email', "%{$this->search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(12),
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}

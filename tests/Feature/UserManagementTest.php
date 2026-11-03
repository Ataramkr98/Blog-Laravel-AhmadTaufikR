<?php

use App\Livewire\AdminUserManager;
use App\Models\User;
use Livewire\Livewire;

test('only administrators can access user management', function () {
    $writer = User::factory()->create(['role' => 'penulis']);

    $this->actingAs($writer)
        ->get(route('admin.users'))
        ->assertForbidden();
});

test('administrator changes a users role', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $reader = User::factory()->create(['role' => 'pembaca']);

    Livewire::actingAs($admin)
        ->test(AdminUserManager::class)
        ->call('changeRole', $reader->id, 'penulis');

    expect($reader->fresh()->role)->toBe('penulis');
});

test('the final administrator cannot be demoted', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(AdminUserManager::class)
        ->call('changeRole', $admin->id, 'penulis')
        ->assertHasErrors(['role']);

    expect($admin->fresh()->role)->toBe('admin');
});

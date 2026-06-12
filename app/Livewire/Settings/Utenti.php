<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Utenti extends Component
{
    // Crea
    public string $newName     = '';
    public string $newEmail    = '';
    public string $newPassword = '';

    // Edit inline (nome + email)
    public ?int   $editingId   = null;
    public string $editName    = '';
    public string $editEmail   = '';

    // Cambio password di un altro utente
    public ?int   $changingPasswordId = null;
    public string $newPasswordForUser = '';

    // Delete
    public ?int $confirmDeleteId = null;

    public function create(): void
    {
        $this->validate([
            'newName'     => 'required|string|max:255',
            'newEmail'    => 'required|email|unique:users,email',
            'newPassword' => 'required|string|min:8',
        ]);

        User::create([
            'name'     => trim($this->newName),
            'email'    => trim($this->newEmail),
            'password' => Hash::make($this->newPassword),
        ]);

        $this->newName     = '';
        $this->newEmail    = '';
        $this->newPassword = '';
    }

    public function startEdit(int $id): void
    {
        $user            = User::findOrFail($id);
        $this->editingId = $id;
        $this->editName  = $user->name;
        $this->editEmail = $user->email;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editName'  => 'required|string|max:255',
            'editEmail' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
        ]);

        User::where('id', $this->editingId)->update([
            'name'  => trim($this->editName),
            'email' => trim($this->editEmail),
        ]);

        $this->editingId = null;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function startChangePassword(int $id): void
    {
        $this->changingPasswordId = $id;
        $this->newPasswordForUser = '';
    }

    public function savePassword(): void
    {
        $this->validate([
            'newPasswordForUser' => 'required|string|min:8',
        ]);

        User::where('id', $this->changingPasswordId)->update([
            'password' => Hash::make($this->newPasswordForUser),
        ]);

        $this->changingPasswordId = null;
        $this->newPasswordForUser = '';
    }

    public function cancelChangePassword(): void
    {
        $this->changingPasswordId = null;
        $this->newPasswordForUser = '';
    }

    public function delete(int $id): void
    {
        if ($id === Auth::id()) {
            return; // non si può eliminare se stessi
        }

        User::findOrFail($id)->delete();
        $this->confirmDeleteId = null;
    }

    public function render()
    {
        $users = User::orderBy('name')->get();

        return view('livewire.settings.utenti', compact('users'))
            ->layout('layouts.app', ['title' => 'Utenti']);
    }
}

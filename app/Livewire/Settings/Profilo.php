<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profilo extends Component
{
    public string $name  = '';
    public string $email = '';

    public bool $saved = false;

    public function mount(): void
    {
        $user        = Auth::user();
        $this->name  = $user->name;
        $this->email = $user->email;
    }

    public function save(): void
    {
        $user = Auth::user();

        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name'  => trim($this->name),
            'email' => trim($this->email),
        ]);

        $this->saved = true;
        $this->dispatch('toast', message: 'Profilo aggiornato.');
    }

    public function render()
    {
        return view('livewire.settings.profilo')
            ->layout('layouts.app', ['title' => 'Profilo']);
    }
}

<div class="flex flex-col sm:flex-row gap-6 sm:gap-8 max-w-4xl mx-auto">
    <x-settings-nav />

    <div class="flex-1">
        <h1 class="text-base font-semibold text-ink mb-6">Utenti</h1>

        {{-- Lista --}}
        <div class="bg-white rounded-xl border border-paper-dark divide-y divide-paper-dark mb-4">

            @foreach($users as $user)
            @php $isSelf = $user->id === auth()->id(); @endphp
            <div wire:key="user-{{ $user->id }}" class="px-4 py-3">

                @if($editingId === $user->id)
                {{-- Edit nome + email --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <input type="text" wire:model="editName"
                           class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia w-40">
                    @error('editName')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                    <input type="email" wire:model="editEmail"
                           class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia flex-1 min-w-40">
                    @error('editEmail')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                    <button wire:click="saveEdit"
                            class="text-xs px-3 py-1.5 bg-salvia text-white rounded-lg
                                   hover:bg-salvia-dark transition-colors">
                        Salva
                    </button>
                    <button wire:click="cancelEdit"
                            class="text-xs text-ink/40 hover:text-ink transition-colors">
                        Annulla
                    </button>
                </div>

                @elseif($changingPasswordId === $user->id)
                {{-- Cambio password --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="text-sm text-ink/60 w-32 shrink-0">{{ $user->name }}</span>
                    <input type="password" wire:model="newPasswordForUser"
                           placeholder="Nuova password (min. 8)..."
                           class="text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia flex-1 min-w-40">
                    @error('newPasswordForUser')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                    <button wire:click="savePassword"
                            class="text-xs px-3 py-1.5 bg-salvia text-white rounded-lg
                                   hover:bg-salvia-dark transition-colors">
                        Salva
                    </button>
                    <button wire:click="cancelChangePassword"
                            class="text-xs text-ink/40 hover:text-ink transition-colors">
                        Annulla
                    </button>
                </div>

                @elseif($confirmDeleteId === $user->id)
                {{-- Conferma delete --}}
                <div class="flex items-center gap-3">
                    <span class="text-sm text-ink flex-1">Eliminare <strong>{{ $user->name }}</strong>?</span>
                    <button wire:click="delete({{ $user->id }})"
                            class="text-xs text-terracotta font-medium hover:opacity-80">Elimina</button>
                    <button wire:click="$set('confirmDeleteId', null)"
                            class="text-xs text-ink/40 hover:text-ink">Annulla</button>
                </div>

                @else
                {{-- Vista normale --}}
                <div class="flex items-center gap-3 group">
                    <span class="w-7 h-7 rounded-full bg-salvia flex items-center justify-center
                                 text-xs font-semibold text-white uppercase shrink-0">
                        {{ substr($user->name, 0, 2) }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-ink font-medium truncate">
                            {{ $user->name }}
                            @if($isSelf)<span class="text-[10px] text-ink/30 font-normal ml-1">(tu)</span>@endif
                        </p>
                        <p class="text-xs text-ink/40 truncate">{{ $user->email }}</p>
                    </div>
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button title="Modifica" wire:click="startEdit({{ $user->id }})"
                                class="text-xs text-ink/40 hover:text-salvia transition-colors">
                            modifica
                        </button>
                        @if(!$isSelf)
                        <button wire:click="startChangePassword({{ $user->id }})"
                                class="text-xs text-ink/40 hover:text-salvia transition-colors">
                            password
                        </button>
                        <button title="Elimina" wire:click="$set('confirmDeleteId', {{ $user->id }})"
                                class="text-xs text-ink/30 hover:text-terracotta transition-colors">
                            ✕
                        </button>
                        @endif
                    </div>
                </div>
                @endif

            </div>
            @endforeach

        </div>

        {{-- Crea nuovo utente --}}
        <div class="bg-white rounded-xl border border-paper-dark p-4">
            <p class="text-xs font-medium text-ink/50 mb-3">Nuovo utente</p>
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div>
                    <label class="block text-[10px] text-ink/40 mb-1">Nome</label>
                    <input type="text" wire:model="newName" placeholder="Nome..."
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia">
                    @error('newName')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[10px] text-ink/40 mb-1">Email</label>
                    <input type="email" wire:model="newEmail" placeholder="email@esempio.it"
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia">
                    @error('newEmail')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[10px] text-ink/40 mb-1">Password</label>
                    <input type="password" wire:model="newPassword" placeholder="Min. 8 caratteri"
                           class="w-full text-sm border border-paper-dark rounded-lg px-3 py-1.5
                                  focus:outline-none focus:border-salvia">
                    @error('newPassword')<p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>@enderror
                </div>
            </div>
            <button wire:click="create"
                    class="px-4 py-1.5 bg-salvia text-white rounded-lg text-sm
                           hover:bg-salvia-dark transition-colors">
                + Aggiungi utente
            </button>
        </div>

    </div>
</div>

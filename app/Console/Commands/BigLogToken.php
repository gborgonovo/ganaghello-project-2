<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BigLogToken extends Command
{
    protected $signature = 'biglog:token
                            {nome : etichetta del token (es. portatile, telefono)}
                            {email : email dell\'utente a cui appartiene}
                            {--revoca : cancella i token con questo nome invece di crearne uno}';

    protected $description = 'Emette o revoca un token per il server MCP';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (!$user) {
            $this->error('Utente non trovato.');
            return self::FAILURE;
        }

        $nome = $this->argument('nome');

        if ($this->option('revoca')) {
            $n = $user->tokens()->where('name', $nome)->delete();
            $this->info("Token revocati: {$n}");
            return self::SUCCESS;
        }

        $token = $user->createToken($nome, ['mcp:leggi'])->plainTextToken;

        $this->info("Token \"{$nome}\" per {$user->name}, capacità: mcp:leggi.");
        $this->line('Si mostra una volta sola, conservalo ora:');
        $this->line($token);

        return self::SUCCESS;
    }
}

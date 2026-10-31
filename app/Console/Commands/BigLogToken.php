<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BigLogToken extends Command
{
    protected $signature = 'biglog:token
                            {nome : etichetta del token (es. portatile, telefono)}
                            {email : email dell\'utente a cui appartiene}
                            {--scrittura : il token nuovo può anche scrivere}
                            {--abilita-scrittura : dà la scrittura al token esistente con questo nome, senza cambiarlo}
                            {--togli-scrittura : toglie la scrittura al token esistente con questo nome}
                            {--revoca : cancella i token con questo nome invece di crearne uno}';

    protected $description = 'Emette, modifica o revoca un token per il server MCP';

    private const LETTURA   = 'mcp:leggi';
    private const SCRITTURA = 'mcp:scrivere';

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

        if ($this->option('abilita-scrittura') || $this->option('togli-scrittura')) {
            $tokens = $user->tokens()->where('name', $nome)->get();
            if ($tokens->isEmpty()) {
                $this->error("Nessun token \"{$nome}\" per {$user->name}.");
                return self::FAILURE;
            }
            $capacita = $this->option('abilita-scrittura') ? [self::LETTURA, self::SCRITTURA] : [self::LETTURA];
            $tokens->each(fn ($t) => $t->forceFill(['abilities' => $capacita])->save());
            $this->info("Token \"{$nome}\": capacità " . implode(', ', $capacita) . " ({$tokens->count()} aggiornati).");
            return self::SUCCESS;
        }

        $capacita = $this->option('scrittura') ? [self::LETTURA, self::SCRITTURA] : [self::LETTURA];
        $token    = $user->createToken($nome, $capacita)->plainTextToken;

        $this->info("Token \"{$nome}\" per {$user->name}, capacità: " . implode(', ', $capacita) . '.');
        $this->line('Si mostra una volta sola, conservalo ora:');
        $this->line($token);

        return self::SUCCESS;
    }
}

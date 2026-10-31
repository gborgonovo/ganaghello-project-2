<?php

namespace App\Models;

use App\Support\Porta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/** Una scrittura sul dominio: chi, da quale porta, cosa, su quale elemento, con quali valori prima e dopo. */
class RegistroScrittura extends Model
{
    public const UPDATED_AT = null;
    private const TRONCA    = 500;

    protected $table    = 'registro_scritture';
    protected $fillable = ['user_id', 'token_id', 'porta', 'azione', 'entita', 'entita_id', 'riassunto', 'modifiche'];

    protected function casts(): array
    {
        return ['modifiche' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** @param array<string, array{0:mixed,1:mixed}> $modifiche campo => [prima, dopo] */
    public static function annota(?User $utente, string $azione, string $entita, ?int $entitaId, string $riassunto, array $modifiche = []): self
    {
        $token = $utente?->currentAccessToken();

        return static::create([
            'user_id'   => $utente?->id,
            'token_id'  => $token instanceof PersonalAccessToken ? $token->id : null,
            'porta'     => app(Porta::class)->corrente(),
            'azione'    => $azione,
            'entita'    => $entita,
            'entita_id' => $entitaId,
            'riassunto' => Str::limit($riassunto, 250),
            'modifiche' => $modifiche ? array_map(fn ($c) => array_map([static::class, 'breve'], $c), $modifiche) : null,
        ]);
    }

    /** Le differenze tra gli attributi originali e quelli salvati di un model, nella forma di annota(). */
    public static function differenze(Model $m, array $originali): array
    {
        $diff = [];
        foreach ($m->getChanges() as $campo => $dopo) {
            if (in_array($campo, ['updated_at', 'created_at', 'mnemosyne_node_name'], true)) {
                continue;
            }
            $diff[$campo] = [$originali[$campo] ?? null, $dopo];
        }

        return $diff;
    }

    private static function breve(mixed $valore): mixed
    {
        return is_string($valore) ? Str::limit($valore, self::TRONCA) : $valore;
    }
}

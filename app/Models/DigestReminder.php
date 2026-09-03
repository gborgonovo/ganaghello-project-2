<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Traccia di un promemoria del digest gia' recapitato, per non ripeterlo.
 * offset: giorni rispetto alla scadenza (negativo = prima, 0 = giorno stesso,
 * positivo = ritardo). Vedi SendDailyDigest.
 */
class DigestReminder extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'task_id', 'offset', 'sent_on'];

    protected function casts(): array
    {
        return [
            'offset'  => 'integer',
            'sent_on' => 'date',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function task() { return $this->belongsTo(Task::class); }
}

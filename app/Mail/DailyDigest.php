<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array  $groups   Gruppi di scadenze in ordine di urgenza:
     *                           [['label'=>'Oggi', 'late'=>false, 'tasks'=>[['id','title','area'], ...]], ...]
     * @param  array  $dormant  Nodi dormienti: [['label'=>'...', 'url'=>'...', 'days_inactive'=>47], ...]
     * @param  string $userName
     */
    public function __construct(
        public readonly array  $groups,
        public readonly array  $dormant,
        public readonly string $userName,
    ) {}

    public function envelope(): Envelope
    {
        $parts = [];
        $taskCount = collect($this->groups)->sum(fn ($g) => count($g['tasks']));
        if ($taskCount > 0) {
            $parts[] = $taskCount === 1 ? '1 scadenza' : "{$taskCount} scadenze";
        }
        if (count($this->dormant) > 0) {
            $n = count($this->dormant);
            $parts[] = $n === 1 ? '1 filo si raffredda' : "{$n} fili si raffreddano";
        }
        $subject = 'BiG-Log · ' . implode(', ', $parts);

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.digest',
            text: 'mail.digest-text',
        );
    }
}

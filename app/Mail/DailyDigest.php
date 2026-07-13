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
     * @param  array  $sections   Raggruppamento scadenze: ['oggi'=>[...], 'domani'=>[...], '3giorni'=>[...], '7giorni'=>[...], '30giorni'=>[...]]
     * @param  array  $dormant    Nodi dormienti: [['label'=>'...', 'days_inactive'=>47, 'task_id'=>null], ...]
     * @param  string $userName
     */
    public function __construct(
        public readonly array  $sections,
        public readonly array  $dormant,
        public readonly string $userName,
    ) {}

    public function envelope(): Envelope
    {
        $parts = [];
        $taskCount = collect($this->sections)->flatten(1)->count();
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

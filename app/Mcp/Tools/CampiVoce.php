<?php

namespace App\Mcp\Tools;

use App\Models\Entry;
use Illuminate\Contracts\JsonSchema\JsonSchema;

trait CampiVoce
{
    protected function mappaVoce(): array
    {
        return ['testo' => 'content', 'titolo' => 'title', 'data' => 'entry_date', 'ora' => 'entry_time', 'area_id' => 'area_id', 'tipo' => 'kind', 'task_ids' => 'task_ids'];
    }

    protected function schemaVoce(JsonSchema $schema): array
    {
        return [
            'testo'    => $schema->string(),
            'titolo'   => $schema->string()->description('Breve, preso dalle parole dell\'utente.'),
            'data'     => $schema->string()->description('AAAA-MM-GG, solo se non è oggi.'),
            'ora'      => $schema->string()->description('HH:MM, solo se l\'utente la dice.'),
            'area_id'  => $schema->integer()->description('Id dell\'area (da `riferimenti`).'),
            'tipo'     => $schema->string()->enum(['auto', ...Entry::KINDS])->description('Di norma non serve: lo decide l\'app.'),
            'task_ids' => $schema->array()->items($schema->integer())->description('Task di cui la voce racconta un momento.'),
        ];
    }

    /** Per una polaroid ancora senza foto, il link all'editor dove caricarla. */
    protected function linkFoto(Entry $voce): array
    {
        return $voce->kind === 'polaroid' && !$voce->attachments()->exists()
            ? ['url_aggiungi_foto' => route('diario.edit', $voce->id)]
            : [];
    }
}

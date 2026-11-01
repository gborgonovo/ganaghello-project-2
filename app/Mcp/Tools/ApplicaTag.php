<?php

namespace App\Mcp\Tools;

use App\Actions\TagActions;
use App\Exceptions\RegolaDominio;
use App\Models\Entry;
use App\Models\Inspiration;
use App\Models\Note;
use App\Models\Post;
use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('applica_tag')]
#[Description('Aggiunge o toglie tag (per nome, sempre minuscoli) a un task, una voce di diario, una nota, un post o un\'ispirazione. I tag che non esistono vengono creati. Un post pubblicato non si tocca da qui.')]
#[IsIdempotent]
class ApplicaTag extends StrumentoScrittura
{
    private const MODELLI = ['task' => Task::class, 'voce' => Entry::class, 'nota' => Note::class, 'post' => Post::class, 'ispirazione' => Inspiration::class];

    protected function esegui(Request $request): Response
    {
        $d = $request->validate([
            'entita'   => 'required|in:' . implode(',', array_keys(self::MODELLI)),
            'id'       => 'required|integer',
            'aggiungi' => 'nullable|array',
            'aggiungi.*' => 'string|max:100',
            'togli'    => 'nullable|array',
            'togli.*'  => 'string',
        ]);
        $elemento = self::MODELLI[$d['entita']]::find($d['id']) ?? throw new RegolaDominio("Nessun elemento di tipo {$d['entita']} con id {$d['id']}.");
        if ($elemento instanceof Post && $elemento->isPublic()) {
            throw new RegolaDominio('Il post è pubblicato: modificalo dall\'app.');
        }

        app(TagActions::class)->applica($request->user(), $elemento, $d['aggiungi'] ?? [], $d['togli'] ?? []);

        return $this->risposta($d['entita'], $elemento->id, 'tag aggiornati');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'entita'   => $schema->string()->enum(array_keys(self::MODELLI))->required(),
            'id'       => $schema->integer()->required(),
            'aggiungi' => $schema->array()->items($schema->string())->description('Nomi dei tag da aggiungere.'),
            'togli'    => $schema->array()->items($schema->string())->description('Nomi dei tag da togliere.'),
        ];
    }
}

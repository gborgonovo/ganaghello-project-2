<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;

/** Parametri dei task comuni a crea_task e modifica_task: nome italiano del tool => campo del model. */
trait CampiTask
{
    protected function mappaTask(): array
    {
        return [
            'titolo'      => 'title',
            'descrizione' => 'description',
            'area_id'     => 'area_id',
            'padre_id'    => 'parent_task_id',
            'priorita'    => 'priority',
            'esecutore'   => 'executor',
            'scadenza'    => 'due_date',
            'inizio'      => 'start_date',
            'stima_ore'   => 'work_estimate',
            'costo_min'   => 'cost_min',
            'costo_max'   => 'cost_max',
            'base_costo'  => 'cost_basis',
            'costo_reale' => 'cost_real',
        ];
    }

    protected function schemaTask(JsonSchema $schema): array
    {
        return [
            'titolo'      => $schema->string(),
            'descrizione' => $schema->string(),
            'area_id'     => $schema->integer()->description('Id dell\'area (da `riferimenti`).'),
            'padre_id'    => $schema->integer()->description('Id del task padre, per un sotto-task.'),
            'priorita'    => $schema->string()->enum(['bassa', 'media', 'alta']),
            'esecutore'   => $schema->string()->enum(['una_persona', 'team', 'professionista', 'impresa']),
            'scadenza'    => $schema->string()->description('AAAA-MM-GG. Obbligatoria per gli stage todo, doing, in_attesa.'),
            'inizio'      => $schema->string()->description('AAAA-MM-GG.'),
            'stima_ore'   => $schema->number(),
            'costo_min'   => $schema->number()->description('Euro.'),
            'costo_max'   => $schema->number()->description('Euro.'),
            'base_costo'  => $schema->string()->enum(['stima', 'preventivo', 'reale']),
            'costo_reale' => $schema->number()->description('Euro.'),
        ];
    }
}

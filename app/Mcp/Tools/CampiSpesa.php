<?php

namespace App\Mcp\Tools;

use App\Actions\SpesaActions;
use Illuminate\Contracts\JsonSchema\JsonSchema;

trait CampiSpesa
{
    protected function mappaSpesa(): array
    {
        return ['descrizione' => 'description', 'importo' => 'amount', 'data' => 'expense_date', 'categoria' => 'category', 'fornitore' => 'supplier', 'note' => 'notes'];
    }

    protected function schemaSpesa(JsonSchema $schema): array
    {
        return [
            'descrizione' => $schema->string(),
            'importo'     => $schema->number()->description('Euro, almeno 0,01.'),
            'data'        => $schema->string()->description('AAAA-MM-GG.'),
            'categoria'   => $schema->string()->enum(SpesaActions::CATEGORIE),
            'fornitore'   => $schema->string(),
            'note'        => $schema->string(),
        ];
    }
}

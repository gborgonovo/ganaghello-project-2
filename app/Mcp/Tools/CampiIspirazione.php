<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;

trait CampiIspirazione
{
    protected function mappaIspirazione(): array
    {
        return ['titolo' => 'title', 'descrizione' => 'description', 'link' => 'url', 'area_id' => 'area_id'];
    }

    protected function schemaIspirazione(JsonSchema $schema): array
    {
        return [
            'titolo'      => $schema->string(),
            'descrizione' => $schema->string(),
            'link'        => $schema->string()->description('Indirizzo web completo.'),
            'area_id'     => $schema->integer(),
        ];
    }
}

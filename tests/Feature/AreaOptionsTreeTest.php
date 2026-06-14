<?php

namespace Tests\Feature;

use App\Models\Area;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blocco 13: le opzioni delle select aree sono ordinate ad albero (ogni radice
 * seguita dalle figlie, indentate).
 */
class AreaOptionsTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordine_e_indentazione(): void
    {
        $abitazione = Area::create(['name' => 'Abitazione', 'sequence' => 1]);
        Area::create(['name' => 'Cucina', 'parent_area_id' => $abitazione->id, 'sequence' => 1]);
        Area::create(['name' => 'Stalla', 'sequence' => 2]);

        $labels = Area::optionsTree()->pluck('label')->all();

        $this->assertSame(['Abitazione', '— Cucina', 'Stalla'], $labels);
    }
}

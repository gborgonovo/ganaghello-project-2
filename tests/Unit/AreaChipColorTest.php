<?php

namespace Tests\Unit;

use App\Models\Area;
use Tests\TestCase;

/**
 * Blocco 5: il chip dell'area deriva un testo leggibile dallo sfondo quando
 * text_color non e' impostato, e rispetta text_color quando c'e'.
 */
class AreaChipColorTest extends TestCase
{
    public function test_sfondo_scuro_da_testo_bianco(): void
    {
        $area = new Area(['color' => '#2C322A']); // ink, scuro
        $this->assertSame('#FFFFFF', $area->chip_text_color);
    }

    public function test_sfondo_chiaro_da_testo_scuro(): void
    {
        $area = new Area(['color' => '#EFEDE3']); // paper, chiaro
        $this->assertSame('#2C322A', $area->chip_text_color);
    }

    public function test_text_color_esplicito_vince(): void
    {
        $area = new Area(['color' => '#2C322A', 'text_color' => '#FF0000']);
        $this->assertSame('#FF0000', $area->chip_text_color);
    }

    public function test_sfondo_fallback_quando_manca_color(): void
    {
        $area = new Area();
        $this->assertSame('#E0DDD2', $area->chip_bg_color);
        $this->assertSame('#2C322A', $area->chip_text_color);
    }
}

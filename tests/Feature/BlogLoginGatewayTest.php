<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blocco 15: dalla vetrina pubblica del blog si raggiunge il login (varco
 * discreto nel footer), e il cruscotto se già autenticati.
 */
class BlogLoginGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_ospite_trova_il_varco_al_login(): void
    {
        $resp = $this->get('/');
        $resp->assertOk();
        $resp->assertSee(route('login'), false);
    }

    public function test_autenticato_trova_il_varco_al_cruscotto(): void
    {
        $this->actingAs(User::factory()->create());
        $resp = $this->get('/');
        $resp->assertOk();
        $resp->assertSee(route('cruscotto'), false);
    }
}

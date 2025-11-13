<?php

namespace Tests\Feature;

use Tests\TestCase;

class EstadosTest extends TestCase
{
    public function test_index_returns_ok()
    {
        $this->withoutMiddleware(\App\Http\Middleware\CheckBeeartToken::class);

        $response = $this->getJson('/api/estados');

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    public function test_show_nonexistent_returns_404()
    {
        $this->withoutMiddleware(\App\Http\Middleware\CheckBeeartToken::class);

        $response = $this->getJson('/api/estados/999999');

        $response->assertStatus(404);
    }
}

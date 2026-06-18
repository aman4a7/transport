<?php

test('health endpoint returns success', function (): void {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'OK',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['app', 'version', 'timestamp'],
        ]);
});

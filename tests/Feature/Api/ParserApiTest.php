<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('requires authentication to list parsers', function () {
    getJson('/api/parsers')
        ->assertStatus(401);
});

it('can list all available parsers', function () {
    actingAs($this->user)
        ->getJson('/api/parsers')
        ->assertStatus(200)
        ->assertJsonStructure([
            'parsers' => [
                '*' => [
                    'name',
                    'supported_types',
                ],
            ],
        ]);
});

it('can get parser details', function () {
    actingAs($this->user)
        ->getJson('/api/parsers/feeds')
        ->assertStatus(200)
        ->assertJsonStructure([
            'name',
            'supported_types',
            'config',
        ]);
});

it('returns 404 for non-existent parser', function () {
    actingAs($this->user)
        ->getJson('/api/parsers/nonexistent')
        ->assertStatus(404)
        ->assertJsonStructure(['error']);
});

it('requires authentication to execute parsing', function () {
    postJson('/api/parsers/parse', [
        'parser' => 'feeds',
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
    ])
        ->assertStatus(401);
});

it('validates required fields for parse request', function () {
    actingAs($this->user)
        ->postJson('/api/parsers/parse', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['parser', 'source', 'type']);
});

it('can execute parsing operation', function () {
    actingAs($this->user)
        ->postJson('/api/parsers/parse', [
            'parser' => 'feeds',
            'source' => 'https://example.com/feed.xml',
            'type' => 'rss',
        ])
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'items',
            'metadata',
        ]);
});

it('can get supported types for a parser', function () {
    actingAs($this->user)
        ->getJson('/api/parsers/feeds/types')
        ->assertStatus(200)
        ->assertJsonStructure([
            'types',
        ])
        ->assertJson([
            'types' => expect()->toBeArray(),
        ]);
});

it('returns 404 when getting types for non-existent parser', function () {
    actingAs($this->user)
        ->getJson('/api/parsers/nonexistent/types')
        ->assertStatus(404);
});

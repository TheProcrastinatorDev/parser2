<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

class ParserControllerTest extends TestCase
{
    /** @test */
    public function it_executes_a_parse_request(): void
    {
        $response = $this->postJson('/api/parsers/parse', [
            'source' => 'https://example.com/test',
            'type' => 'single_page',
            'options' => [
                'selector' => '.content',
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'metadata',
                ],
            ]);
    }

    /** @test */
    public function it_validates_required_fields_for_parse_request(): void
    {
        $response = $this->postJson('/api/parsers/parse', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['source', 'type']);
    }

    /** @test */
    public function it_validates_parser_type_exists(): void
    {
        $response = $this->postJson('/api/parsers/parse', [
            'source' => 'https://example.com',
            'type' => 'invalid_parser',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_returns_error_for_failed_parse(): void
    {
        $response = $this->postJson('/api/parsers/parse', [
            'source' => 'https://nonexistent-domain-12345.com',
            'type' => 'single_page',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'error',
            ]);
    }

    /** @test */
    public function it_lists_all_available_parsers(): void
    {
        $response = $this->getJson('/api/parsers');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'name',
                        'description',
                    ],
                ],
            ]);

        // Verify all 8 parsers are listed
        $data = $response->json('data');
        $this->assertCount(8, $data);

        $parserNames = array_column($data, 'name');
        $this->assertContains('feeds', $parserNames);
        $this->assertContains('reddit', $parserNames);
        $this->assertContains('single_page', $parserNames);
        $this->assertContains('telegram', $parserNames);
        $this->assertContains('medium', $parserNames);
        $this->assertContains('bing', $parserNames);
        $this->assertContains('multi', $parserNames);
        $this->assertContains('craigslist', $parserNames);
    }

    /** @test */
    public function it_gets_parser_details_by_name(): void
    {
        $response = $this->getJson('/api/parsers/feeds');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'name',
                    'description',
                    'config',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'feeds',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_unknown_parser(): void
    {
        $response = $this->getJson('/api/parsers/unknown');

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_executes_batch_parse_requests(): void
    {
        $response = $this->postJson('/api/parsers/batch', [
            'requests' => [
                [
                    'source' => 'https://example.com/page1',
                    'type' => 'single_page',
                ],
                [
                    'source' => 'https://example.com/page2',
                    'type' => 'single_page',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'results' => [
                        '*' => [
                            'success',
                            'data',
                        ],
                    ],
                    'summary' => [
                        'total',
                        'successful',
                        'failed',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data['results']);
        $this->assertEquals(2, $data['summary']['total']);
    }

    /** @test */
    public function it_validates_batch_requests_array(): void
    {
        $response = $this->postJson('/api/parsers/batch', [
            'requests' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['requests']);
    }

    /** @test */
    public function it_validates_individual_batch_request_fields(): void
    {
        $response = $this->postJson('/api/parsers/batch', [
            'requests' => [
                [
                    // Missing source and type
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'requests.0.source',
                'requests.0.type',
            ]);
    }

    /** @test */
    public function it_limits_batch_requests_to_maximum(): void
    {
        $requests = [];
        for ($i = 0; $i < 101; $i++) {
            $requests[] = [
                'source' => "https://example.com/page{$i}",
                'type' => 'single_page',
            ];
        }

        $response = $this->postJson('/api/parsers/batch', [
            'requests' => $requests,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['requests']);
    }

    /** @test */
    public function it_accepts_parse_request_with_all_optional_fields(): void
    {
        $response = $this->postJson('/api/parsers/parse', [
            'source' => 'https://example.com',
            'type' => 'single_page',
            'keywords' => ['test', 'keyword'],
            'options' => [
                'selector' => '.content',
                'clean_html' => true,
            ],
            'limit' => 10,
            'offset' => 5,
            'filters' => [
                'date_from' => '2025-01-01',
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function it_returns_pagination_metadata_in_parse_response(): void
    {
        $response = $this->postJson('/api/parsers/parse', [
            'source' => 'https://reddit.com/r/programming.json',
            'type' => 'reddit',
            'limit' => 5,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'metadata' => [
                        'parser',
                        'total',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_handles_rate_limiting(): void
    {
        // Configure low rate limit for testing
        config(['parser.parsers.single_page.rate_limit_per_minute' => 2]);

        // Make requests up to the limit
        $this->postJson('/api/parsers/parse', [
            'source' => 'https://example.com/1',
            'type' => 'single_page',
        ])->assertOk();

        $this->postJson('/api/parsers/parse', [
            'source' => 'https://example.com/2',
            'type' => 'single_page',
        ])->assertOk();

        // This should be rate limited
        $response = $this->postJson('/api/parsers/parse', [
            'source' => 'https://example.com/3',
            'type' => 'single_page',
        ]);

        $response->assertStatus(429)
            ->assertJson([
                'success' => false,
            ]);
    }
}

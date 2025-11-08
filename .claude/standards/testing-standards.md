# Testing Standards (Pest PHP)

## Testing Philosophy

This project follows **Test-Driven Development (TDD)**:

1. **Write failing test first**
2. **Implement minimum code to pass**
3. **Refactor while keeping tests green**

### Coverage Requirements

- **Minimum**: 80% code coverage
- **Focus**: Services, DTOs, critical business logic
- **Priority**: Feature tests > Unit tests > Integration tests

## Test Organization

```
tests/
├── Feature/                # Feature/Integration tests
│   ├── Services/
│   │   ├── ParsingServiceTest.php
│   │   └── DataNormalizationServiceTest.php
│   ├── API/
│   │   ├── CampaignAPITest.php
│   │   └── SourceAPITest.php
│   └── Http/
│       └── Controllers/
├── Unit/                   # Unit tests
│   ├── DTOs/
│   │   ├── ParsingConfigDTOTest.php
│   │   └── ParsingResultDTOTest.php
│   └── Services/
│       └── CategorizationServiceTest.php
└── Pest.php               # Pest configuration
```

## Test Naming Conventions

### File Names

- Test files MUST end with `Test.php`
- Mirror the structure of `app/` directory
- Example: `app/Services/ParsingService.php` → `tests/Feature/Services/ParsingServiceTest.php`

### Test Names

Use descriptive, readable test names with Pest's `it()` syntax:

```php
// ✅ Good - Descriptive, clear intent
it('can execute parsing with valid configuration')
it('throws exception when parser type is invalid')
it('normalizes data to standard schema')

// ❌ Bad - Not descriptive
it('works')
it('test parsing')
it('should execute')
```

## Pest Syntax

### Basic Test Structure

```php
<?php

use App\Services\ParsingService;
use App\DTOs\ParsingConfigDTO;
use App\DTOs\ParsingResultDTO;

it('can execute parsing with DTO configuration', function () {
    // Arrange
    $config = new ParsingConfigDTO(
        parserType: 'feeds',
        configuration: ['url' => 'https://example.com/feed'],
    );

    $service = app(ParsingService::class);

    // Act
    $result = $service->executeParsing($config);

    // Assert
    expect($result)->toBeInstanceOf(ParsingResultDTO::class);
    expect($result->status)->toBe('completed');
    expect($result->content)->not->toBeEmpty();
});
```

### Expectations

```php
// Type checks
expect($result)->toBeInstanceOf(ParsingResultDTO::class);
expect($value)->toBeString();
expect($count)->toBeInt();
expect($flag)->toBeBool();
expect($array)->toBeArray();

// Value checks
expect($result->status)->toBe('completed');
expect($count)->toEqual(5);
expect($result)->not->toBeNull();
expect($array)->toBeEmpty();
expect($array)->not->toBeEmpty();

// Numeric comparisons
expect($count)->toBeGreaterThan(0);
expect($count)->toBeLessThan(100);
expect($count)->toBeGreaterThanOrEqual(1);

// String checks
expect($string)->toContain('substring');
expect($string)->toStartWith('prefix');
expect($string)->toEndWith('suffix');
expect($string)->toMatch('/regex/');

// Array checks
expect($array)->toHaveKey('key');
expect($array)->toHaveCount(3);
expect($array)->toContain('value');

// Object checks
expect($object)->toHaveProperty('propertyName');
expect($object)->toHaveMethod('methodName');

// Exceptions
expect(fn () => $service->invalid())->toThrow(Exception::class);
expect(fn () => $service->invalid())->toThrow(Exception::class, 'error message');
```

## Unit Tests

Test individual units in isolation (DTOs, single methods, helpers).

### DTO Testing

```php
<?php

use App\DTOs\ParsingConfigDTO;

describe('ParsingConfigDTO', function () {
    it('can be created from array', function () {
        $data = [
            'parser_type' => 'feeds',
            'configuration' => ['url' => 'https://example.com'],
            'schedule' => '0 * * * *',
        ];

        $dto = ParsingConfigDTO::fromArray($data);

        expect($dto->parserType)->toBe('feeds');
        expect($dto->configuration)->toEqual(['url' => 'https://example.com']);
        expect($dto->schedule)->toBe('0 * * * *');
    });

    it('can be converted to array', function () {
        $dto = new ParsingConfigDTO(
            parserType: 'feeds',
            configuration: ['url' => 'https://example.com'],
            schedule: '0 * * * *',
        );

        $array = $dto->toArray();

        expect($array)->toHaveKey('parser_type');
        expect($array)->toHaveKey('configuration');
        expect($array['parser_type'])->toBe('feeds');
    });

    it('handles optional parameters', function () {
        $dto = new ParsingConfigDTO(
            parserType: 'feeds',
            configuration: [],
        );

        expect($dto->schedule)->toBeNull();
    });
});
```

### Service Testing (Unit)

```php
<?php

use App\Services\CategorizationService;
use App\DTOs\NormalizedDataDTO;

describe('CategorizationService', function () {
    it('categorizes technology content correctly', function () {
        $service = app(CategorizationService::class);

        $data = new NormalizedDataDTO(
            title: 'New AI Breakthrough',
            body: 'Researchers have developed a new AI model...',
            author: 'John Doe',
            publishedAt: now(),
            metadata: [],
        );

        $categories = $service->categorize($data);

        expect($categories)->toBeArray();
        expect($categories)->toContain(fn ($cat) => $cat->name === 'Technology');
    });

    it('returns empty array for uncategorizable content', function () {
        $service = app(CategorizationService::class);

        $data = new NormalizedDataDTO(
            title: '',
            body: '',
            author: null,
            publishedAt: null,
            metadata: [],
        );

        $categories = $service->categorize($data);

        expect($categories)->toBeArray();
        expect($categories)->toBeEmpty();
    });
});
```

## Feature Tests

Test complete features including database, services, and HTTP layer.

### Service Testing (Feature)

```php
<?php

use App\Services\ParsingService;
use App\Services\DataNormalizationService;
use App\DTOs\ParsingConfigDTO;
use App\Models\ParsingCampaign;
use App\Models\User;

describe('ParsingService Integration', function () {
    it('executes full parsing workflow', function () {
        // Arrange
        $user = User::factory()->create();
        $campaign = ParsingCampaign::factory()->create([
            'user_id' => $user->id,
            'parser_type' => 'feeds',
        ]);

        $service = app(ParsingService::class);

        // Act
        $result = $service->executeFromCampaign($campaign);

        // Assert
        expect($result)->toBeInstanceOf(ParsingResultDTO::class);
        expect($result->status)->toBe('completed');

        // Verify database persistence
        $this->assertDatabaseHas('parsing_results', [
            'campaign_id' => $campaign->id,
            'status' => 'completed',
        ]);
    });

    it('handles parsing failures gracefully', function () {
        $config = new ParsingConfigDTO(
            parserType: 'invalid-parser',
            configuration: [],
        );

        $service = app(ParsingService::class);

        expect(fn () => $service->executeParsing($config))
            ->toThrow(ParsingException::class);
    });
});
```

### API Testing

```php
<?php

use App\Models\User;
use App\Models\ParsingCampaign;

describe('Campaign API', function () {
    it('can list campaigns for authenticated user', function () {
        $user = User::factory()->create();
        ParsingCampaign::factory()->count(3)->create(['user_id' => $user->id]);
        ParsingCampaign::factory()->count(2)->create(); // Other user's campaigns

        $response = $this->actingAs($user)
            ->getJson('/api/campaigns');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'parser_type', 'is_active'],
            ],
        ]);
        $response->assertJsonCount(3, 'data');
    });

    it('can create campaign with valid data', function () {
        $user = User::factory()->create();

        $data = [
            'name' => 'Test Campaign',
            'parser_type' => 'feeds',
            'configuration' => ['url' => 'https://example.com/feed'],
            'is_active' => true,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/campaigns', $data);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'Test Campaign');

        $this->assertDatabaseHas('parsing_campaigns', [
            'name' => 'Test Campaign',
            'user_id' => $user->id,
        ]);
    });

    it('validates required fields on creation', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/campaigns', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'parser_type', 'configuration']);
    });

    it('requires authentication', function () {
        $response = $this->getJson('/api/campaigns');

        $response->assertUnauthorized();
    });

    it('can execute campaign', function () {
        $user = User::factory()->create();
        $campaign = ParsingCampaign::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/campaigns/{$campaign->id}/execute");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => ['content', 'status'],
        ]);
    });
});
```

## Database Testing

### Using Factories

```php
use App\Models\ParsingCampaign;
use App\Models\User;

it('creates campaign with relationships', function () {
    $user = User::factory()->create();
    $campaign = ParsingCampaign::factory()
        ->for($user)
        ->has(ParsingSource::factory()->count(3))
        ->create();

    expect($campaign->user_id)->toBe($user->id);
    expect($campaign->sources)->toHaveCount(3);
});
```

### Database Assertions

```php
// Check record exists
$this->assertDatabaseHas('parsing_campaigns', [
    'name' => 'Test Campaign',
    'user_id' => $user->id,
]);

// Check record doesn't exist
$this->assertDatabaseMissing('parsing_campaigns', [
    'name' => 'Deleted Campaign',
]);

// Check record count
$this->assertDatabaseCount('parsing_campaigns', 5);
```

## Mocking & Faking

### Mocking Services

```php
use App\Services\ParsingService;
use Mockery\MockInterface;

it('uses mocked parsing service', function () {
    $this->mock(ParsingService::class, function (MockInterface $mock) {
        $mock->shouldReceive('executeParsing')
            ->once()
            ->with(Mockery::type(ParsingConfigDTO::class))
            ->andReturn(new ParsingResultDTO(
                content: 'test',
                normalizedData: [],
                categories: [],
                status: 'completed',
            ));
    });

    $controller = app(ParsingCampaignController::class);
    $result = $controller->execute($campaign);

    expect($result)->not->toBeNull();
});
```

### Faking Queue

```php
use Illuminate\Support\Facades\Queue;

it('dispatches parsing job', function () {
    Queue::fake();

    $service->executeParsing($config);

    Queue::assertPushed(ProcessParsingJob::class);
    Queue::assertPushed(ProcessParsingJob::class, function ($job) {
        return $job->config->parserType === 'feeds';
    });
});
```

### Faking HTTP

```php
use Illuminate\Support\Facades\Http;

it('makes external API call', function () {
    Http::fake([
        'example.com/*' => Http::response(['data' => 'test'], 200),
    ]);

    $result = $service->fetchData();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/api';
    });
});
```

## Test Hooks

### beforeEach / afterEach

```php
describe('ParsingService', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->campaign = ParsingCampaign::factory()->create([
            'user_id' => $this->user->id,
        ]);
    });

    it('uses setup data', function () {
        expect($this->campaign->user_id)->toBe($this->user->id);
    });

    afterEach(function () {
        // Cleanup if needed
    });
});
```

## Datasets

Use datasets for testing multiple scenarios:

```php
it('validates parser types', function (string $parserType, bool $isValid) {
    $dto = new ParsingConfigDTO(
        parserType: $parserType,
        configuration: [],
    );

    if ($isValid) {
        expect($dto->parserType)->toBe($parserType);
    } else {
        expect(fn () => $validator->validate($dto))
            ->toThrow(ValidationException::class);
    }
})->with([
    ['feeds', true],
    ['telegram', true],
    ['reddit', true],
    ['invalid', false],
    ['', false],
]);
```

## Running Tests

```bash
# Run all tests
sail artisan test

# Run specific file
sail artisan test tests/Feature/Services/ParsingServiceTest.php

# Run by filter
sail artisan test --filter=ParsingService

# With coverage
sail artisan test --coverage

# Parallel execution
sail artisan test --parallel

# Stop on first failure
sail artisan test --stop-on-failure
```

## Best Practices

### DO:

- ✅ Write tests before implementation (TDD)
- ✅ Use descriptive test names
- ✅ Follow AAA pattern (Arrange, Act, Assert)
- ✅ Test one thing per test
- ✅ Use factories for test data
- ✅ Mock external dependencies
- ✅ Test edge cases and error conditions
- ✅ Aim for >80% coverage
- ✅ Keep tests fast
- ✅ Use database transactions (automatic in Laravel)

### DON'T:

- ❌ Skip tests to save time
- ❌ Write tests after implementation
- ❌ Test multiple things in one test
- ❌ Use real external APIs
- ❌ Hardcode test data
- ❌ Leave failing tests
- ❌ Skip edge cases
- ❌ Write slow tests
- ❌ Test framework code (test your code)

## Coverage Report

```bash
# Generate coverage report
sail artisan test --coverage

# With minimum threshold
sail artisan test --coverage --min=80

# HTML coverage report
sail artisan test --coverage-html coverage/
```

## Example: Complete Test File

```php
<?php

use App\Services\DataNormalizationService;
use App\DTOs\NormalizedDataDTO;

describe('DataNormalizationService', function () {
    beforeEach(function () {
        $this->service = app(DataNormalizationService::class);
    });

    it('normalizes RSS feed data', function () {
        $rawData = [
            'title' => 'Test Article',
            'description' => 'Test description',
            'author' => 'John Doe',
            'pubDate' => '2025-11-08T10:00:00Z',
        ];

        $result = $this->service->normalize($rawData);

        expect($result)->toBeInstanceOf(NormalizedDataDTO::class);
        expect($result->title)->toBe('Test Article');
        expect($result->body)->toBe('Test description');
        expect($result->author)->toBe('John Doe');
    });

    it('handles missing optional fields', function () {
        $rawData = [
            'title' => 'Test Article',
            'description' => 'Test description',
        ];

        $result = $this->service->normalize($rawData);

        expect($result->title)->toBe('Test Article');
        expect($result->author)->toBeNull();
    });

    it('throws exception for missing required fields', function () {
        expect(fn () => $this->service->normalize([]))
            ->toThrow(ValidationException::class);
    });

    it('sanitizes HTML in content', function () {
        $rawData = [
            'title' => '<script>alert("xss")</script>Test',
            'description' => '<p>Test</p>',
        ];

        $result = $this->service->normalize($rawData);

        expect($result->title)->not->toContain('<script>');
        expect($result->body)->toBe('Test'); // HTML stripped
    });
});
```

## Summary

- **Always write tests first (TDD)**
- **Aim for >80% coverage**
- **Use Pest syntax for readability**
- **Test services, DTOs, and API endpoints**
- **Mock external dependencies**
- **Keep tests fast and focused**
- **Use factories for test data**
- **Follow AAA pattern: Arrange, Act, Assert**

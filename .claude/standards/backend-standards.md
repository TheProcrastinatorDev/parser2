# Backend Standards (Laravel)

## Architecture Principles

This project follows a layered architecture with clear separation of concerns:

```
Controllers → Services → DTOs → Models → Database
```

### Layer Responsibilities

1. **Controllers** - Thin HTTP layer
   - Handle HTTP requests/responses
   - Validate input (via FormRequest)
   - Call services
   - Return API Resources
   - **NO business logic**

2. **Services** - Business logic layer
   - Orchestrate operations
   - Use DTOs for data transfer
   - Call other services
   - Handle transactions
   - **All business logic here**

3. **DTOs (Data Transfer Objects)** - Type-safe data transfer
   - Readonly classes
   - Type-safe properties
   - fromArray() and toArray() methods
   - **No business logic**

4. **Models** - Data layer
   - Eloquent ORM
   - Relationships
   - Accessors/Mutators
   - Scopes
   - **NO business logic** (keep in services)

## Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Controllers | ResourceController | `ParsingCampaignController` |
| Models | Singular PascalCase | `ParsingCampaign`, `ParsingResult` |
| Services | DescriptiveService | `ParsingService`, `DataNormalizationService` |
| DTOs | DescriptiveDTO | `ParsingConfigDTO`, `NormalizedDataDTO` |
| Tables | Plural snake_case | `parsing_campaigns`, `parsing_results` |
| Columns | snake_case | `parser_type`, `created_at` |
| Foreign Keys | singular_id | `campaign_id`, `user_id` |
| Pivot Tables | singular_singular | `category_result` |
| FormRequests | Action + Resource + Request | `StoreParsingCampaignRequest` |
| Resources | Resource + Resource | `ParsingCampaignResource` |
| Middleware | Descriptive | `EnsureUserOwnsResource` |
| Jobs | Descriptive + Job | `ProcessParsingJob` |
| Events | Past tense | `ParsingCompleted` |
| Listeners | Descriptive + Listener | `SendParsingNotification` |
| Routes | kebab-case | `/api/parsing-campaigns` |

## Code Organization

### Directory Structure

```
app/
├── DTOs/                       # Data Transfer Objects
│   ├── ParsingConfigDTO.php
│   ├── ParsingResultDTO.php
│   └── NormalizedDataDTO.php
├── Services/                   # Business logic services
│   ├── ParsingService.php
│   ├── DataNormalizationService.php
│   └── CategorizationService.php
├── Http/
│   ├── Controllers/           # HTTP layer
│   │   └── ParsingCampaignController.php
│   ├── Requests/             # Form validation
│   │   └── StoreParsingCampaignRequest.php
│   ├── Resources/            # API response transformation
│   │   └── ParsingCampaignResource.php
│   └── Middleware/           # HTTP middleware
├── Models/                    # Eloquent models
│   ├── ParsingCampaign.php
│   └── ParsingResult.php
├── Repositories/             # (Optional) Complex queries
│   └── ParsingResultRepository.php
└── Providers/
    └── AppServiceProvider.php
```

## DTO Pattern

### DTO Structure

All DTOs MUST be readonly classes with named constructor parameters.

```php
<?php

namespace App\DTOs;

readonly class ParsingConfigDTO
{
    public function __construct(
        public string $parserType,
        public array $configuration,
        public ?string $schedule = null,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            parserType: $data['parser_type'],
            configuration: $data['configuration'],
            schedule: $data['schedule'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    public static function fromModel($model): self
    {
        return new self(
            parserType: $model->parser_type,
            configuration: $model->configuration,
            schedule: $model->schedule,
            isActive: $model->is_active,
        );
    }

    public function toArray(): array
    {
        return [
            'parser_type' => $this->parserType,
            'configuration' => $this->configuration,
            'schedule' => $this->schedule,
            'is_active' => $this->isActive,
        ];
    }
}
```

### DTO Best Practices

- ✅ Use `readonly` keyword
- ✅ Named constructor parameters
- ✅ Provide `fromArray()` static factory
- ✅ Provide `toArray()` for serialization
- ✅ Type hint all properties
- ✅ Use nullable types with `?` when optional
- ✅ Provide default values for optional parameters
- ❌ NO setters (readonly enforces this)
- ❌ NO business logic in DTOs
- ❌ NO database queries in DTOs

## Service Pattern

### Service Structure

```php
<?php

namespace App\Services;

use App\DTOs\ParsingConfigDTO;
use App\DTOs\ParsingResultDTO;
use App\Models\ParsingCampaign;
use Illuminate\Support\Facades\DB;

class ParsingService
{
    public function __construct(
        private readonly ParserFactory $parserFactory,
        private readonly DataNormalizationService $normalizationService,
        private readonly CategorizationService $categorizationService,
    ) {}

    public function executeParsing(ParsingConfigDTO $config): ParsingResultDTO
    {
        return DB::transaction(function () use ($config) {
            $parser = $this->parserFactory->make($config->parserType);
            $rawData = $parser->parse($config->configuration);

            $normalizedData = $this->normalizationService->normalize($rawData);
            $categories = $this->categorizationService->categorize($normalizedData);

            return new ParsingResultDTO(
                content: $rawData,
                normalizedData: $normalizedData->toArray(),
                categories: $categories,
                status: 'completed',
            );
        });
    }

    public function executeFromCampaign(ParsingCampaign $campaign): ParsingResultDTO
    {
        $config = ParsingConfigDTO::fromModel($campaign);
        return $this->executeParsing($config);
    }
}
```

### Service Best Practices

- ✅ Use constructor injection for dependencies
- ✅ Accept DTOs as parameters
- ✅ Return DTOs from methods
- ✅ Use database transactions for multi-step operations
- ✅ Handle exceptions appropriately
- ✅ Log important operations
- ✅ Type hint everything
- ❌ NO HTTP concerns (requests, responses)
- ❌ NO direct array passing (use DTOs)
- ❌ NO static methods (use dependency injection)

## Controller Pattern

### Controller Structure

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParsingCampaignRequest;
use App\Http\Resources\ParsingCampaignResource;
use App\Services\ParsingService;
use App\Models\ParsingCampaign;
use App\DTOs\ParsingConfigDTO;
use Illuminate\Http\JsonResponse;

class ParsingCampaignController extends Controller
{
    public function __construct(
        private readonly ParsingService $parsingService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/campaigns",
     *     summary="Create a new parsing campaign",
     *     tags={"Campaigns"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreParsingCampaignRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Campaign created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ParsingCampaignResource")
     *     )
     * )
     */
    public function store(StoreParsingCampaignRequest $request): JsonResponse
    {
        $campaign = ParsingCampaign::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => new ParsingCampaignResource($campaign),
            'message' => 'Campaign created successfully',
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/campaigns/{id}/execute",
     *     summary="Execute parsing campaign",
     *     ...
     * )
     */
    public function execute(ParsingCampaign $campaign): JsonResponse
    {
        $result = $this->parsingService->executeFromCampaign($campaign);

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
            'message' => 'Campaign executed successfully',
        ]);
    }
}
```

### Controller Best Practices

- ✅ Use FormRequest for validation
- ✅ Use API Resources for responses
- ✅ Use route model binding
- ✅ Keep methods thin (call services)
- ✅ Add OpenAPI annotations
- ✅ Type hint everything
- ✅ Return JSON responses with consistent structure
- ❌ NO business logic in controllers
- ❌ NO direct model queries (use services)
- ❌ NO array responses (use Resources)

## Model Best Practices

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParsingCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parser_type',
        'configuration',
        'schedule',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(ParsingSource::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ParsingResult::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors (if needed)
    public function getFormattedConfigurationAttribute(): string
    {
        return json_encode($this->configuration, JSON_PRETTY_PRINT);
    }
}
```

## Database Conventions

### Migrations

```php
Schema::create('parsing_campaigns', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('parser_type');
    $table->json('configuration');
    $table->string('schedule')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['user_id', 'parser_type']);
    $table->index('is_active');
});
```

### Best Practices

- ✅ Use `id()` for primary keys
- ✅ Use `foreignId()` for foreign keys with constraints
- ✅ Use `timestamps()` on all tables
- ✅ Add indexes on frequently queried columns
- ✅ Use `json()` for flexible data
- ✅ Use `nullable()` for optional columns
- ✅ Use `default()` for default values
- ✅ Add `onDelete('cascade')` where appropriate

## API Response Format

### Success Response

```php
return response()->json([
    'success' => true,
    'data' => $resource,
    'message' => 'Operation successful',
], 200);
```

### Error Response

```php
return response()->json([
    'success' => false,
    'message' => 'Error message',
    'errors' => $validator->errors(),
    'code' => 'ERROR_CODE',
], 422);
```

## Testing

See [testing-standards.md](testing-standards.md) for complete testing guide.

### Quick Example

```php
<?php

use App\Services\ParsingService;
use App\DTOs\ParsingConfigDTO;

it('can execute parsing with DTO', function () {
    $config = new ParsingConfigDTO(
        parserType: 'feeds',
        configuration: ['url' => 'https://example.com/feed'],
    );

    $service = app(ParsingService::class);
    $result = $service->executeParsing($config);

    expect($result)->toBeInstanceOf(ParsingResultDTO::class);
    expect($result->status)->toBe('completed');
});
```

## Error Handling

```php
try {
    $result = $this->parsingService->executeParsing($config);
} catch (ParsingException $e) {
    Log::error('Parsing failed', [
        'config' => $config->toArray(),
        'error' => $e->getMessage(),
    ]);

    return response()->json([
        'success' => false,
        'message' => 'Parsing failed',
        'code' => 'PARSING_FAILED',
    ], 500);
}
```

## Type Hinting

Always use type hints and return types:

```php
// ✅ Good
public function executeParsing(ParsingConfigDTO $config): ParsingResultDTO
{
    // ...
}

// ❌ Bad
public function executeParsing($config)
{
    // ...
}
```

## Summary

**DO:**
- ✅ Use DTOs for service-to-service communication
- ✅ Keep controllers thin
- ✅ Put business logic in services
- ✅ Use dependency injection
- ✅ Type hint everything
- ✅ Add OpenAPI annotations
- ✅ Use transactions for multi-step operations
- ✅ Follow naming conventions

**DON'T:**
- ❌ Pass arrays between services
- ❌ Put business logic in controllers or models
- ❌ Use static methods
- ❌ Skip type hints
- ❌ Skip OpenAPI documentation
- ❌ Directly access request in services

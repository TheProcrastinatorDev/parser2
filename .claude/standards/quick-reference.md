# Quick Reference

## Common Commands

### Docker & Sail

```bash
# Start containers
sail up -d

# Stop containers
sail down

# View container status
sail ps

# View logs
sail logs
sail logs -f  # Follow logs
```

### Development

```bash
# Full development environment (server + queue + logs + vite)
sail composer dev

# Individual services
sail artisan serve          # Laravel server (port 80)
sail npm run dev            # Vite dev server (port 5173)
sail artisan queue:work     # Queue worker
sail artisan pail           # Real-time logs
```

### Testing

```bash
# Run all tests
sail artisan test

# Run specific test file
sail artisan test tests/Feature/ParsingServiceTest.php

# Run test by filter
sail artisan test --filter=ParsingServiceTest

# With coverage
sail artisan test --coverage

# Parallel execution
sail artisan test --parallel
```

### Code Quality

```bash
# PHP formatting (Laravel Pint)
sail bin pint
sail bin pint --test  # Check only, don't fix

# JavaScript/TypeScript
sail npm run lint           # ESLint with auto-fix
sail npm run format         # Prettier formatting
sail npm run format:check   # Check only
```

### Database

```bash
# Run migrations
sail artisan migrate

# Fresh database with seeders
sail artisan migrate:fresh --seed

# Run seeders only
sail artisan db:seed

# Rollback last migration
sail artisan migrate:rollback

# Rollback all and re-run
sail artisan migrate:refresh

# Interactive database shell
sail artisan tinker

# Database shell
sail mysql
```

### API Documentation

```bash
# Generate OpenAPI/Swagger docs
sail artisan l5-swagger:generate

# Access at: https://dev.parser2.local/api/documentation
```

### Artisan Commands

```bash
# Make controller
sail artisan make:controller ParsingCampaignController

# Make model with migration
sail artisan make:model ParsingCampaign -m

# Make service
sail artisan make:class Services/ParsingService

# Make request
sail artisan make:request StoreParsingCampaignRequest

# Make resource
sail artisan make:resource ParsingCampaignResource

# Make test
sail artisan make:test Services/ParsingServiceTest
sail artisan make:test Services/ParsingServiceTest --unit

# Clear caches
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear
sail artisan view:clear

# List routes
sail artisan route:list
```

### Composer

```bash
# Install dependencies
sail composer install

# Add package
sail composer require vendor/package

# Add dev package
sail composer require --dev vendor/package

# Update dependencies
sail composer update

# Dump autoload
sail composer dump-autoload
```

### NPM

```bash
# Install dependencies
sail npm install

# Add package
sail npm install package-name

# Add dev package
sail npm install -D package-name

# Update dependencies
sail npm update

# Build for production
sail npm run build

# Build with SSR
sail npm run build:ssr
```

## File Locations

### Backend Structure

```
app/
├── DTOs/                    # Data Transfer Objects
│   ├── ParsingConfigDTO.php
│   └── ParsingResultDTO.php
├── Services/                # Business logic layer
│   ├── ParsingService.php
│   └── DataNormalizationService.php
├── Http/
│   ├── Controllers/         # Thin HTTP layer
│   ├── Requests/           # Form validation
│   ├── Resources/          # API response transformation
│   └── Middleware/
├── Models/                  # Eloquent models
└── Providers/              # Service providers
```

### Frontend Structure

```
resources/js/
├── pages/                  # Inertia.js pages
│   ├── Dashboard.vue
│   └── auth/
├── components/             # Vue components
│   └── ui/                # UI component library
├── layouts/               # Layout components
├── composables/           # Vue composables
└── types/                 # TypeScript types
```

### Routes

```
routes/
├── web.php               # Web routes (Inertia)
├── api.php               # API routes
├── auth.php              # Authentication routes
└── console.php           # Artisan commands
```

### Tests

```
tests/
├── Feature/              # Feature/integration tests
│   └── Services/
└── Unit/                # Unit tests
    └── DTOs/
```

## Naming Conventions

### PHP/Laravel

| Element | Convention | Example |
|---------|-----------|---------|
| Controllers | ResourceController | `ParsingCampaignController` |
| Models | Singular PascalCase | `ParsingCampaign` |
| Services | DescriptiveService | `ParsingService` |
| DTOs | DescriptiveDTO | `ParsingConfigDTO` |
| Tables | Plural snake_case | `parsing_campaigns` |
| Migrations | descriptive_snake_case | `create_parsing_campaigns_table` |
| Routes | kebab-case | `/api/parsing-campaigns` |
| Variables | camelCase | `$parsingResult` |
| Constants | UPPER_SNAKE_CASE | `MAX_RETRIES` |

### Vue/TypeScript

| Element | Convention | Example |
|---------|-----------|---------|
| Components | PascalCase | `ParsingCampaignForm.vue` |
| Composables | use prefix | `useParsingCampaign` |
| Props | camelCase | `campaignId` |
| Events | kebab-case | `@campaign-updated` |
| Files | PascalCase | `Dashboard.vue` |

## Git Workflow

### Branch Strategy

- **master** - Production code
- **dev** - Active development

### Commit Format

```
type: subject
```

**Types:**
- `feat` - New feature
- `fix` - Bug fix
- `test` - Tests
- `docs` - Documentation
- `refactor` - Code improvement
- `chore` - Maintenance

**Examples:**
```bash
feat: add data normalization service
fix: resolve parser timeout issue
test: add parsing service tests
docs: update API documentation
```

See [~/.claude/standards/git.md](~/.claude/standards/git.md) for complete guide.

## Environment Variables

Key `.env` variables:

```bash
APP_NAME=parser2
APP_URL=https://dev.parser2.local

DB_CONNECTION=mysql
DB_DATABASE=parser2
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PORT=6379

QUEUE_CONNECTION=redis
```

## Access Points

- **Application:** https://dev.parser2.local
- **API Docs:** https://dev.parser2.local/api/documentation
- **MySQL:** localhost:3306 (via Sail)
- **Redis:** localhost:6379 (via Sail)
- **Vite HMR:** localhost:5173 (via Sail)

## Testing Quick Reference

### Test Structure

```php
<?php

use App\Services\ParsingService;
use App\DTOs\ParsingConfigDTO;

it('can execute parsing', function () {
    // Arrange
    $config = new ParsingConfigDTO(
        parserType: 'feeds',
        configuration: ['url' => 'https://example.com/feed'],
    );

    // Act
    $service = app(ParsingService::class);
    $result = $service->executeParsing($config);

    // Assert
    expect($result)->toBeInstanceOf(ParsingResultDTO::class);
    expect($result->status)->toBe('completed');
});
```

### Useful Test Helpers

```php
// Create factory data
$campaign = ParsingCampaign::factory()->create();

// Act as authenticated user
$this->actingAs($user);

// API testing
$response = $this->postJson('/api/campaigns', $data);
$response->assertStatus(201);
$response->assertJsonStructure(['success', 'data']);

// Database assertions
$this->assertDatabaseHas('parsing_campaigns', ['name' => 'Test']);
```

## Troubleshooting

### Clear all caches

```bash
sail artisan optimize:clear
```

### Rebuild containers

```bash
sail down
sail build --no-cache
sail up -d
```

### Fix permissions

```bash
sudo chown -R $USER:$USER .
```

### View queue jobs

```bash
sail artisan queue:failed      # Failed jobs
sail artisan queue:retry all   # Retry failed
```

## Documentation Links

- **Project Plan:** [docs/PLAN.md](../../docs/PLAN.md)
- **API Docs:** [docs/API.md](../../docs/API.md)
- **Decisions:** [docs/DECISIONS.md](../../docs/DECISIONS.md)
- **TODO:** [docs/TODO.md](../../docs/TODO.md)
- **Git Workflow:** [~/.claude/standards/git.md](~/.claude/standards/git.md)
- **Reference:** `/home/null/misc/apps/personal/parser`

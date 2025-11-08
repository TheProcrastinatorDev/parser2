# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**parser2** is a Full-Stack Web Application built with Laravel 12 + Vue 3 + Inertia.js that provides advanced parsing, processing, normalization, and categorization of data from multiple sources. This is an improved iteration with better API consistency (OpenAPI/Swagger), enhanced architecture (DTOs, service layer), and additional side services for comprehensive data processing.

## Tech Stack

**Backend:**
- Laravel 12 (PHP 8.2+)
- Database: MySQL 8.0
- Cache/Queue: Redis
- Authentication: Laravel Fortify
- API Documentation: OpenAPI/Swagger (L5-Swagger)

**Frontend:**
- Vue 3 with TypeScript
- Inertia.js for SPA-like experience
- Styling: Tailwind CSS 4
- UI Components: reka-ui, lucide-vue-next
- Build Tool: Vite

**Development Environment:**
- Docker via Laravel Sail
- Traefik reverse proxy (configured)
- Access: https://dev.parser2.local

**Testing:**
- Pest PHP for backend tests
- Feature and Unit test separation

## Key Architectural Patterns

This project follows a clean architecture approach with emphasis on:

- **Service Layer Pattern** - Business logic separated from controllers
- **Data Transfer Objects (DTOs)** - Standardized data structures across services
- **Repository Pattern (where needed)** - Abstract data access for complex queries
- **Laravel MVC Defaults** - Standard Eloquent models and route-model binding
- **API Resources** - Consistent API response transformation

### Architecture Principles

1. **Controllers** → Thin, handle HTTP concerns only
2. **Services** → Business logic orchestration, use DTOs for data exchange
3. **DTOs** → Type-safe data transfer between layers
4. **Models** → Eloquent ORM, relationships, basic accessors
5. **API Resources** → Transform model data to API responses
6. **Requests** → Validation logic

## Project-Specific Conventions

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Controllers | ResourceController | `ParsingCampaignController` |
| Services | DescriptiveService | `ParsingService`, `DataNormalizationService` |
| DTOs | DescriptiveDTO | `ParsingConfigDTO`, `NormalizedDataDTO` |
| Models | Singular PascalCase | `ParsingCampaign`, `ParsingResult` |
| Tables | Plural snake_case | `parsing_campaigns`, `parsing_results` |
| Routes | Kebab-case | `/api/parsing-campaigns` |
| API Endpoints | RESTful | `GET /api/resources`, `POST /api/resources` |

### File Organization

- **Controllers**: `app/Http/Controllers/`
- **Services**: `app/Services/`
- **DTOs**: `app/DTOs/`
- **Models**: `app/Models/`
- **Requests**: `app/Http/Requests/`
- **Resources**: `app/Http/Resources/`
- **Tests**: `tests/Feature/`, `tests/Unit/`
- **Vue Components**: `resources/js/components/`
- **Inertia Pages**: `resources/js/pages/`
- **Composables**: `resources/js/composables/`

## Development Workflow

### Git Workflow

This project follows the **master + dev** workflow from `~/.claude/standards/git.md`.

**Branches:**
- `master` - Production-ready code
- `dev` - Active development (current branch)

**Commit Format:**
```
type: subject
```

**Types:** feat, fix, test, docs, refactor, chore

**Examples:**
```bash
feat: add data normalization service
fix: resolve parsing timeout issue
test: add parser service tests
docs: update API documentation
refactor: extract DTO from service
chore: update dependencies
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for complete workflow and `~/.claude/standards/git.md` for standards.

### Testing Requirements

- Write tests FIRST (TDD approach)
- Maintain >80% code coverage
- Run `sail artisan test` before committing
- Feature tests for API endpoints
- Unit tests for services and DTOs

## Code Generation Guidelines

### When generating code, follow these principles:

1. **Check for existing patterns** - Look in `/home/null/misc/apps/personal/parser` for reference implementations
2. **Follow project standards** - Refer to `.claude/standards/` for conventions
3. **Use DTOs for service communication** - Never pass arrays between services
4. **Write tests first** - TDD approach for all new features
5. **Small, focused commits** - One logical change per commit
6. **Document decisions** - Update docs/DECISIONS.md for architectural choices
7. **OpenAPI annotations** - Add Swagger annotations to all API endpoints
8. **Validate inputs** - Use FormRequest classes for validation

### DTO Pattern Example

```php
// app/DTOs/ParsingConfigDTO.php
readonly class ParsingConfigDTO
{
    public function __construct(
        public string $parserType,
        public array $configuration,
        public ?string $schedule = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            parserType: $data['parser_type'],
            configuration: $data['configuration'],
            schedule: $data['schedule'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'parser_type' => $this->parserType,
            'configuration' => $this->configuration,
            'schedule' => $this->schedule,
        ];
    }
}
```

### Service Pattern Example

```php
// app/Services/ParsingService.php
class ParsingService
{
    public function __construct(
        private readonly ParserFactory $parserFactory,
        private readonly DataNormalizationService $normalizationService,
    ) {}

    public function executeParsing(ParsingConfigDTO $config): ParsingResultDTO
    {
        $parser = $this->parserFactory->make($config->parserType);
        $rawData = $parser->parse($config->configuration);

        return $this->normalizationService->normalize($rawData);
    }
}
```

## Development Commands

**IMPORTANT**: All commands must be prefixed with `./vendor/bin/sail` (or use alias: `sail`).

**Application URL**: https://dev.parser2.local

### Setup

```bash
# Initial setup
sail composer install
sail npm install
sail artisan key:generate
sail artisan migrate --seed

# Start containers
sail up -d
```

### Development

```bash
# Start development server (backend + frontend + queue + logs)
sail composer dev

# Or separately:
sail artisan serve          # Laravel server
sail npm run dev            # Vite development server
sail artisan queue:work     # Queue worker
sail artisan pail           # Real-time logs
```

### Testing

```bash
# Run all tests
sail artisan test

# Run specific test
sail artisan test --filter=ParsingServiceTest

# Run with coverage
sail artisan test --coverage
```

### Code Quality

```bash
# Backend
sail bin pint               # PHP formatting (Laravel Pint)

# Frontend
sail npm run lint           # ESLint with auto-fix
sail npm run format         # Prettier formatting
sail npm run format:check   # Check formatting
```

### Database

```bash
sail artisan migrate                    # Run migrations
sail artisan migrate:fresh --seed       # Fresh database with seeders
sail artisan db:seed                    # Run seeders
sail artisan tinker                     # Interactive REPL
```

### API Documentation

```bash
# Generate OpenAPI/Swagger documentation
sail artisan l5-swagger:generate

# Access: https://dev.parser2.local/api/documentation
```

## Important Context

### Current Sprint Focus

- **Primary goal**: Establish architecture with DTOs and service layer
- **Blockers**: None currently
- **Tech debt**: Migrate from parser v1 patterns to v2 (DTOs, better consistency)

### Reference Project

The `/home/null/misc/apps/personal/parser` project serves as a reference for:
- Swagger API implementation
- Parser service patterns
- Data models and relationships
- Testing approaches

**Improvements in parser2:**
- ✅ DTOs for type-safe service communication
- ✅ Better OpenAPI/Swagger documentation
- ✅ Enhanced service layer separation
- ✅ More consistent API responses
- ✅ Additional side services for data processing

### Known Issues & Limitations

- Traefik already configured (https://dev.parser2.local)
- Inherits parser v1 database schema patterns
- Some legacy code may exist from initial setup

## Do's and Don'ts

### DO:

- ✅ Use DTOs for all service-to-service communication
- ✅ Add OpenAPI annotations to all API endpoints
- ✅ Follow service layer pattern for business logic
- ✅ Use dependency injection over direct instantiation
- ✅ Write descriptive commit messages (see ~/.claude/standards/git.md)
- ✅ Update documentation as you code
- ✅ Check `/home/null/misc/apps/personal/parser` for reference patterns
- ✅ Run tests before committing
- ✅ Use readonly DTOs with named constructor parameters

### DON'T:

- ❌ Pass arrays between services (use DTOs instead)
- ❌ Put business logic in controllers
- ❌ Skip OpenAPI annotations on API endpoints
- ❌ Copy-paste without understanding
- ❌ Skip tests to save time
- ❌ Use magic numbers or strings
- ❌ Commit directly to master branch
- ❌ Leave TODO comments without creating issues

## Resources & Documentation

- **Project Plan**: [docs/PLAN.md](docs/PLAN.md) - Project scope and architecture
- **API Documentation**: [docs/API.md](docs/API.md) - API endpoints and usage
- **Architecture Decisions**: [docs/DECISIONS.md](docs/DECISIONS.md) - ADR records
- **Current Tasks**: [docs/TODO.md](docs/TODO.md) - Sprint tasks and priorities
- **Git Workflow**: [~/.claude/standards/git.md](~/.claude/standards/git.md) - Commit standards
- **Reference Project**: `/home/null/misc/apps/personal/parser` - Parser v1 implementation

## Quick Reference

**Start Development:**
```bash
sail up -d && sail composer dev
```

**Run Tests:**
```bash
sail artisan test
```

**Access Application:**
- App: https://dev.parser2.local
- API Docs: https://dev.parser2.local/api/documentation (when configured)

**Common Tasks:**
- New feature: Check TODO.md → Create service with DTOs → Write tests → Implement → Update API docs
- Bug fix: Write failing test → Fix → Verify all tests pass → Commit
- Refactor: Write tests first → Refactor → Ensure tests still pass

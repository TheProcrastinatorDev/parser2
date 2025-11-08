# parser2

Advanced parsing, processing, normalization, and categorization of data from multiple sources with improved architecture and comprehensive API documentation.

## Tech Stack

**Backend:**
- Laravel 12 (PHP 8.2+)
- MySQL 8.0
- Redis
- Laravel Fortify (Authentication)
- OpenAPI/Swagger Documentation

**Frontend:**
- Vue 3 with TypeScript
- Inertia.js
- Tailwind CSS 4
- Vite

**Development:**
- Docker (Laravel Sail)
- Traefik reverse proxy
- Pest PHP testing

## Key Features

- **Data Parsing** - Extract content from multiple sources (RSS, web pages, APIs)
- **Data Normalization** - Transform raw data to standardized schemas using DTOs
- **Content Categorization** - Automatically categorize and tag parsed content
- **RESTful API** - Comprehensive API with OpenAPI/Swagger documentation
- **Campaign Management** - Orchestrate parsing operations across multiple sources
- **Type-Safe Architecture** - DTOs for service-to-service communication

## Getting Started

### Prerequisites

- Docker Desktop
- Git
- Terminal/Command Line

### Installation

```bash
# Clone repository
git clone <repository-url>
cd parser2

# Start Docker containers
./vendor/bin/sail up -d

# Install dependencies
./vendor/bin/sail composer install
./vendor/bin/sail npm install

# Setup environment
cp .env.example .env
./vendor/bin/sail artisan key:generate

# Run migrations
./vendor/bin/sail artisan migrate --seed

# Build frontend
./vendor/bin/sail npm run build

# Start development servers
./vendor/bin/sail composer dev
```

The application will be available at: **https://dev.parser2.local**

### Quick Start Alias

Add this to your shell profile for convenience:

```bash
alias sail='./vendor/bin/sail'
```

Then use `sail` instead of `./vendor/bin/sail`.

## Development

### Start Development Environment

```bash
# Full development stack (server + queue + logs + vite)
sail composer dev

# Or start services individually:
sail up -d              # Docker containers
sail artisan serve      # Laravel server
sail npm run dev        # Vite dev server
sail artisan queue:work # Queue worker
```

### Access Points

- **Application:** https://dev.parser2.local
- **API Documentation:** https://dev.parser2.local/api/documentation (when configured)
- **Database:** localhost:3306 (via Sail)
- **Redis:** localhost:6379 (via Sail)

## Testing

```bash
# Run all tests
sail artisan test

# Run specific test file
sail artisan test --filter=ParsingServiceTest

# Run with coverage
sail artisan test --coverage

# Run tests in parallel
sail artisan test --parallel
```

## Code Quality

```bash
# PHP formatting (Laravel Pint)
sail bin pint

# JavaScript linting and formatting
sail npm run lint           # ESLint with auto-fix
sail npm run format         # Prettier formatting
sail npm run format:check   # Check formatting only
```

## Database

```bash
# Run migrations
sail artisan migrate

# Fresh database with seeders
sail artisan migrate:fresh --seed

# Run seeders only
sail artisan db:seed

# Rollback migrations
sail artisan migrate:rollback

# Interactive database shell
sail artisan tinker
```

## API Documentation

Generate and view OpenAPI/Swagger documentation:

```bash
# Generate documentation
sail artisan l5-swagger:generate

# Access at: https://dev.parser2.local/api/documentation
```

See [docs/API.md](docs/API.md) for detailed API documentation.

## Git Workflow

This project follows the **master + dev** workflow from `~/.claude/standards/git.md`.

**Branches:**
- `master` - Production-ready code
- `dev` - Active development

**Commit Format:**
```
type: subject
```

**Types:** `feat`, `fix`, `test`, `docs`, `refactor`, `chore`

**Examples:**
```bash
feat: add data normalization service
fix: resolve parser timeout issue
test: add normalization service tests
docs: update API documentation
refactor: extract DTO from service
chore: update dependencies
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for complete workflow guidelines.

## Documentation

- **[CLAUDE.md](CLAUDE.md)** - AI-assisted development context
- **[docs/PLAN.md](docs/PLAN.md)** - Project scope and architecture
- **[docs/API.md](docs/API.md)** - API endpoints and usage
- **[docs/DECISIONS.md](docs/DECISIONS.md)** - Architecture Decision Records
- **[docs/TODO.md](docs/TODO.md)** - Sprint planning and tasks
- **[CONTRIBUTING.md](CONTRIBUTING.md)** - Contribution guidelines
- **[CHANGELOG.md](CHANGELOG.md)** - Version history

## Architecture

This project uses a clean architecture approach:

```
┌──────────────┐
│ Controllers  │ → Thin HTTP layer
└──────────────┘
       ↓
┌──────────────┐
│   Services   │ → Business logic
└──────────────┘
       ↓
┌──────────────┐
│     DTOs     │ → Type-safe data transfer
└──────────────┘
       ↓
┌──────────────┐
│    Models    │ → Database layer
└──────────────┘
```

**Key Patterns:**
- **Service Layer** - Business logic separated from controllers
- **DTOs (Data Transfer Objects)** - Type-safe service communication
- **Repository Pattern** - Abstract complex data access
- **API Resources** - Consistent API response transformation

See [docs/DECISIONS.md](docs/DECISIONS.md) for architectural decisions.

## Reference Implementation

Parser v1 (reference): `/home/null/misc/apps/personal/parser`

**Improvements in v2:**
- ✅ DTOs for type-safe service communication
- ✅ OpenAPI/Swagger documentation
- ✅ Enhanced service layer separation
- ✅ Better data normalization and categorization
- ✅ More consistent API responses

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines.

**Quick Summary:**
1. Work on `dev` branch
2. Follow commit message format
3. Write tests first (TDD)
4. Run code quality tools before committing
5. Update documentation

## License

MIT License (or specify your license)

## Support

For issues, questions, or contributions:
- Check [docs/TODO.md](docs/TODO.md) for current tasks
- Review [CLAUDE.md](CLAUDE.md) for project context
- See [docs/API.md](docs/API.md) for API reference

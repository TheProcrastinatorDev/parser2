# Contributing Guide

Thank you for considering contributing to parser2!

## Git Workflow

This project follows the **master + dev** workflow from `~/.claude/standards/git.md`.

### Branch Strategy

- **master**: Production-ready code, deployable at any time
- **dev**: Active development, where all work happens

### Workflow Steps

1. **Work on `dev` branch**
   ```bash
   git checkout dev
   git pull origin dev  # Get latest changes (if team)
   ```

2. **Make your changes**
   - Write tests first (TDD approach)
   - Implement feature/fix
   - Run code quality tools

3. **Commit frequently with descriptive messages**
   ```bash
   git add .
   git commit -m "feat: add data normalization service"
   git commit -m "test: add normalization service tests"
   git commit -m "docs: update API documentation"
   ```

4. **Push to dev branch**
   ```bash
   git push origin dev
   ```

5. **Test thoroughly before merging to master**
   ```bash
   sail artisan test           # Run all tests
   sail bin pint               # Check PHP formatting
   sail npm run lint           # Check JS/TS code
   sail composer dev           # Verify app works
   ```

6. **Merge to master when ready for production**
   ```bash
   git checkout master
   git merge dev
   git push origin master

   # Optional: Tag release
   git tag -a v1.0.0 -m "Release version 1.0.0"
   git push origin v1.0.0

   # Return to dev for continued work
   git checkout dev
   ```

### Commit Message Format

Follow the format from `~/.claude/standards/git.md`:

```
type: subject
```

**Types:**
- `feat` - New feature
- `fix` - Bug fix
- `test` - Tests
- `docs` - Documentation
- `refactor` - Code improvement without behavior change
- `chore` - Maintenance (dependencies, config)

**Rules:**
- Imperative mood: "add" not "added" or "adds"
- No capital first letter
- No period at end
- Maximum 50 characters
- Focus on WHAT and WHY, not HOW

**Examples:**

```bash
# Good
feat: add data normalization service
fix: resolve parser timeout in telegram service
test: add normalization service tests
docs: update API documentation with new endpoints
refactor: extract DTO base class
chore: update laravel to 12.1

# Bad
feat: Added data normalization service  # Capital letter, past tense
fix: Fix bug                            # Not descriptive
test: tests                             # Too vague
docs: Updated docs.                     # Past tense, period at end
```

## Development Process

### 1. Check TODO.md

See [docs/TODO.md](docs/TODO.md) for current tasks and priorities.

### 2. Create/Pick Issue

- Work on defined tasks from TODO.md
- Or create new issue if needed

### 3. Write Tests First (TDD)

```bash
# Create test file first
sail artisan make:test Services/ParsingServiceTest

# Write failing test
# Implement feature
# Make test pass
```

### 4. Implement Feature

**Follow Architecture Patterns:**

#### DTOs (Data Transfer Objects)

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

#### Services

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

#### Controllers

```php
// app/Http/Controllers/ParsingCampaignController.php
class ParsingCampaignController extends Controller
{
    public function __construct(
        private readonly ParsingService $parsingService
    ) {}

    public function execute(ParsingCampaign $campaign): JsonResponse
    {
        $config = ParsingConfigDTO::fromArray($campaign->toArray());
        $result = $this->parsingService->executeParsing($config);

        return response()->json([
            'success' => true,
            'data' => $result->toArray(),
        ]);
    }
}
```

### 5. Update Documentation

As you code:

- Add code comments for complex logic
- Update [docs/API.md](docs/API.md) for new endpoints
- Add OpenAPI annotations to controllers
- Update [docs/DECISIONS.md](docs/DECISIONS.md) for architectural choices
- Update [CHANGELOG.md](CHANGELOG.md) for user-facing changes

### 6. Run Tests

```bash
# All tests
sail artisan test

# Specific test
sail artisan test --filter=ParsingServiceTest

# With coverage
sail artisan test --coverage
```

### 7. Code Quality

```bash
# PHP formatting
sail bin pint

# JavaScript/TypeScript
sail npm run lint           # Lint with auto-fix
sail npm run format         # Prettier formatting
sail npm run format:check   # Check only
```

### 8. Commit

```bash
git add .
git commit -m "feat: add parsing service with DTO support"
```

### 9. Push

```bash
git push origin dev
```

## Code Standards

Refer to project-specific standards in `.claude/standards/`:

- [Backend Standards](.claude/standards/backend-standards.md)
- [Frontend Standards](.claude/standards/frontend-standards.md)
- [Testing Standards](.claude/standards/testing-standards.md)

### Key Standards

**Backend (Laravel):**
- Use DTOs for service-to-service communication (never pass arrays)
- Service layer for business logic
- FormRequest classes for validation
- API Resources for response transformation
- OpenAPI annotations on all API endpoints
- Type hints and return types on all methods

**Frontend (Vue 3):**
- Composition API (not Options API)
- TypeScript for type safety
- Composables for shared logic
- Component organization in pages/ and components/

**Testing:**
- TDD approach: tests first
- >80% code coverage
- Unit tests for services and DTOs
- Feature tests for API endpoints
- Use factories for test data

## Pull Request Process

1. Ensure all tests pass
2. Code formatted with Pint and ESLint
3. Documentation updated
4. Follow commit message format
5. Link to related issue (if exists)
6. Request review (if team)

## When to Merge to Master

Merge `dev` → `master` when:

- ✅ Feature is complete and tested
- ✅ All tests pass
- ✅ Code reviewed and formatted
- ✅ Documentation updated
- ✅ Ready for production deployment
- ✅ CHANGELOG.md updated

## Development Best Practices

### DO:

- ✅ Work on `dev` branch for active development
- ✅ Write tests BEFORE implementation (TDD)
- ✅ Use DTOs for all service communication
- ✅ Add OpenAPI annotations to API endpoints
- ✅ Commit frequently with clear messages
- ✅ Run tests before pushing
- ✅ Update TODO.md as you work
- ✅ Keep master in releasable state
- ✅ Reference parser v1 for patterns: `/home/null/misc/apps/personal/parser`
- ✅ Use dependency injection
- ✅ Type hint everything

### DON'T:

- ❌ Commit directly to master
- ❌ Leave broken code on dev
- ❌ Skip writing tests
- ❌ Pass arrays between services (use DTOs)
- ❌ Put business logic in controllers
- ❌ Skip OpenAPI annotations
- ❌ Merge to master without testing
- ❌ Leave uncommitted changes overnight
- ❌ Use magic numbers or strings
- ❌ Leave TODO comments without creating issues

## Quick Reference

### Common Commands

```bash
# Start development
sail up -d && sail composer dev

# Run tests
sail artisan test

# Format code
sail bin pint
sail npm run lint

# Generate API docs
sail artisan l5-swagger:generate

# Database
sail artisan migrate
sail artisan migrate:fresh --seed
sail artisan tinker
```

### Git Commands

| Action | Command |
|--------|---------|
| Check current branch | `git branch` or `git status` |
| Switch to dev | `git checkout dev` |
| View commit history | `git log --oneline -10` |
| View changes | `git diff` |
| Undo last commit | `git reset --soft HEAD~1` |
| Stash changes | `git stash` |
| Apply stashed changes | `git stash pop` |

## Questions?

- Check [CLAUDE.md](CLAUDE.md) for project context
- Review [docs/PLAN.md](docs/PLAN.md) for project scope
- See [docs/API.md](docs/API.md) for API reference
- Read [~/.claude/standards/git.md](~/.claude/standards/git.md) for git workflow details
- Check [docs/TODO.md](docs/TODO.md) for current tasks
- Reference parser v1: `/home/null/misc/apps/personal/parser`

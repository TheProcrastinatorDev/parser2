# Issue Workflow Guide

This document describes the workflow for working on issues using the master + dev branch strategy.

## Overview

This project uses a **simple two-branch workflow**: master (production) and dev (active development). All work happens on the `dev` branch.

**Branches:**
- **master** - Production-ready, deployable code
- **dev** - Active development, where all work happens

## Daily Workflow

### 1. Pick an Issue

Check [docs/TODO.md](../TODO.md) for current tasks or create a new issue if needed.

**Priority Levels:**
- 🔴 P0 - Critical (blocking other work)
- 🟡 P1 - Important (core features, this sprint)
- 🟢 P2 - Normal (backlog, do soon)
- ⭐ PX - Exciting (fun tasks for motivation)

### 2. Work on Dev Branch

```bash
# Ensure you're on dev branch
git checkout dev

# Pull latest changes (if working with a team)
git pull origin dev

# Verify you're on dev
git branch  # Should show * dev
```

### 3. Write Tests First (TDD)

```bash
# Create test file
sail artisan make:test Services/ParsingServiceTest

# Write failing test
# Implement feature
# Make test pass
```

**Example Test:**

```php
<?php

use App\Services\ParsingService;
use App\DTOs\ParsingConfigDTO;

it('can execute parsing with DTO configuration', function () {
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

### 4. Implement Feature

Follow project patterns:

**DTOs:**
```php
// app/DTOs/ParsingConfigDTO.php
readonly class ParsingConfigDTO
{
    public function __construct(
        public string $parserType,
        public array $configuration,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            parserType: $data['parser_type'],
            configuration: $data['configuration'],
        );
    }
}
```

**Services:**
```php
// app/Services/ParsingService.php
class ParsingService
{
    public function executeParsing(ParsingConfigDTO $config): ParsingResultDTO
    {
        // Business logic here
    }
}
```

**Controllers:**
```php
// app/Http/Controllers/ParsingCampaignController.php
class ParsingCampaignController extends Controller
{
    public function __construct(
        private readonly ParsingService $parsingService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/campaigns/{id}/execute",
     *     summary="Execute parsing campaign",
     *     ...
     * )
     */
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

### 5. Run Tests

```bash
# Run tests
sail artisan test

# Specific test
sail artisan test --filter=ParsingServiceTest

# With coverage
sail artisan test --coverage
```

### 6. Code Quality

```bash
# PHP formatting
sail bin pint

# JavaScript/TypeScript
sail npm run lint
sail npm run format
```

### 7. Commit Frequently

Commit small, logical changes with descriptive messages.

**Commit Message Format** (from `~/.claude/standards/git.md`):

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

**Rules:**
- Imperative mood: "add" not "added"
- Lowercase first letter
- No period at end
- Maximum 50 characters

**Examples:**

```bash
git add .
git commit -m "feat: add data normalization service"
git commit -m "test: add normalization service tests"
git commit -m "docs: update API documentation"
```

### 8. Push Changes

```bash
# Push to dev branch
git push origin dev
```

### 9. Update Documentation

As you work:

- Update [docs/TODO.md](../TODO.md) - Mark tasks as complete
- Update [docs/API.md](../API.md) - Document new endpoints
- Update [CHANGELOG.md](../../CHANGELOG.md) - User-facing changes
- Update [docs/DECISIONS.md](../DECISIONS.md) - Architectural decisions

### 10. Test Before Merging to Master

Before merging to master, ensure:

```bash
# ✅ All tests pass
sail artisan test

# ✅ Code formatted
sail bin pint
sail npm run lint

# ✅ No broken functionality
sail composer dev  # Verify app works

# ✅ Documentation updated
# Check TODO.md, API.md, CHANGELOG.md

# ✅ OpenAPI docs generated
sail artisan l5-swagger:generate
```

### 11. Merge to Master (When Ready for Production)

Only merge to master when feature is complete and tested:

```bash
# Switch to master
git checkout master

# Merge dev into master
git merge dev

# Push to master
git push origin master

# Optional: Tag release
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0

# Return to dev for continued work
git checkout dev
```

## When to Merge to Master

Merge `dev` → `master` when:

- ✅ Feature is complete and tested
- ✅ All tests pass
- ✅ Code has been reviewed (if team)
- ✅ Ready for production deployment
- ✅ Documentation is updated
- ✅ CHANGELOG.md reflects changes

**Don't merge if:**
- ❌ Tests are failing
- ❌ Feature is incomplete
- ❌ Known bugs exist
- ❌ Documentation is missing

## Best Practices

### DO:

- ✅ Work on `dev` branch for active development
- ✅ Write tests before implementation (TDD)
- ✅ Commit frequently with clear messages
- ✅ Run tests before pushing
- ✅ Update TODO.md as you work
- ✅ Keep master in releasable state
- ✅ Use DTOs for service communication
- ✅ Add OpenAPI annotations to API endpoints
- ✅ Reference parser v1 for patterns: `/home/null/misc/apps/personal/parser`

### DON'T:

- ❌ Commit directly to master
- ❌ Leave broken code on dev
- ❌ Skip writing tests
- ❌ Merge to master without testing
- ❌ Leave uncommitted changes overnight
- ❌ Pass arrays between services (use DTOs)
- ❌ Put business logic in controllers
- ❌ Skip OpenAPI annotations

## Quick Reference

| Action | Command |
|--------|---------|
| Check current branch | `git branch` or `git status` |
| Switch to dev | `git checkout dev` |
| See commit history | `git log --oneline -10` |
| View changes | `git diff` |
| Undo last commit | `git reset --soft HEAD~1` |
| List all branches | `git branch -a` |
| Stash changes | `git stash` |
| Apply stash | `git stash pop` |

## Common Scenarios

### Starting a New Feature

```bash
git checkout dev
git pull origin dev
# Create tests
# Implement feature
git add .
git commit -m "feat: add new feature"
git push origin dev
```

### Fixing a Bug

```bash
git checkout dev
# Write failing test that reproduces bug
# Fix bug
# Verify test passes
git add .
git commit -m "fix: resolve parsing timeout issue"
git push origin dev
```

### Updating Documentation

```bash
git checkout dev
# Update docs
git add docs/
git commit -m "docs: update API documentation"
git push origin dev
```

### Releasing to Production

```bash
# Ensure all tests pass
sail artisan test

# Merge to master
git checkout master
git merge dev
git push origin master

# Tag release
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0

# Back to dev
git checkout dev
```

## Troubleshooting

### Uncommitted Changes When Switching Branches

```bash
# Stash changes
git stash

# Switch branch
git checkout master

# Return and apply stash
git checkout dev
git stash pop
```

### Merge Conflicts

```bash
# When merging dev to master
git checkout master
git merge dev

# If conflicts occur
# 1. Open conflicted files
# 2. Resolve conflicts
# 3. Mark as resolved
git add .
git commit -m "chore: resolve merge conflicts"
```

## Related Documentation

- [Git Standards](~/.claude/standards/git.md) - Complete git workflow guide
- [TODO.md](../TODO.md) - Current tasks and issues
- [CONTRIBUTING.md](../../CONTRIBUTING.md) - Contributing guidelines
- [PLAN.md](../PLAN.md) - Project scope and architecture

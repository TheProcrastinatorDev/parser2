# Architecture Decision Records (ADRs)

This document records significant architectural decisions made for parser2.

**Format:** Each decision follows the pattern: Context → Decision → Consequences

**Status Options:**
- Proposed: Under discussion
- Accepted: Approved and implementing
- Deprecated: No longer valid
- Superseded: Replaced by another ADR

---

## ADR Template

Use this template for new decisions:

```markdown
## ADR-XXX: [Decision Title]
**Date:** YYYY-MM-DD
**Status:** [Proposed|Accepted|Deprecated|Superseded]
**Deciders:** [List of people involved]
**Tags:** [e.g., architecture, security, performance]

### Context
[What is the issue/problem that we're seeing that motivates this decision?]

### Decision
[What is the change that we're proposing/have agreed to?]

### Consequences

#### Positive
- [Good outcome 1]
- [Good outcome 2]

#### Negative
- [Trade-off 1]
- [Trade-off 2]

#### Neutral
- [Neutral impact/change]

### Alternatives Considered
1. **[Alternative 1]**
   - Pros: [Benefits]
   - Cons: [Drawbacks]
   - Rejected because: [Reason]

### Implementation Notes
[Any specific implementation details or migration steps]

### References
- [Link to relevant documentation]
```

---

## ADR-001: Use Laravel 12 for Backend Framework

**Date:** 2025-11-08
**Status:** Accepted
**Deciders:** Project Lead
**Tags:** architecture, backend

### Context

This project builds upon parser v1 which already uses Laravel. The existing codebase at `/home/null/misc/apps/personal/parser` is Laravel-based with established patterns and database schemas.

### Decision

Continue using **Laravel 12** as the backend framework for parser2, inheriting the foundation from parser v1 while applying architectural improvements.

### Consequences

#### Positive

- ✅ Continuity with parser v1 - can reference existing patterns
- ✅ Comprehensive ecosystem (Eloquent ORM, Sanctum auth, testing)
- ✅ Laravel Sail for Docker development environment
- ✅ Existing database schema and migrations as foundation
- ✅ Strong type safety with PHP 8.2+
- ✅ Excellent documentation and community support

#### Negative

- ❌ Framework lock-in (but acceptable for this use case)
- ❌ Some legacy patterns from v1 may need refactoring
- ❌ Heavier than microframeworks (but performance acceptable)

#### Neutral

- Database migrations can be adapted from v1
- Team already familiar with Laravel patterns

### Implementation Notes

- Use Laravel 12's latest features (readonly properties, enums)
- Apply service layer pattern consistently
- Leverage dependency injection container
- Use FormRequest classes for validation

### References

- Parser v1: `/home/null/misc/apps/personal/parser`
- Laravel 12 docs: https://laravel.com/docs/12.x

---

## ADR-002: Use MySQL 8.0 for Primary Database

**Date:** 2025-11-08
**Status:** Accepted
**Deciders:** Project Lead
**Tags:** architecture, database

### Context

Project detected MySQL 8.0 in docker-compose.yaml. Parser v1 uses MySQL. Need a relational database for structured parsing data with relationships (campaigns → sources → results).

### Decision

Use **MySQL 8.0** as the primary database for parser2.

### Consequences

#### Positive

- ✅ Excellent support for complex queries and relationships
- ✅ ACID compliance for data integrity
- ✅ JSON column support for flexible parser configurations
- ✅ Full-text search capabilities
- ✅ Already configured in Laravel Sail

#### Negative

- ❌ Requires careful index management for large datasets
- ❌ More complex horizontal scaling than NoSQL

#### Neutral

- Standard choice for Laravel applications
- Redis used for caching and queues

### Implementation Notes

- Use JSON columns for parser-specific configuration
- Add proper indexes on foreign keys and search columns
- Use migrations for schema versioning

---

## ADR-003: Adopt Data Transfer Objects (DTOs) for Service Layer

**Date:** 2025-11-08
**Status:** Accepted
**Deciders:** Project Lead
**Tags:** architecture, code-quality, improvement

### Context

Parser v1 passes arrays between services, leading to:
- Lack of type safety
- Unclear service contracts
- Difficult refactoring
- No IDE autocomplete

Parser2 aims to improve code quality and maintainability.

### Decision

Implement **readonly DTOs** for all service-to-service communication in parser2.

### Consequences

#### Positive

- ✅ Type-safe data transfer between services
- ✅ Clear service contracts and expectations
- ✅ Better IDE support (autocomplete, type checking)
- ✅ Easier refactoring with PHPStan/Psalm
- ✅ Self-documenting code
- ✅ Immutability prevents unintended mutations

#### Negative

- ❌ Slight overhead creating DTO instances
- ❌ More boilerplate code (offset by benefits)
- ❌ Learning curve for developers unfamiliar with pattern

#### Neutral

- Need to establish DTO conventions (fromArray, toArray)
- Services become more testable

### Alternatives Considered

1. **Continue with Arrays**
   - Pros: Less code, familiar pattern
   - Cons: No type safety, error-prone, hard to maintain
   - Rejected because: Benefits of type safety outweigh convenience

2. **Use Collections**
   - Pros: Laravel native, chainable methods
   - Cons: Still not type-safe, doesn't enforce contract
   - Rejected because: Collections good for iteration, not service contracts

3. **Use Laravel Resources**
   - Pros: Already in Laravel, good for API responses
   - Cons: Designed for HTTP layer, not service layer
   - Rejected because: Resources are for HTTP transformation, not business logic

### Implementation Notes

**DTO Pattern:**

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

**Conventions:**
- All DTOs are readonly
- Named constructor parameters
- Static `fromArray()` factory method
- `toArray()` for serialization
- Store in `app/DTOs/` directory

### References

- [PHP readonly properties](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)
- Martin Fowler on DTOs: https://martinfowler.com/eaaCatalog/dataTransferObject.html

---

## ADR-004: Use OpenAPI/Swagger for API Documentation

**Date:** 2025-11-08
**Status:** Accepted
**Deciders:** Project Lead
**Tags:** documentation, api, improvement

### Context

Parser v1 lacks comprehensive API documentation. Developers need to read code to understand endpoints. Parser2 aims for better API consistency and documentation.

### Decision

Use **L5-Swagger** package to generate OpenAPI 3.0 documentation from annotations.

### Consequences

#### Positive

- ✅ Interactive API documentation at `/api/documentation`
- ✅ Industry-standard OpenAPI format
- ✅ Auto-generated from code annotations (stays in sync)
- ✅ Try-it-out functionality for testing endpoints
- ✅ Request/response examples
- ✅ Authentication flows documented

#### Negative

- ❌ Additional annotation overhead in controllers
- ❌ Requires discipline to keep annotations updated
- ❌ Learning curve for OpenAPI syntax

#### Neutral

- Documentation generation is automated
- Can export to JSON/YAML for client SDK generation

### Alternatives Considered

1. **Manual API Documentation (Markdown)**
   - Pros: Simple, flexible
   - Cons: Gets out of sync, no interactive features
   - Rejected because: Prone to documentation drift

2. **Postman Collections**
   - Pros: Easy to share, can import/export
   - Cons: Not code-based, manual maintenance
   - Rejected because: Separate from codebase, harder to maintain

3. **API Blueprint or RAML**
   - Pros: Alternative specification formats
   - Cons: Less popular than OpenAPI, fewer tools
   - Rejected because: OpenAPI is industry standard

### Implementation Notes

- Install L5-Swagger package: `composer require darkaonline/l5-swagger`
- Configure to use OpenAPI 3.0
- Add annotations to all controllers
- Generate docs: `php artisan l5-swagger:generate`
- Access at: https://dev.parser2.local/api/documentation

**Example Annotation:**

```php
/**
 * @OA\Post(
 *     path="/api/campaigns",
 *     summary="Create a new parsing campaign",
 *     tags={"Campaigns"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/ParsingCampaignRequest")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Campaign created successfully",
 *         @OA\JsonContent(ref="#/components/schemas/ParsingCampaignResource")
 *     )
 * )
 */
public function store(StoreParsingCampaignRequest $request)
{
    // ...
}
```

### References

- L5-Swagger: https://github.com/DarkaOnLine/L5-Swagger
- OpenAPI Specification: https://spec.openapis.org/oas/v3.0.0

---

## ADR-005: Use Vue 3 + Inertia.js + TypeScript for Frontend

**Date:** 2025-11-08
**Status:** Accepted
**Deciders:** Project Lead
**Tags:** architecture, frontend

### Context

Detected from package.json. Project already configured with Vue 3, Inertia.js, and TypeScript. Need modern SPA experience with server-side routing.

### Decision

Use **Vue 3 + Inertia.js + TypeScript** for the frontend application.

### Consequences

#### Positive

- ✅ SPA-like experience without complex routing
- ✅ Type safety with TypeScript
- ✅ Server-side routing with Inertia simplifies auth
- ✅ Composition API for better code organization
- ✅ Excellent TypeScript support in Vue 3
- ✅ Already configured in project

#### Negative

- ❌ Inertia ties frontend to Laravel backend
- ❌ TypeScript learning curve
- ❌ More complex build process

#### Neutral

- Consistent with parser v1 stack
- Tailwind CSS 4 for styling

### Implementation Notes

- Use Composition API (not Options API)
- Define TypeScript interfaces for API responses
- Leverage composables for shared logic
- Use Inertia.js for navigation

---

## ADR-006: Use Pest PHP for Testing

**Date:** 2025-11-08
**Status:** Accepted
**Deciders:** Project Lead
**Tags:** testing, code-quality

### Context

Detected in composer.json. Pest provides expressive testing syntax built on PHPUnit.

### Decision

Use **Pest PHP** as the primary testing framework.

### Consequences

#### Positive

- ✅ Expressive, readable test syntax
- ✅ Built on PHPUnit (can use PHPUnit features)
- ✅ Excellent Laravel integration
- ✅ Type-safe with PHP 8.2+
- ✅ Fast test execution

#### Negative

- ❌ Another framework to learn (if unfamiliar)

#### Neutral

- Can mix PHPUnit tests if needed
- Standard choice for modern Laravel apps

### Implementation Notes

- Separate test suites: Unit, Feature
- TDD approach: write tests first
- Maintain >80% code coverage
- Test DTOs, services, and API endpoints

---

---

## ADR-007: Adapt Parser v1 Architecture as Foundation

**Date:** 2025-11-08
**Status:** Accepted
**Deciders:** Project Lead
**Tags:** architecture, parser, code-reuse

### Context

parser2 needs to implement 8 different parser types (Feeds, Reddit, Single Page, Telegram, Medium, Bing, Multi-URL, Craigslist). Parser v1 (`/home/null/misc/apps/personal/parser`) has battle-tested implementations of these parsers that handle many edge cases.

Two approaches considered:
1. Build parsers from scratch with modern patterns
2. Adapt parser v1 architecture with v2 improvements (DTOs, OpenAPI, better testing)

### Decision

**Adapt parser v1 architecture** as the foundation for parser2, with the following improvements:
- Replace arrays with DTOs (ParseRequestDTO, ParseResultDTO)
- Add comprehensive test coverage (>80%)
- Implement OpenAPI documentation
- Use dependency injection throughout
- Add proper error handling and logging

### Consequences

#### Positive

- ✅ **Proven patterns:** Parser v1 is battle-tested in production
- ✅ **Edge cases handled:** HTTP retries, encoding issues, rate limiting already solved
- ✅ **Faster development:** Focus on improvements, not reinventing wheel
- ✅ **Lower risk:** Fewer unknown unknowns, proven to work
- ✅ **Knowledge transfer:** Can reference v1 for complex logic

#### Negative

- ❌ **Legacy patterns:** Some v1 patterns need refactoring
- ❌ **Learning curve:** Need to understand v1 code first
- ❌ **Potential tech debt:** May carry over v1 assumptions

#### Neutral

- AbstractParser pattern provides good extensibility
- ParserManager registration system is solid
- Support services (HttpClient, ContentExtractor, RateLimiter) are well-designed

### Alternatives Considered

1. **Build from scratch**
   - Pros: Clean slate, modern patterns from day 1
   - Cons: Would miss v1's edge case handling, slower development
   - Rejected because: Risk of missing production edge cases is too high

### Implementation Notes

- **Foundation built (Phase 1 complete):**
  - AbstractParser base class with pagination, error handling
  - ParserManager for registration/lookup
  - HttpClient with retry logic (exponential backoff, 429/500/502/503/504)
  - ContentExtractor with HTML cleaning, image extraction, URL fixing
  - RateLimiter with per-minute/hour limits
  - DTOs: ParseRequestDTO, ParseResultDTO (readonly, type-safe)

- **P1 Parsers complete (Phase 2A):**
  - FeedsParser: RSS/Atom/JSON with auto-detection
  - RedditParser: JSON API with post type detection (text/image/video/link)
  - SinglePageParser: CSS/XPath/auto extraction

- **Reference parser v1 for:**
  - Complex parsing logic
  - Edge case handling
  - Test data/fixtures

---

## ADR Index

| ID | Title | Status | Date | Tags |
|----|-------|--------|------|------|
| 001 | Use Laravel 12 for Backend | Accepted | 2025-11-08 | architecture, backend |
| 002 | Use MySQL 8.0 for Database | Accepted | 2025-11-08 | architecture, database |
| 003 | Adopt DTOs for Service Layer | Accepted | 2025-11-08 | architecture, code-quality |
| 004 | Use OpenAPI/Swagger for API Docs | Accepted | 2025-11-08 | documentation, api |
| 005 | Use Vue 3 + Inertia.js + TS | Accepted | 2025-11-08 | architecture, frontend |
| 006 | Use Pest PHP for Testing | Accepted | 2025-11-08 | testing, code-quality |
| 007 | Adapt Parser v1 Architecture | Accepted | 2025-11-08 | architecture, parser, code-reuse |

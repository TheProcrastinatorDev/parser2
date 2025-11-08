# Issue #1: Implement Parser2 Architecture: 8 Standalone Parsers with DTO-based Service Layer

**GitHub Issue:** https://github.com/TheProcrastinatorDev/parser2/issues/1
**Created:** 2025-11-08
**Status:** 🔴 Not Started
**Branch:** dev (following git.md workflow)
**Sprint Priority:** P1 (from TODO.md)

---

## Project Context References

### From CLAUDE.md
- **Project Type:** Full-Stack Web Application (Laravel 12 + Vue 3 + Inertia.js)
- **Tech Stack:** Laravel 12 (PHP 8.2+), MySQL 8.0, Redis, Vue 3, TypeScript, Tailwind CSS 4
- **Key Patterns:**
  - Service Layer Pattern - Business logic separated from controllers
  - Data Transfer Objects (DTOs) - Standardized data structures across services
  - Repository Pattern (where needed) - Abstract data access for complex queries
  - API Resources - Consistent API response transformation
- **Conventions:**
  - Controllers: `{Resource}Controller` → Thin, handle HTTP concerns only
  - Services: `{Descriptive}Service` → Business logic orchestration, use DTOs
  - DTOs: `{Descriptive}DTO` → Type-safe data transfer between layers
  - Models: Singular PascalCase (Eloquent ORM, relationships, basic accessors)
  - Routes: Kebab-case (e.g., `/api/parsing-campaigns`)

### From docs/PLAN.md
- **Related MVP Feature:** Core Parsing Service with DTOs (Must Have - P0/P1)
- **Success Metric:** "All core parsers migrated with DTO-based architecture"
- **Architecture Component:** ParsingService + NormalizationService + CategorizationService
- **Performance Target:** Process 100+ parsing operations per minute

### From docs/TODO.md
- **Sprint Goal:** Establish DTO-based architecture and core services with OpenAPI documentation
- **Dependencies:** Start Sail containers (P0), Database migrations (P1)
- **Blockers:** DTO pattern needs to be standardized before implementing all services

### From docs/DECISIONS.md
- **Relevant ADRs:**
  - ADR-003: Adopt DTOs for Service Layer - Use readonly classes for type-safe service communication
  - ADR-006: Use Pest PHP for Testing - TDD approach with >80% code coverage

---

## Problem Analysis

### Detailed Problem Statement

parser2 requires a complete parsing infrastructure that supports multiple data sources with a unified DTO-based architecture. Currently, the project has only foundational setup (L5-Swagger, Debugbar, Documentation) but lacks:

- **Core DTOs:** 0 implemented (need ParseRequestDTO, ParseResultDTO, and parser-specific DTOs)
- **Service Layer:** 0 services implemented (need ParserManager + 8 individual parsers)
- **Parser Implementations:** 0 parsers (need 8 standalone parsers from v1)
- **API Endpoints:** Only /api/health exists (need complete parser management API)

### Alignment with Project Goals

- **Contributes to:** "All core parsers migrated with DTO-based architecture" success metric
- **Solves:** Need for consistent, well-documented API to automate content extraction
- **Priority:** P1 - Core MVP feature, blocking other parsing-related work

### Edge Cases Identified

1. **HTTP Failures:** Network timeouts, 429 rate limiting, 500 server errors
2. **Malformed Data:** Invalid RSS/JSON feeds, broken HTML, encoding issues
3. **IP Blocking:** Craigslist and other scrapers may trigger blocking
4. **Rate Limiting:** Must respect per-parser rate limits to avoid bans
5. **Empty Results:** Handle gracefully when no items are found
6. **Pagination:** Support offset-based pagination across all parsers
7. **Concurrent Requests:** Thread-safe parser execution
8. **Content Extraction:** Lazy-loaded images, JavaScript-rendered content

---

## Prior Art Research

### From Project Documentation

**Parser v1 Architecture** (`/home/null/misc/apps/personal/parser`):

1. **AbstractParser** (`app/Services/Parser/AbstractParser.php`)
   - Base class with unified configuration loading
   - Error handling and logging
   - Item processing pipeline
   - Pagination support via offset/limit

2. **ParserManager** (`app/Services/Parser/ParserManager.php`)
   - Registration pattern for parsers
   - Configuration validation
   - Parser lookup and invocation

3. **Support Services:**
   - **HttpClient** - Retry logic (429/500 errors), user-agent rotation, proxy support
   - **ContentExtractor** - Image extraction, HTML cleaning, UTF-8 handling
   - **RateLimiter** - Requests per minute/hour limits

4. **DTOs:**
   - **ParseRequestDTO** - source, type, keywords, options, limit, offset, filters
   - **ParseResultDTO** - success, items, error, metadata, total, nextOffset

### From Previous Issues

**No previous issues found** - This is issue #1

### From .scratchpads/

**No previous scratchpads** - This is the first implementation issue

### Similar Code Patterns Found

**From parser v1:**

**FeedsParser Pattern:**
```php
// Detects feed type automatically
$feedType = $this->detectFeedType($content);
// Parse based on type
$items = match($feedType) {
    'json' => $this->parseJsonFeed($content),
    'atom' => $this->parseAtomFeed($content),
    default => $this->parseRssFeed($content),
};
```

**HttpClient Retry Pattern:**
```php
// Retry on specific status codes
$retryableStatuses = [429, 500, 502, 503, 504];
if (in_array($status, $retryableStatuses) && $attempt < $maxRetries) {
    sleep(pow(2, $attempt)); // Exponential backoff
    return $this->retry($url, $options, $attempt + 1);
}
```

**DTO Pattern:**
```php
readonly class ParseRequestDTO {
    public function __construct(
        public string $source,
        public string $type,
        public array $keywords = [],
        public array $options = [],
        public ?int $limit = null,
        public ?int $offset = null,
        public array $filters = [],
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            source: $data['source'],
            type: $data['type'],
            keywords: $data['keywords'] ?? [],
            options: $data['options'] ?? [],
            limit: $data['limit'] ?? null,
            offset: $data['offset'] ?? null,
            filters: $data['filters'] ?? [],
        );
    }
}
```

---

## Implementation Plan

### Following Project Standards

#### From backend-standards.md
- **Controller pattern:** Thin controllers (ParserController) with validation via FormRequest
- **Service layer:** Business logic in services (ParserManager, individual parsers)
- **Naming convention:**
  - Services: `app/Services/Parser/{Parser}Parser.php`
  - DTOs: `app/DTOs/Parser/{Name}DTO.php`
  - Tests: `tests/Unit/Services/Parser/Parsers/{Parser}ParserTest.php`

#### From frontend-standards.md
- Not applicable for this backend-focused issue
- Future: Vue components for parser management UI

#### From testing-standards.md
- **Coverage requirement:** >80% (from CLAUDE.md)
- **Test organization:**
  - Unit tests: `tests/Unit/` for services, DTOs, support classes
  - Feature tests: `tests/Feature/` for API endpoints
- **Naming pattern:** `{ClassName}Test.php`
- **TDD approach:** Write tests first, then implement

### Files to Modify/Create

**Phase 1: Foundation**
- [ ] `app/Services/Parser/AbstractParser.php` - Base parser class
- [ ] `app/Services/Parser/ParserManager.php` - Parser registration/invocation
- [ ] `app/Services/Parser/Support/HttpClient.php` - HTTP with retry logic
- [ ] `app/Services/Parser/Support/ContentExtractor.php` - HTML/content processing
- [ ] `app/Services/Parser/Support/RateLimiter.php` - Rate limiting
- [ ] `app/DTOs/Parser/ParseRequestDTO.php` - Request DTO
- [ ] `app/DTOs/Parser/ParseResultDTO.php` - Result DTO
- [ ] `config/parser.php` - Parser configuration
- [ ] `tests/Unit/Services/Parser/AbstractParserTest.php`
- [ ] `tests/Unit/Services/Parser/ParserManagerTest.php`
- [ ] `tests/Unit/Services/Parser/Support/HttpClientTest.php`
- [ ] `tests/Unit/Services/Parser/Support/ContentExtractorTest.php`
- [ ] `tests/Unit/Services/Parser/Support/RateLimiterTest.php`

**Phase 2: Parsers**

P1 Parsers (Simple, High Value):
- [ ] `app/Services/Parser/Parsers/FeedsParser.php` - RSS/Atom/JSON
- [ ] `app/Services/Parser/Parsers/RedditParser.php` - Reddit JSON API
- [ ] `app/Services/Parser/Parsers/SinglePageParser.php` - Generic HTML extraction
- [ ] `tests/Unit/Services/Parser/Parsers/FeedsParserTest.php`
- [ ] `tests/Unit/Services/Parser/Parsers/RedditParserTest.php`
- [ ] `tests/Unit/Services/Parser/Parsers/SinglePageParserTest.php`

P2 Parsers (Medium Complexity):
- [ ] `app/Services/Parser/Parsers/TelegramParser.php` - Telegram scraping
- [ ] `app/Services/Parser/Parsers/MediumParser.php` - Medium RSS
- [ ] `app/Services/Parser/Parsers/BingSearchParser.php` - Bing scraping
- [ ] `tests/Unit/Services/Parser/Parsers/TelegramParserTest.php`
- [ ] `tests/Unit/Services/Parser/Parsers/MediumParserTest.php`
- [ ] `tests/Unit/Services/Parser/Parsers/BingSearchParserTest.php`

P3 Parsers (Complex):
- [ ] `app/Services/Parser/Parsers/MultiUrlParser.php` - Bulk URL extraction
- [ ] `app/Services/Parser/Parsers/CraigslistParser.php` - Craigslist scraping
- [ ] `tests/Unit/Services/Parser/Parsers/MultiUrlParserTest.php`
- [ ] `tests/Unit/Services/Parser/Parsers/CraigslistParserTest.php`

Service Provider:
- [ ] `app/Providers/ParserServiceProvider.php` - Register all parsers

**Phase 3: API**
- [ ] `app/Http/Controllers/Api/ParserController.php` - Parser API endpoints
- [ ] `app/Http/Requests/Parser/ExecuteParserRequest.php` - Validation
- [ ] `app/Http/Resources/Parser/ParserResource.php` - Parser details
- [ ] `app/Http/Resources/Parser/ParserCollectionResource.php` - Parser list
- [ ] `app/Http/Resources/Parser/ParseResultResource.php` - Parse results
- [ ] `routes/api.php` - Update with parser routes
- [ ] `tests/Feature/Api/ParserApiTest.php`

**Phase 4: Documentation**
- [ ] docs/API.md - Add parser endpoint documentation
- [ ] OpenAPI annotations on ParserController
- [ ] Regenerate Swagger docs: `sail artisan l5-swagger:generate`

### Data/API Impacts

**New API Endpoints:**
```
GET    /api/parsers              - List all available parsers
GET    /api/parsers/{parser}     - Get parser details
POST   /api/parsers/parse        - Execute parsing
GET    /api/parsers/{parser}/types - Get supported types
```

**Request/Response Schemas:** See issue description

---

## TDD Implementation Tasks

### Phase 0: Setup & Context
- [x] Review parser v1 codebase at `/home/null/misc/apps/personal/parser`
- [x] Read CLAUDE.md for DTO patterns and service layer guidance
- [x] Review docs/PLAN.md Success Metrics section
- [x] Note ADR-003 (DTOs) and ADR-006 (Pest) from docs/DECISIONS.md

### Phase 1: Foundation (TDD)

**AbstractParser:**
- [ ] Write test: parser loads configuration from config/parser.php
- [ ] Write test: parser validates ParseRequestDTO
- [ ] Write test: parser handles errors gracefully with try/catch
- [ ] Write test: parser processes items through pipeline
- [ ] Write test: parser supports pagination via offset/limit
- [ ] Implement AbstractParser base class
- [ ] All tests passing ✅

**HttpClient:**
- [ ] Write test: successful HTTP GET request
- [ ] Write test: retry logic on 429 (rate limit) errors with exponential backoff
- [ ] Write test: retry logic on 500/502/503/504 errors
- [ ] Write test: user-agent rotation (cycle through user agents)
- [ ] Write test: timeout handling (throw exception on timeout)
- [ ] Write test: proxy support (if configured)
- [ ] Implement HttpClient support class
- [ ] All tests passing ✅

**ContentExtractor:**
- [ ] Write test: extract images from HTML content
- [ ] Write test: clean HTML (remove scripts, styles, ads)
- [ ] Write test: handle UTF-8 encoding correctly
- [ ] Write test: fix relative URLs to absolute
- [ ] Write test: extract meta tags (og:image, twitter:image)
- [ ] Implement ContentExtractor
- [ ] All tests passing ✅

**RateLimiter:**
- [ ] Write test: enforce requests per minute limit
- [ ] Write test: enforce requests per hour limit
- [ ] Write test: per-parser rate limiting (separate limits)
- [ ] Write test: reset rate limit after time window
- [ ] Implement RateLimiter
- [ ] All tests passing ✅

**DTOs:**
- [ ] Write test: ParseRequestDTO::fromArray() factory method
- [ ] Write test: ParseRequestDTO::toArray() serialization
- [ ] Write test: ParseRequestDTO type safety (readonly properties)
- [ ] Write test: ParseResultDTO construction with required fields
- [ ] Write test: ParseResultDTO handles null error field
- [ ] Write test: ParseResultDTO pagination (nextOffset)
- [ ] Implement ParseRequestDTO and ParseResultDTO
- [ ] All tests passing ✅

**ParserManager:**
- [ ] Write test: register parser with string key
- [ ] Write test: get parser by name returns correct instance
- [ ] Write test: list all parsers returns array
- [ ] Write test: parser not found throws ParserNotFoundException
- [ ] Write test: duplicate registration throws exception
- [ ] Implement ParserManager
- [ ] All tests passing ✅

### Phase 2A: P1 Parsers (TDD - parallel after Phase 1)

**FeedsParser:**
- [ ] Write test: detect feed type (RSS/Atom/JSON)
- [ ] Write test: parse RSS 2.0 feed
- [ ] Write test: parse Atom feed
- [ ] Write test: parse JSON feed format
- [ ] Write test: parse Google News RSS feed
- [ ] Write test: extract images from content via ContentExtractor
- [ ] Write test: handle invalid feed XML/JSON
- [ ] Write test: respect max_items limit
- [ ] Implement FeedsParser
- [ ] All tests passing ✅
- [ ] Add to ParserServiceProvider registration

**RedditParser:**
- [ ] Write test: parse subreddit JSON endpoint
- [ ] Write test: parse user submissions JSON
- [ ] Write test: parse single post JSON
- [ ] Write test: determine post type (text/image/video/link)
- [ ] Write test: extract post metadata (score, upvote_ratio, gilded)
- [ ] Write test: handle pagination via 'after' parameter
- [ ] Write test: handle NSFW and spoiler flags
- [ ] Implement RedditParser
- [ ] All tests passing ✅
- [ ] Add to ParserServiceProvider registration

**SinglePageParser:**
- [ ] Write test: auto content extraction (intelligent detection)
- [ ] Write test: CSS selector extraction
- [ ] Write test: XPath expression extraction
- [ ] Write test: regex pattern extraction
- [ ] Write test: fix relative URLs to absolute
- [ ] Write test: handle lazy-loaded images
- [ ] Write test: clean HTML before extraction
- [ ] Implement SinglePageParser
- [ ] All tests passing ✅
- [ ] Add to ParserServiceProvider registration

### Phase 2B: P2 Parsers (TDD)

**TelegramParser:**
- [ ] Write test: parse public channel messages
- [ ] Write test: detect message types (text/photo/video/link)
- [ ] Write test: extract media thumbnails
- [ ] Write test: normalize t.me URLs (t.me/channel → t.me/s/channel)
- [ ] Write test: extract view counts
- [ ] Write test: handle YouTube links in messages
- [ ] Implement TelegramParser
- [ ] All tests passing ✅
- [ ] Add to ParserServiceProvider registration

**MediumParser:**
- [ ] Write test: parse user RSS feed (@username/feed)
- [ ] Write test: parse publication feed (medium.com/feed/{publication})
- [ ] Write test: extract read time from content
- [ ] Write test: extract author information
- [ ] Write test: handle both RSS 2.0 and Atom formats
- [ ] Write test: estimate read time if not provided
- [ ] Implement MediumParser
- [ ] All tests passing ✅
- [ ] Add to ParserServiceProvider registration

**BingSearchParser:**
- [ ] Write test: parse web search results
- [ ] Write test: parse news search results
- [ ] Write test: parse image search results
- [ ] Write test: extract result titles, URLs, descriptions
- [ ] Write test: pagination support via 'first' parameter
- [ ] Write test: handle no results found
- [ ] Implement BingSearchParser
- [ ] All tests passing ✅
- [ ] Add to ParserServiceProvider registration

### Phase 2C: P3 Parsers (TDD)

**MultiUrlParser:**
- [ ] Write test: extract URLs via XPath selectors
- [ ] Write test: extract URLs via CSS selectors
- [ ] Write test: extract URLs via regex patterns
- [ ] Write test: extract URLs from fixed list
- [ ] Write test: process individual URLs with SinglePageParser
- [ ] Write test: deduplicate extracted links
- [ ] Write test: fix relative URLs to absolute
- [ ] Write test: graceful fallback if individual fetch fails
- [ ] Implement MultiUrlParser (depends on SinglePageParser)
- [ ] All tests passing ✅
- [ ] Add to ParserServiceProvider registration

**CraigslistParser:**
- [ ] Write test: parse search results page
- [ ] Write test: parse full listing details
- [ ] Write test: extract price, location, images
- [ ] Write test: handle IP blocking detection
- [ ] Write test: pagination support (120 items per page)
- [ ] Write test: geographic coordinates extraction
- [ ] Write test: multiple image support
- [ ] Write test: respect rate limit (1 req/sec)
- [ ] Implement CraigslistParser
- [ ] All tests passing ✅
- [ ] Add to ParserServiceProvider registration

### Phase 3: API & Documentation (TDD)

**ParserController:**
- [ ] Write test: GET /api/parsers (list all parsers)
- [ ] Write test: GET /api/parsers/{parser} (show parser details)
- [ ] Write test: POST /api/parsers/parse (execute parsing)
- [ ] Write test: GET /api/parsers/{parser}/types (show supported types)
- [ ] Write test: authentication required (401 if not authenticated)
- [ ] Write test: validation errors (422 for invalid input)
- [ ] Write test: parser not found (404)
- [ ] Implement ParserController with methods: index, show, parse, types
- [ ] Add OpenAPI annotations to all methods
- [ ] All tests passing ✅

**API Resources:**
- [ ] Create ParserResource for parser details
- [ ] Create ParserCollectionResource for parser list
- [ ] Create ParseResultResource for parse results
- [ ] Test resource transformations

**Validation:**
- [ ] Create ExecuteParserRequest FormRequest
- [ ] Validate required fields: parser, source, type
- [ ] Validate optional fields: keywords, options, limit, offset, filters
- [ ] Custom validation rules per parser type

**Routes:**
- [ ] Update routes/api.php with parser routes
- [ ] Apply auth:sanctum middleware
- [ ] Rate limiting middleware

**Documentation:**
- [ ] Update docs/API.md with parser endpoints
- [ ] Add OpenAPI schemas for ParseRequestDTO
- [ ] Add OpenAPI schemas for ParseResultDTO
- [ ] Document authentication flows
- [ ] Generate Swagger docs: `sail artisan l5-swagger:generate`
- [ ] Verify docs at https://dev.parser2.local/api/documentation

### Phase 4: Integration & Polish

**Integration Tests:**
- [ ] Write test: full parse flow (request → ParserManager → parser → result)
- [ ] Write test: error handling end-to-end (network error → error response)
- [ ] Write test: rate limiting integration (RateLimiter → HttpClient → parser)
- [ ] Write test: concurrent parser execution (thread safety)
- [ ] All integration tests passing ✅

**Performance:**
- [ ] Benchmark: measure operations per minute (target >100 ops/min)
- [ ] Optimize: identify bottlenecks with profiler
- [ ] Cache: implement caching for repeated requests

**Error Handling:**
- [ ] Structured logging for all parser failures
- [ ] Exception handling: ParserException, HttpException, ValidationException
- [ ] User-friendly error messages in API responses

**Rate Limiting:**
- [ ] Configure per-parser rate limits in config/parser.php
- [ ] Enforce rate limits via RateLimiter
- [ ] Return 429 Too Many Requests when limit exceeded

**Final Verification:**
- [ ] All tests passing (>80% coverage)
- [ ] All parsers registered in ParserServiceProvider
- [ ] OpenAPI documentation complete
- [ ] Performance benchmarks met (>100 ops/min)
- [ ] Code formatted with Pint: `sail bin pint`
- [ ] No linting errors

---

## Technical Decisions Log

### 2025-11-08 - Use parser v1 Architecture as Foundation
**Context:** parser2 needs proven patterns for 8 different parsers
**Considered:**
- Option 1: Build from scratch with modern patterns
- Option 2: Adapt v1 architecture with v2 improvements (DTOs, OpenAPI)
**Decision:** Adapt v1 architecture with v2 improvements
**Rationale:**
- v1 has working parsers in production (battle-tested)
- AbstractParser pattern is solid and extensible
- HttpClient retry logic handles edge cases well
- Can focus on improvements (DTOs, OpenAPI) rather than reinventing wheel
- Reduces risk of missing edge cases already handled in v1
**ADR Created:** Will add to docs/DECISIONS.md as ADR-007

### 2025-11-08 - Exclude YouTubeParser from MVP
**Context:** YouTubeParser requires YouTube Data API v3 key
**Considered:**
- Include with API key requirement
- Exclude and focus on standalone parsers
**Decision:** Exclude from standalone parser implementation
**Rationale:**
- Not standalone (requires API key configuration)
- API keys add complexity (storage, rotation, quota management)
- 8 other parsers provide sufficient MVP value
- Can add in future iteration with proper API key management system
**ADR Created:** Not significant enough for ADR (noted in issue)

### 2025-11-08 - Phase Parsers by Complexity
**Context:** 8 parsers can't all be built simultaneously
**Considered:**
- Alphabetical order
- By popularity/usage
- By complexity (simple → complex)
**Decision:** P1 (simple: Feeds, Reddit, Single) → P2 (medium: Telegram, Medium, Bing) → P3 (complex: Multi, Craigslist)
**Rationale:**
- Quick wins with P1 parsers (build momentum)
- Learn patterns and establish best practices with simple parsers
- Apply learnings to more complex parsers
- P3 parsers (Multi, Craigslist) can leverage P1/P2 code (e.g., MultiUrlParser uses SinglePageParser)
**ADR Created:** Not significant enough for ADR (implementation detail)

---

## Testing Strategy

**Based on testing-standards.md and CLAUDE.md requirements:**

### Coverage Requirements
- **Target:** >80% (from CLAUDE.md Testing Requirements)
- **Current:** 0% (no parsers implemented yet)
- **Track:** After each phase, run `sail artisan test --coverage`

### Test Organization
```
tests/
├── Unit/
│   ├── DTOs/
│   │   └── Parser/
│   │       ├── ParseRequestDTOTest.php
│   │       └── ParseResultDTOTest.php
│   └── Services/
│       └── Parser/
│           ├── AbstractParserTest.php
│           ├── ParserManagerTest.php
│           ├── Support/
│           │   ├── HttpClientTest.php
│           │   ├── ContentExtractorTest.php
│           │   └── RateLimiterTest.php
│           └── Parsers/
│               ├── FeedsParserTest.php
│               ├── RedditParserTest.php
│               ├── SinglePageParserTest.php
│               ├── TelegramParserTest.php
│               ├── MediumParserTest.php
│               ├── BingSearchParserTest.php
│               ├── MultiUrlParserTest.php
│               └── CraigslistParserTest.php
└── Feature/
    └── Api/
        └── ParserApiTest.php
```

### Test Commands (from CLAUDE.md)
```bash
# Start Sail (required before testing)
sail up -d

# Run all tests
sail artisan test

# Run specific parser tests
sail artisan test --filter=FeedsParserTest
sail artisan test --filter=ParserApiTest

# Check coverage
sail artisan test --coverage

# Run tests for specific phase
sail artisan test tests/Unit/Services/Parser/Support      # Phase 1 support
sail artisan test tests/Unit/Services/Parser/Parsers      # Phase 2 parsers
sail artisan test tests/Feature/Api/ParserApiTest         # Phase 3 API
```

### Test Data & Fixtures

**From parser v1 config (use as fixtures):**
```php
// Test URLs for each parser
'feeds' => [
    'test_urls' => [
        'https://hnrss.org/frontpage',
        'https://www.reddit.com/.rss',
    ]
],
'telegram' => [
    'test_urls' => [
        'https://t.me/s/durov',
        'https://t.me/s/telegram',
    ]
],
'reddit' => [
    'test_urls' => [
        'https://www.reddit.com/r/programming.json',
        'https://www.reddit.com/r/laravel.json',
    ]
],
// ... etc
```

**VCR Pattern for External API Calls:**
- Use HTTP fixtures to record/replay external API responses
- Ensures tests are deterministic and fast
- Avoids hitting rate limits during testing

---

## Code Review Checklist

**Before marking as complete, verify against:**

### From CLAUDE.md Do's and Don'ts

**DO:**
- [x] ✅ Use DTOs for all service-to-service communication
- [x] ✅ Add OpenAPI annotations to all API endpoints
- [x] ✅ Follow service layer pattern for business logic
- [x] ✅ Use dependency injection over direct instantiation
- [x] ✅ Write descriptive commit messages (see ~/.claude/standards/git.md)
- [x] ✅ Update documentation as you code
- [x] ✅ Check parser v1 for reference patterns
- [x] ✅ Run tests before committing
- [x] ✅ Use readonly DTOs with named constructor parameters

**DON'T:**
- [x] ❌ Pass arrays between services (use DTOs instead)
- [x] ❌ Put business logic in controllers
- [x] ❌ Skip OpenAPI annotations on API endpoints
- [x] ❌ Copy-paste without understanding
- [x] ❌ Skip tests to save time
- [x] ❌ Use magic numbers or strings
- [x] ❌ Commit directly to master branch
- [x] ❌ Leave TODO comments without creating issues

### From Project Standards

**Backend Standards:**
- [ ] Controllers are thin (ParserController only handles HTTP concerns)
- [ ] Business logic in services (all parsers extend AbstractParser)
- [ ] DTOs used for data transfer (ParseRequestDTO, ParseResultDTO)
- [ ] Dependency injection used (ParserManager injected into controller)
- [ ] Naming follows conventions (FeedsParser, not FeedParser or FeedsService)

**Testing Standards:**
- [ ] >80% code coverage achieved
- [ ] Unit tests for all services and DTOs
- [ ] Feature tests for all API endpoints
- [ ] Tests are deterministic (use fixtures, not live APIs)
- [ ] Test naming follows pattern: {ClassName}Test.php

**Code Quality:**
- [ ] Code formatted with Pint: `sail bin pint`
- [ ] No linting errors
- [ ] No commented-out code
- [ ] No debug statements (dd(), dump(), var_dump())
- [ ] All TODO comments have corresponding GitHub issues

### From Git Workflow (git.md)

- [ ] Commits follow format: `type: subject`
  - Examples: `feat: add feeds parser`, `test: add feeds parser tests`, `docs: update API.md with parser endpoints`
- [ ] Working on dev branch (not master)
- [ ] Ready to merge to master after PR approval
- [ ] No merge commits (use rebase if needed)

---

## Documentation Updates Required

Track documentation that needs updating:

- [ ] **docs/API.md** - Add parser endpoints section
  - GET /api/parsers
  - GET /api/parsers/{parser}
  - POST /api/parsers/parse
  - GET /api/parsers/{parser}/types
  - Request/response examples
  - Authentication requirements

- [ ] **docs/TODO.md** - Mark parser implementation tasks complete
  - ✅ Define core DTO structure (ParseRequestDTO, ParseResultDTO)
  - ✅ Setup service layer structure (ParserManager + parsers)
  - ✅ Implement ParsingService (via parsers)
  - Update sprint metrics (DTOs Created, Services Created, API Endpoints)

- [ ] **docs/DECISIONS.md** - Add ADR-007
  - Title: "Adapt Parser v1 Architecture for Parser2"
  - Document decision to use v1 patterns with v2 improvements
  - Rationale: battle-tested, focus on improvements not reinvention

- [ ] **CHANGELOG.md** - Document new parser functionality
  - [0.2.0] - 2025-11-XX
  - Added: 8 standalone parsers (Feeds, Reddit, Single, Telegram, Medium, Bing, Multi, Craigslist)
  - Added: Parser API endpoints (list, show, parse, types)
  - Added: ParseRequestDTO and ParseResultDTO
  - Added: ParserManager service for parser registration/invocation

- [ ] **config/parser.php** - Create complete parser configuration
  - Configuration for all 8 parsers
  - Rate limits per parser
  - Test URLs for fixtures
  - Enable/disable flags

---

## Completion Criteria

**From docs/PLAN.md Definition of Done:**

- [ ] Code passes all tests (>80% coverage) ✅
- [ ] Code reviewed and approved ✅
- [ ] Code formatted with Pint: `sail bin pint` ✅
- [ ] OpenAPI documentation complete and validated ✅
- [ ] Merged to master branch via dev ✅
- [ ] Performance benchmarks met (>100 ops/min) ✅
- [ ] All 8 parsers functional and tested ✅
- [ ] All documentation updated (API.md, TODO.md, DECISIONS.md, CHANGELOG.md) ✅

---

## PR Preparation

**PR Title:** Implement Parser2 Architecture: 8 Standalone Parsers with DTO-based Service Layer

**PR Branch:** dev → master (following git.md)

**PR Description Template:**
```markdown
Closes #1

## Summary

Implements complete parser2 architecture with 8 standalone parsers based on parser v1:
- **Foundation:** AbstractParser, ParserManager, HttpClient, ContentExtractor, RateLimiter, DTOs
- **P1 Parsers:** FeedsParser, RedditParser, SinglePageParser
- **P2 Parsers:** TelegramParser, MediumParser, BingSearchParser
- **P3 Parsers:** MultiUrlParser, CraigslistParser
- **API:** ParserController with 4 endpoints, OpenAPI annotations
- **Tests:** >80% coverage (unit + feature)

## Alignment

- **Implements MVP feature from docs/PLAN.md:** Core Parsing Service with DTOs
- **Follows pattern from CLAUDE.md:** Service Layer + DTOs + API Resources
- **Respects ADR from docs/DECISIONS.md:** ADR-003 (DTOs), ADR-006 (Pest), ADR-007 (Parser v1 Architecture)

## Testing

- **Coverage:** XX% (target >80%)
- **All tests passing:** ✅
- **Test commands:**
  ```bash
  sail artisan test
  sail artisan test --coverage
  ```

## How to Test

1. Start Sail containers: `sail up -d`
2. Run migrations: `sail artisan migrate`
3. Access Swagger docs: https://dev.parser2.local/api/documentation
4. Test parser endpoint:
   ```bash
   curl -X POST https://dev.parser2.local/api/parsers/parse \
     -H "Authorization: Bearer {token}" \
     -H "Content-Type: application/json" \
     -d '{"parser":"feeds","source":"https://hnrss.org/frontpage","type":"rss"}'
   ```

## API Endpoints Added

- `GET /api/parsers` - List all parsers
- `GET /api/parsers/{parser}` - Show parser details
- `POST /api/parsers/parse` - Execute parsing
- `GET /api/parsers/{parser}/types` - Show supported types

## Documentation Updated

- [x] docs/API.md - Parser endpoints and examples
- [x] docs/TODO.md - Marked parser tasks complete
- [x] docs/DECISIONS.md - Added ADR-007
- [x] CHANGELOG.md - Version 0.2.0 with parser features
- [x] config/parser.php - Complete parser configuration

## Performance

- **Benchmarked:** XX operations per minute (target >100)
- **Optimizations:** HTTP client connection pooling, rate limiting, caching

## Checklist

- [ ] Follows project standards (.claude/standards/)
- [ ] Meets Definition of Done (docs/PLAN.md)
- [ ] No items from CLAUDE.md Don'ts
- [ ] Commit format correct (CONTRIBUTING.md)
- [ ] All tests passing (>80% coverage)
- [ ] OpenAPI docs generated and validated
- [ ] Code formatted with Pint
```

---

## Post-Implementation Review

### Documentation Sync
- [ ] All project docs remain accurate after parser implementation
- [ ] New patterns documented if created (e.g., parser registration pattern)
- [ ] Decisions recorded in DECISIONS.md (ADR-007)
- [ ] API.md updated with all parser endpoints

### Lessons for Future Issues

**What patterns from this implementation can be reused?**
- AbstractParser pattern for other extensible services
- DTO pattern (readonly, fromArray, toArray) for all service communication
- HttpClient with retry logic for other HTTP-based features
- OpenAPI annotation pattern for future API endpoints

**What should be added to standards files?**
- Parser service pattern (extend AbstractParser, register in provider)
- HTTP client retry pattern (exponential backoff, retryable status codes)
- Rate limiting pattern (per-service rate limits)

**What ADR might be needed in future?**
- ADR-008: Parser Plugin System (if we add custom parsers in future)
- ADR-009: Caching Strategy for Parser Results
- ADR-010: Parser Result Storage vs. Transient (if we add persistence)

**What went well?**
- TDD approach ensured high coverage
- Phased implementation (P1→P2→P3) provided quick wins
- Parser v1 reference saved significant development time

**What could be improved?**
- Consider abstracting HTTP client to support different HTTP libraries
- Rate limiting could be more sophisticated (sliding window vs. fixed window)
- Add parser health checks (connectivity, rate limit status)

---

## Notes

- **LARGEST FEATURE** for parser2 MVP - validates entire architecture
- Can be broken into smaller sub-issues if needed (one per phase)
- Parser v1 codebase at `/home/null/misc/apps/personal/parser` provides battle-tested patterns
- **CRITICAL:** Focus on TDD - write tests first, then implement
- Sail containers MUST be running for tests: `sail up -d`
- After implementation, this becomes reference for future service layer work

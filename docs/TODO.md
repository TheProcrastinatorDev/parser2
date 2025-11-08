# Sprint Task Tracker

**Project:** parser2
**Last Updated:** 2025-11-08 (Audit completed - aligned with actual project state)

<!--
PRIORITY LEVELS:
P0 🔴 Critical - Blocking other work, do immediately
P1 🟡 Important - Core features, do this sprint
P2 🟢 Normal - Should do soon, not urgent
PX ⭐ Exciting - Fun tasks for motivation

TAGS:
#setup #feature #bug #test #docs #refactor #perf #security #ux
-->

## Current Sprint: Foundation & Architecture

**Goal:** Establish DTO-based architecture and core services with OpenAPI documentation

**🔍 Audit Summary (2025-11-08):**
This TODO.md has been audited and updated to reflect the actual project state. Key findings:
- ✅ **5 foundation tasks completed:** Documentation, L5-Swagger, Debugbar, OpenAPI base, Health endpoint
- 🔴 **Core architecture not started:** 0 DTOs, 0 Services, 0 business migrations
- 🔴 **No business logic APIs:** Only /api/health exists (campaign/source/results not built)
- ⚠️ **Dev environment needs verification:** Sail containers not running

**Next Priority:** Start Sail containers (P0) → Begin DTO structure (P1) → Database migrations (P1)

---

## 🔴 P0 - Critical (Active Now)

**Note:** These tasks verify the development environment is functional before starting feature work.

- [x] ~~Review project documentation structure (.claude/, docs/, CLAUDE.md)~~ `#setup` ✓ Complete
- [ ] Start Sail containers: `sail up -d` `#setup`
  - **Status:** Containers not currently running (verified via `sail ps`)
- [ ] Run existing tests to verify setup: `sail artisan test` `#test`
  - **Depends on:** Sail containers running
- [ ] Verify application accessible at https://dev.parser2.local `#setup`
  - **Status:** Traefik running but /api/health returns 404
  - **Action needed:** Investigate HealthController registration or start containers

---

## 🟡 P1 - Important (This Sprint)

### Parser Architecture Implementation

- [ ] **[#1](https://github.com/TheProcrastinatorDev/parser2/issues/1): Implement Parser2 Architecture: 8 Standalone Parsers with DTO-based Service Layer** `#feature` `#parser` `#architecture`
  - **Status:** 🔴 Not Started
  - **Scratchpad:** `.scratchpads/1-parser-architecture-8-standalone-parsers.md`
  - **Estimated:** 10-16 days (2-3 weeks)
  - **Dependencies:** Sail containers running (P0)
  - **Description:** Complete parser infrastructure with Foundation (AbstractParser, ParserManager, DTOs) + 8 standalone parsers (Feeds, Reddit, Single, Telegram, Medium, Bing, Multi, Craigslist) + API endpoints

### Backend Architecture Setup

- [ ] Define core DTO structure in `app/DTOs/` `#setup` `#feature`
  - **Note:** Covered by Issue #1 (ParseRequest, ParseResult DTOs)
  - [ ] Create `ParsingConfigDTO`
  - [ ] Create `ParsingResultDTO`
  - [ ] Create `NormalizedDataDTO`
  - [ ] Create `CategorizationDTO`
  - [ ] Create base `BaseDTO` abstract class (if needed)

- [ ] Setup service layer structure in `app/Services/` `#setup`
  - **Note:** Parser services covered by Issue #1 (ParserManager + 8 parsers)
  - [ ] Create `ParsingService` with DTO integration (blocked by Issue #1)
  - [ ] Create `DataNormalizationService`
  - [ ] Create `CategorizationService`
  - [ ] Create `ExportService` (basic structure)

- [ ] Database schema migration from parser v1 `#setup` `#feature`
  - [ ] Review parser v1 migrations in `/home/null/misc/apps/personal/parser/database/migrations`
  - [ ] Create `parsing_campaigns` migration
  - [ ] Create `parsing_sources` migration
  - [ ] Create `parsing_results` migration
  - [ ] Create `normalized_data` migration (new table)
  - [ ] Create `categories` migration (new table)
  - [ ] Create `category_result` pivot migration

### Core Features (MVP)

- [ ] Implement ParsingService `#feature`
  - [ ] Accept ParsingConfigDTO
  - [ ] Execute parser based on type
  - [ ] Return ParsingResultDTO
  - [ ] Store result in database

- [ ] Implement DataNormalizationService `#feature`
  - [ ] Accept raw parsing data
  - [ ] Normalize to standard schema
  - [ ] Return NormalizedDataDTO
  - [ ] Persist normalized data

- [ ] Implement CategorizationService `#feature`
  - [ ] Accept NormalizedDataDTO
  - [ ] Apply categorization rules
  - [ ] Return categories
  - [ ] Link categories to results

- [ ] Create Campaign Management API `#feature`
  - [ ] POST /api/campaigns - Create campaign
  - [ ] GET /api/campaigns - List campaigns
  - [ ] GET /api/campaigns/{id} - Show campaign
  - [ ] PUT /api/campaigns/{id} - Update campaign
  - [ ] DELETE /api/campaigns/{id} - Delete campaign
  - [ ] POST /api/campaigns/{id}/execute - Execute campaign

- [ ] Create Source Management API `#feature`
  - [ ] CRUD endpoints for parsing sources
  - [ ] Source validation by parser type
  - [ ] Active/inactive toggling

- [ ] Create Results API `#feature`
  - [ ] GET /api/results - List with filtering
  - [ ] GET /api/results/{id} - Show single result
  - [ ] GET /api/results/export - Export results

### OpenAPI/Swagger Documentation

- [ ] Add OpenAPI annotations to all API endpoints `#docs`
  - [ ] Campaign endpoints annotations
  - [ ] Source endpoints annotations
  - [ ] Results endpoints annotations
  - [ ] Parser endpoints annotations
  - [ ] Authentication endpoints annotations

- [ ] Document request/response schemas `#docs`
  - [ ] Campaign schemas
  - [ ] Source schemas
  - [ ] Result schemas
  - [ ] DTO schemas
  - [ ] Error response schemas

### Testing & Documentation

- [ ] Write tests for DTO classes `#test`
  - [ ] Test fromArray() factory methods
  - [ ] Test toArray() serialization
  - [ ] Test type safety

- [ ] Write tests for ParsingService `#test`
  - [ ] Test successful parsing
  - [ ] Test error handling
  - [ ] Test DTO transformations

- [ ] Write tests for NormalizationService `#test`
  - [ ] Test data normalization
  - [ ] Test various input formats
  - [ ] Test edge cases

- [ ] Write tests for CategorizationService `#test`
  - [ ] Test categorization logic
  - [ ] Test category assignment

- [ ] Write API feature tests `#test`
  - [ ] Campaign API tests
  - [ ] Source API tests
  - [ ] Results API tests
  - [ ] Authentication tests

- [ ] Update docs/API.md with endpoint documentation `#docs`

---

## 🟢 P2 - Normal (Backlog)

### Technical Debt

- [ ] Refactor any v1 legacy patterns to v2 standards `#refactor`
- [ ] Optimize database queries with eager loading `#perf`
- [ ] Add database indexes for common queries `#perf`
- [ ] Review and optimize DTO transformations `#perf`

### Features

- [ ] Advanced result filtering (by date, parser type, category) `#feature`
- [ ] Full-text search across parsed content `#feature`
- [ ] Scheduled campaign execution `#feature`
- [ ] Parser performance metrics `#feature`
- [ ] Result deduplication `#feature`

### Infrastructure

- [ ] Setup CI/CD pipeline with GitHub Actions `#setup`
- [ ] Configure monitoring and logging `#setup`
- [ ] Add rate limiting to API endpoints `#security`
- [ ] Setup backup strategy for database `#setup`

### Frontend

- [ ] Create campaign management UI `#feature` `#ux`
- [ ] Create source configuration UI `#feature` `#ux`
- [ ] Create results viewing UI with filtering `#feature` `#ux`
- [ ] Create parser status dashboard `#feature` `#ux`

---

## ⭐ PX - Exciting (Motivation Fuel)

- [ ] Build interactive API playground in Swagger UI `#docs` `#ux`
- [ ] Create real-time parsing status dashboard `#feature` `#ux`
- [ ] Implement webhook notifications `#feature`
- [ ] Add parsing analytics with charts `#feature` `#ux`
- [ ] Build parser plugin system for custom parsers `#feature`

---

## ✅ Completed (This Sprint)

- [x] ~~Create project documentation structure~~ `#setup` ✓ 2025-11-08
- [x] ~~Install and configure L5-Swagger package~~ `#setup` `#docs` ✓ 2025-11-08
- [x] ~~Install Laravel Debugbar~~ `#setup` ✓ 2025-11-08
- [x] ~~Add OpenAPI base annotations to Controller~~ `#docs` ✓ 2025-11-08
- [x] ~~Create example Health Check API endpoint~~ `#feature` ✓ 2025-11-08

---

## 📝 Notes & Blockers

### Blockers

- Database schema needs finalization before migrations
- DTO pattern needs to be standardized before implementing all services

### Questions

- Should we support backward compatibility with parser v1 API?
- What categorization algorithm should we use initially?
- Export format requirements (JSON, CSV, XML)?

### Quick Notes

**Reference Implementation:**
- Parser v1 at `/home/null/misc/apps/personal/parser`
- Check parser v1 for patterns, apply v2 improvements

**Development Workflow:**
1. Start Sail: `sail up -d`
2. Run dev environment: `sail composer dev`
3. Access: https://dev.parser2.local
4. Run tests: `sail artisan test`
5. Format code: `sail bin pint` (PHP), `sail npm run lint` (JS)

**Commit Format (from ~/.claude/standards/git.md):**
```bash
feat: add normalization service with DTO support
fix: resolve parser timeout in categorization
test: add DTO transformation tests
docs: update API documentation with OpenAPI schemas
refactor: extract DTO base class
chore: install l5-swagger package
```

**DTO Best Practices:**
- Use readonly classes
- Named constructor parameters
- Static fromArray() factory
- toArray() for serialization
- Type-safe properties

**Swagger/OpenAPI Documentation:**
- Access: https://dev.parser2.local/api/documentation
- Auto-regenerates in development (L5_SWAGGER_GENERATE_ALWAYS=true)
- Add @OA annotations to all new API endpoints
- Regenerate manually: `sail artisan l5-swagger:generate`
- **IMPORTANT:** Always update Swagger docs when changing API endpoints, request/response schemas, or DTOs
- Example Health Check endpoint: `GET /api/health`

---

## 📊 Sprint Metrics

**Last Updated:** 2025-11-08
**Last Audit:** 2025-11-08

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| Tasks Completed | - | 6 | ✅ Foundation complete |
| Issues Created | - | 1 | ✅ Issue #1 (Parser Architecture) |
| Test Coverage | >80% | N/A | ⚠️ No services to test yet |
| API Endpoints | 15+ | 1 | 🔴 Only /api/health exists |
| DTOs Created | 5+ | 0 | 🔴 Not started (Issue #1 will add 2+) |
| Services Created | 4+ | 0 | 🔴 Not started (Issue #1 will add 9+) |
| Database Migrations | 6+ | 4 | 🟡 Only Laravel defaults |

**Key Insights:**
- ✅ **Foundation solid:** L5-Swagger installed, base OpenAPI annotations added, documentation complete
- ✅ **Issue #1 created:** Comprehensive parser architecture implementation plan (10-16 days)
- 🔴 **Zero business logic:** No DTOs, services, or domain-specific migrations yet
- 🔴 **API incomplete:** Only health check endpoint exists, no campaign/source/results APIs
- ⚠️ **Environment not verified:** Sail containers not running, can't confirm app works end-to-end

**Next Actions:**
1. Start Sail containers (P0) - Unblocks Issue #1
2. Begin Issue #1 Phase 1 (Foundation) - AbstractParser, ParserManager, DTOs
3. Verify tests pass with `sail artisan test`

---

## 🔄 Recurring Tasks

- [ ] Update dependencies weekly: `sail composer update` & `sail npm update`
- [ ] Review and update documentation weekly
- [ ] Run full test suite before merging to master
- [ ] Review parser v1 for additional patterns to adopt
- [ ] Update CHANGELOG.md for user-facing changes
- [ ] Regenerate Swagger docs after API changes: `sail artisan l5-swagger:generate`

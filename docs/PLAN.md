# Project Plan: parser2

**Version:** 0.1.0
**Last Updated:** 2025-11-08
**Status:** Initial Development

---

## Executive Summary

**parser2** is a full-stack web application that provides advanced parsing, processing, normalization, and categorization of data from multiple sources. It builds upon the foundation of the parser v1 project with significant improvements: better API consistency through OpenAPI/Swagger documentation, enhanced architecture with DTOs and service layer patterns, and additional side services for comprehensive data processing. The system targets developers and organizations needing automated content extraction and analysis from diverse data sources.

**Key Differentiator:** Improved architecture with type-safe DTOs, comprehensive Swagger API documentation, and better service layer separation compared to v1.

---

## Problem Statement

### Primary Problem

**User Pain Point:** Organizations need to parse, normalize, and categorize data from multiple sources but lack a consistent, well-documented API to automate this process.

**Current Alternative:** Using the parser v1 system or separate tools for each data source, leading to inconsistent APIs, poor documentation, and fragmented data processing.

**Why Current Solution Fails:**
- Inconsistent API responses across different parsers
- Lack of comprehensive API documentation (Swagger/OpenAPI)
- No standardized data transfer between services
- Limited data normalization and categorization capabilities

### Success Metrics

- [ ] All core parsers migrated with DTO-based architecture
- [ ] Complete OpenAPI/Swagger documentation for all API endpoints
- [ ] Data normalization and categorization services operational
- [ ] >80% test coverage on critical services
- [ ] Consistent API response format across all endpoints
- [ ] Process 100+ parsing operations per minute

---

## MVP Features (MoSCoW)

### Must Have (P0/P1)

1. **Core Parsing Service with DTOs**
   - User can submit parsing requests via RESTful API
   - System processes data using parser-specific services
   - Data is transferred between services using type-safe DTOs
   - Results are stored in normalized database structure

2. **Data Normalization Service**
   - System accepts raw parsed data (various formats)
   - Service normalizes data to standard schema
   - Normalized data is returned via DTO
   - Normalized results are persisted to database

3. **Data Categorization Service**
   - System categorizes parsed content by type/topic
   - Service uses configurable categorization rules
   - Categories are applied via DTO-based processing
   - Categorized data is queryable via API

4. **OpenAPI/Swagger Documentation**
   - All API endpoints have Swagger annotations
   - Interactive API documentation accessible via /api/documentation
   - Request/response schemas fully documented
   - Authentication flows documented

5. **Parser Management API**
   - CRUD operations for parsing campaigns
   - Source configuration management
   - Parser execution endpoints
   - User-scoped access control

### Should Have (Important)

- **Advanced Result Filtering** - Filter by parser type, date range, categories
- **Export Functionality** - Export results to JSON, CSV formats
- **Parser Performance Metrics** - Track parsing success/failure rates
- **Scheduled Parsing** - Cron-based campaign execution
- **Full-Text Search** - Search across parsed content

### Could Have (Nice to Have)

- **Webhook Notifications** - Notify external systems on parsing events
- **Result Deduplication** - Automatically detect duplicate content
- **Rate Limiting** - Per-parser and per-user rate limits
- **Analytics Dashboard** - Visual parsing performance analytics
- **Custom Parser Plugins** - User-extensible parser system

### Won't Have (Out of Scope)

- ❌ Real-time streaming of parsing results
- ❌ Machine learning-based categorization (v1.0)
- ❌ Multi-tenancy with organization structure
- ❌ GraphQL API (REST only for MVP)
- ❌ Mobile applications

---

## Technical Specification

### System Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Vue 3 +       │────▶│  Laravel 12 API  │────▶│  MySQL + Redis  │
│   Inertia.js    │     │  (with DTOs)     │     │                 │
│   TypeScript    │     │                  │     │                 │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                              │
                              ├─ ParsingService
                              ├─ NormalizationService
                              ├─ CategorizationService
                              └─ ExportService
```

### Technology Stack

| Layer | Technology | Justification |
|-------|------------|---------------|
| Backend | Laravel 12 | Detected from existing project; robust framework with excellent tooling |
| Frontend | Vue 3 + Inertia.js + TypeScript | Detected from package.json; modern SPA experience with server-side routing |
| Database | MySQL 8.0 | Detected from docker-compose; reliable relational DB for structured data |
| Cache/Queue | Redis | Detected from docker-compose; essential for job queues and caching |
| API Docs | OpenAPI/Swagger (L5-Swagger) | Improvement over v1; industry standard for API documentation |
| Build Tool | Vite | Detected from package.json; fast builds and HMR |
| CSS Framework | Tailwind CSS 4 | Detected from package.json; utility-first CSS |
| Testing | Pest PHP | Detected from composer.json; expressive testing framework |
| Containerization | Docker (Laravel Sail) | Detected from compose.yaml; consistent development environment |

### Data Model

```
User (1) ────────── (∞) ParsingCampaign
                          │
                          └────── (∞) ParsingSource
                                      │
                                      └────── (∞) ParsingResult
                                                  │
                                                  └─ NormalizedData
                                                  └─ Categories
```

**Core Entities:**

| Entity | Purpose | Key Attributes |
|--------|---------|----------------|
| User | Authentication & ownership | email, password, email_verified_at |
| ParsingCampaign | Orchestrates parsing operations | name, parser_type, configuration, schedule, user_id |
| ParsingSource | Defines data sources | name, parser_type, source_url, config, is_active, user_id |
| ParsingResult | Stores extracted content | content, parsed_data, source_id, campaign_id, status |
| NormalizedData | Normalized content | title, body, author, published_at, metadata, result_id |
| Category | Content categorization | name, type, taxonomy, parent_id |

### API Overview

| Resource | Operations | Authentication | Documentation |
|----------|------------|----------------|---------------|
| /api/campaigns | CRUD + execute | Bearer token | OpenAPI |
| /api/sources | CRUD + toggle | Bearer token | OpenAPI |
| /api/results | Read + filter + export | Bearer token | OpenAPI |
| /api/parsers | List + capabilities | Bearer token | OpenAPI |
| /api/normalize | POST (normalize data) | Bearer token | OpenAPI |
| /api/categorize | POST (categorize) | Bearer token | OpenAPI |

### DTO Architecture

**Key DTOs:**

1. **ParsingConfigDTO** - Parser configuration
2. **ParsingResultDTO** - Raw parsing results
3. **NormalizedDataDTO** - Normalized content
4. **CategorizationDTO** - Categorization metadata
5. **ExportConfigDTO** - Export configuration

**DTO Pattern:**
- Readonly classes with named constructor parameters
- `fromArray()` static factory method
- `toArray()` method for serialization
- Type-safe properties

---

## Timeline & Milestones

### Phase 1: Foundation (Weeks 1-2)

- [x] Project setup with Sail and Traefik
- [x] Documentation structure
- [ ] Database schema migration from v1
- [ ] Core DTO definitions
- [ ] Base service layer structure

### Phase 2: Core Services (Weeks 3-5)

- [ ] ParsingService with DTO integration
- [ ] NormalizationService implementation
- [ ] CategorizationService implementation
- [ ] OpenAPI annotations on all endpoints
- [ ] Service layer tests (>80% coverage)

### Phase 3: Enhancement (Weeks 6-7)

- [ ] Advanced filtering and search
- [ ] Export functionality
- [ ] Performance optimization
- [ ] Frontend UI for parser management
- [ ] Integration tests

### Phase 4: Polish & Deploy (Week 8)

- [ ] Bug fixes from testing
- [ ] Documentation review
- [ ] Deployment configuration
- [ ] Performance benchmarking
- [ ] Launch preparation

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation Strategy |
|------|-------------|--------|-------------------|
| DTO refactoring complexity | Medium | Medium | Start with new services, gradually migrate existing code |
| Performance degradation with DTOs | Low | Medium | Benchmark early, use readonly DTOs, avoid unnecessary transformations |
| Swagger documentation drift | High | Low | Automated tests to validate OpenAPI spec matches actual API |
| Migration from v1 database schema | Medium | High | Keep backward compatibility, use database migrations carefully |
| Service layer over-engineering | Medium | Low | Follow simple patterns from reference project, avoid premature optimization |

---

## Definition of Done

### Feature Complete When:

- [ ] Code passes all tests (unit, feature, integration)
- [ ] OpenAPI annotations added and validated
- [ ] DTOs used for all service-to-service communication
- [ ] Code reviewed and formatted (Pint, ESLint)
- [ ] Documentation updated (code comments, API docs, CHANGELOG)
- [ ] Merged to master branch via dev branch
- [ ] Performance benchmarks met (100+ ops/min)

---

## Dependencies & Blockers

### External Dependencies

- Laravel Sail - Docker environment - ✅ Configured
- Traefik Proxy - HTTPS routing - ✅ Configured
- L5-Swagger - OpenAPI documentation - ⚠️ To be installed
- Parser v1 - Reference implementation - ✅ Available at `/home/null/misc/apps/personal/parser`

### Known Blockers

- [ ] L5-Swagger package installation and configuration
- [ ] Database schema finalization (based on v1 + new requirements)
- [ ] DTO pattern standardization across all services

---

## Reference Implementation

**Parser v1 Location:** `/home/null/misc/apps/personal/parser`

**Key Learnings to Apply:**
- Parser service registration patterns
- Campaign and source management API structure
- Database relationships and schema design
- Test account seeding approach
- Traefik configuration patterns

**Improvements in v2:**
- ✅ DTOs instead of arrays for service communication
- ✅ Comprehensive OpenAPI/Swagger documentation
- ✅ Better service layer separation
- ✅ Enhanced data normalization and categorization
- ✅ More consistent API responses

---

## Notes

- All development on `dev` branch
- Follow commit format: `type: subject` (see ~/.claude/standards/git.md)
- Reference parser v1 for patterns, but apply v2 improvements
- Keep CLAUDE.md updated with architectural decisions
- Update DECISIONS.md for major technical choices

## Issue Plan: Implement Parser2 Architecture – Phase 1 Foundation

- **Issue:** https://github.com/TheProcrastinatorDev/parser2/issues/1
- **Branch:** `1-implement-parser2-architecture`
- **Date:** 2025-11-08

### Context
- Issue #1 requires establishing the full parser2 architecture with DTO-based services and eight standalone parsers.
- The repository currently has no parser services, DTOs, or configuration for parsers.
- Reference implementation lives in parser v1 (not directly accessible here, so patterns are inferred from documentation).

### Current Scope (Phase 1 Focus)
To keep changes reviewable and follow Sandi Metz principles, this pass targets the shared foundation needed by every parser:
1. **DTO Layer**
   - `ParseRequestDTO` and `ParseResultDTO` readonly classes with `fromArray` / `toArray` helpers.
2. **Parser Manager**
   - Service responsible for registering parsers and resolving them by key.
3. **Configuration**
   - `config/parser.php` describing available parsers, rate limits, and HTTP defaults.
4. **Abstract Parser Base**
   - Base class handling configuration access, common validation hooks, and shared execution contract.
5. **Support Services**
   - `HttpClient`, `ContentExtractor`, `RateLimiter` utilities that the parsers will rely on (initial version focuses on deterministic, testable behaviour such as retry/backoff logic and HTML sanitisation stubs).

Later phases (individual parser implementations, API surface, documentation polish) will build on this foundation after the base abstractions are stable.

### Deliverables for Phase 1
- DTO classes and accompanying Pest unit tests.
- ParserManager service with unit coverage for registration, retrieval, duplicate protection, and missing parser handling.
- New `config/parser.php` containing baseline defaults and parser metadata placeholders.
- AbstractParser base class with tests covering configuration loading and contract expectations.
- Support services with targeted unit tests (retry logic, HTML cleaning, rate limiting windows).
- Initial service provider wiring deferred until individual parsers exist.

### Testing Strategy
- Use Pest for all unit coverage (`tests/Unit/DTOs/Parser/...`, `tests/Unit/Services/Parser/...`).
- Mock dependencies where network access would otherwise be required; focus on deterministic unit behaviour.
- Ensure coverage remains >80% for the new foundation code by driving implementation with tests first.

### Risks & Mitigations
- **Scope creep:** Limit this slice to shared foundation; note follow-up work for actual parser implementations and API endpoints.
- **External dependencies:** Avoid real HTTP calls in tests; use fakes/stubs within the HttpClient tests.
- **Design drift:** Keep naming and structure aligned with backend standards to ease future parser additions.

### Next Steps After Phase 1
- Implement P1 parsers (Feeds, Reddit, SinglePage) against the new abstractions.
- Register parsers via a dedicated service provider.
- Build API endpoints (`ParserController`) and associated resources/requests.
- Expand documentation (API docs, ADR updates) once core behaviour is in place.

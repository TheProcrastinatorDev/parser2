# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Documentation structure with CLAUDE.md, PLAN.md, TODO.md, API.md, DECISIONS.md
- Git workflow following master + dev strategy (from ~/.claude/standards/git.md)
- Standards files in .claude/standards/ (backend, frontend, testing)
- Project architecture with DTO pattern
- OpenAPI/Swagger documentation foundation
- Comprehensive README with setup instructions

### Changed

- N/A

### Deprecated

- N/A

### Removed

- N/A

### Fixed

- N/A

### Security

- N/A

## [0.1.0] - 2025-11-08

### Added

- Initial project setup with Laravel 12 + Vue 3 + Inertia.js
- Docker development environment with Laravel Sail
- Traefik reverse proxy configuration (https://dev.parser2.local)
- MySQL 8.0 and Redis services
- TypeScript support for Vue 3 frontend
- Tailwind CSS 4 for styling
- Pest PHP testing framework
- Laravel Fortify authentication
- Project documentation boilerplate
- Development workflow setup
- Git repository initialization with master + dev branches

---

## How to Update This File

When making changes, add them to the **[Unreleased]** section under the appropriate category:

- **Added** for new features
- **Changed** for changes in existing functionality
- **Deprecated** for soon-to-be removed features
- **Removed** for removed features
- **Fixed** for bug fixes
- **Security** for security vulnerability fixes

When releasing a new version:
1. Create a new version heading: `## [X.Y.Z] - YYYY-MM-DD`
2. Move items from [Unreleased] to the new version section
3. Update the version links at the bottom

### Example Entry

```markdown
### Added
- Data normalization service with DTO support `[#123]`
- OpenAPI annotations for Campaign endpoints `[#124]`

### Fixed
- Parser timeout issue in Telegram service `[#125]`
```

Use issue/PR numbers in brackets when available.

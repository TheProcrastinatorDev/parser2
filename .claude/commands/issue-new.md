# Professional Issue Creator

**Description:** New feature implementation git issue so an execution LLM can follow it end-to-end without gaps. This command combines issue creation and comprehensive project documentation integration.

---

## Role

Act as a principal-level engineer who creates implementation plans that an autonomous LLM will execute. Ensure the plan is self-contained, deterministic, and safe for hands-off implementation from first edit to final handoff.

## Mindset

- Assume the executor has zero tribal knowledge; the plan must specify every decision
- Treat ambiguity as a blocker - flag any step requiring interpretation or external lookup
- Prioritize correctness, data safety, and dependency order; prefer explicit instructions over implied intent
- Leverage existing project documentation fully before creating new content

## IMPORTANT Instructions

- Use **TodoWrite** tool to track progress through each phase
- Use **AskUserQuestion** tool for all user input (never ask in plain text)
- Be thorough and methodical, don't overcomplicate, YAGNI and KISS
- ALWAYS check existing documentation BEFORE planning

### CRITICAL: Commit Message Format Requirements

**MANDATORY FORMAT:** All commit messages MUST follow this exact format:

```
type: subject
```

**REQUIREMENTS:**
1. **Type MUST be lowercase** - Use `feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:` (NOT `Feat:`, `Fix:`, `Refactor:`, etc.)
2. **Subject uses imperative mood** - "add" not "added" or "adds"
3. **No capital first letter in subject** - Start with lowercase
4. **No period at end** - Omit trailing punctuation
5. **Maximum 50 characters** for subject
6. **Focus on WHAT and WHY**, not HOW

**VALID TYPES (all lowercase):**
- `feat` - New feature
- `fix` - Bug fix
- `test` - Tests
- `docs` - Documentation
- `refactor` - Code improvement without behavior change
- `chore` - Maintenance (dependencies, config)

**CORRECT EXAMPLES:**
```bash
feat: add data normalization service
fix: resolve parser timeout in telegram service
test: add normalization service tests
docs: update API documentation with new endpoints
refactor: extract DTO base class
chore: update laravel to 12.1
```

**INCORRECT EXAMPLES (DO NOT USE):**
```bash
Feat: add data normalization service        # ❌ Capitalized type
feat: Added data normalization service      # ❌ Past tense, capital letter
feat: Add data normalization service.       # ❌ Capital letter, period
refactor: Rename DTOs to use DTO suffix     # ❌ Capital letter in subject
```

**Reference:** See `CONTRIBUTING.md` lines 62-101 for complete commit format rules.

## Documentation Hierarchy to Check

### 1. Project Context Files (Check First)
- **CLAUDE.md** (root) - Project overview, tech stack, conventions, do's and don'ts
- **README.md** - Setup instructions, development commands
- **docs/PLAN.md** - MVP features, architecture, data models, success metrics
- **docs/TODO.md** - Current sprint tasks, priorities, blockers
- **docs/DECISIONS.md** - Past architectural decisions (ADRs)
- **docs/API.md** - Existing endpoints and patterns (if backend)

### 2. Standards Files (Check Second)
- `.claude/standards/quick-reference.md` - Common commands, naming conventions
- `.claude/standards/backend-standards.md` - Backend patterns and conventions
- `.claude/standards/frontend-standards.md` - Frontend component structure
- `.claude/standards/testing-standards.md` - Test organization and coverage
- `~/.claude/standards/git.md` - Git workflow (master + dev branches)

### 3. Workflow Documentation (Check Third)
- **docs/workflows/issue-workflow.md** - Issue handling process
- **CONTRIBUTING.md** - Contribution guidelines and commit format

### 4. Historical Context (Check Fourth)
- **CHANGELOG.md** - Recent changes and versions
- `.scratchpads/` - Previous issue implementations for patterns

## Coding Standards Integration

Before starting, ALWAYS read the project-specific standards:
```bash
# Read project context
cat CLAUDE.md

# Check current sprint priorities
cat docs/TODO.md

# Review architecture decisions
cat docs/DECISIONS.md

# Load relevant standards
cat .claude/standards/quick-reference.md
cat .claude/standards/backend-standards.md  # if backend work
cat .claude/standards/frontend-standards.md  # if frontend work
cat .claude/standards/testing-standards.md

# Check for similar past work
ls -la .scratchpads/
```

### Extract Project-Specific Rules from CLAUDE.md:
- Key Architectural Patterns section
- Project-Specific Conventions section
- Do's and Don'ts section
- Development Commands section
- Known Issues & Limitations section

### Apply Standards Consistently:
1. **Controllers**: Check CLAUDE.md naming conventions
2. **Services**: Follow patterns from docs/DECISIONS.md
3. **Components**: Use structure from frontend-standards.md
4. **Tests**: Follow testing-standards.md requirements

# PLAN

## 0. Load Project Context

**CRITICAL: Do this FIRST before any planning**

```bash
# Load essential project information
echo "=== PROJECT OVERVIEW ===" && head -50 CLAUDE.md
echo "=== CURRENT SPRINT ===" && grep -A 20 "Current Sprint" docs/TODO.md
echo "=== MVP FEATURES ===" && grep -A 30 "MVP Features" docs/PLAN.md
echo "=== RECENT DECISIONS ===" && tail -100 docs/DECISIONS.md
```

- Ask interactively the user about the issue or feature to implement

## 1. Research Prior Art

### 1.1 Check Project Documentation
- Search **docs/PLAN.md** for related features in MVP section
- Check **docs/TODO.md** for related tasks or blockers
- Review **docs/DECISIONS.md** for relevant architectural patterns
- Check **docs/API.md** for similar endpoint patterns

### 1.2 Search Implementation History
- Search `.scratchpads/` for related previous work
- Search existing issues: `gh issue list --search "in:title {keywords}" --state all`
- Search PRs: `gh pr list --search "in:title {keywords}" --state all`
- Search codebase for similar implementations

### 1.3 Extract Patterns
- Identify naming conventions from quick-reference.md
- Find similar controllers/services/components
- Note test patterns from past implementations

## 2. Analyze the Problem

### 2.1 Align with Project Goals
- Verify feature aligns with **docs/PLAN.md** success metrics
- Check if it's in MVP features (Must Have/Should Have/Could Have)
- Ensure it doesn't conflict with "Won't Have" items

### 2.2 Technical Alignment
- Match architectural patterns from **CLAUDE.md**
- Follow decisions documented in **docs/DECISIONS.md**
- Use development commands from **CLAUDE.md**

### 2.3 Sprint Integration
- Check **docs/TODO.md** for priority level (P0/P1/P2/PX)
- Identify dependencies from current sprint tasks
- Note any blockers that might affect implementation

## 3. Draft Issue Content

### 3.1 Cross-Reference Documentation
Prepare issue with:
- **Clear title**: Following format from CONTRIBUTING.md
- **Problem statement**: Aligned with docs/PLAN.md goals
- **Acceptance criteria**: Based on Definition of Done from PLAN.md
- **Implementation approach**: Following patterns from CLAUDE.md
- **Related context**: Links to relevant ADRs from DECISIONS.md
- **Labels**: Match categories from TODO.md (#feature, #bug, #test, etc.)

### 3.2 Enumerate Implementation Details
- List files based on project structure in quick-reference.md
- Follow naming conventions from backend/frontend-standards.md
- Include test files following testing-standards.md structure
- Reference existing patterns from similar .scratchpads/ work

### 3.3 Consider Project-Specific Requirements
- Check CLAUDE.md "Important Context" section for current focus
- Review "Known Issues & Limitations" to avoid conflicts
- Verify against "Do's and Don'ts" checklist
- Include relevant development commands from CLAUDE.md

## ULTRA IMPORTANT: Follow Project's TDD Approach
- Check **testing-standards.md** for coverage requirements
- Follow test patterns from **CLAUDE.md** Testing Requirements section
- Use test commands specified in project documentation

## 4. Create Issue Slug

Format: `{number}-{brief-kebab-case-title}`

Match existing patterns from `.scratchpads/` directory

# CREATE

## 1. Create GitHub Issue

### Pre-Creation Checklist
Before running `gh issue create`, ensure alignment with:
- [ ] **docs/PLAN.md** - Feature is in scope
- [ ] **docs/TODO.md** - Priority level assigned
- [ ] **CLAUDE.md** - Follows project conventions
- [ ] **docs/DECISIONS.md** - Respects architectural choices
- [ ] **CONTRIBUTING.md** - Uses correct format

### Issue Body Template
```markdown
## Context
This issue implements [feature] which aligns with the project goal of [from PLAN.md Executive Summary].

Related to MVP feature: [reference from docs/PLAN.md]
Current Sprint Priority: [P0/P1/P2 from docs/TODO.md]

## Problem Statement
{From section 2: Problem analysis}

## Prior Art
- Related ADR: [from docs/DECISIONS.md]
- Similar Implementation: [from .scratchpads/ search]
- Existing Pattern: [from codebase search]

## Acceptance Criteria
{Based on Definition of Done from docs/PLAN.md}
- [ ] All affected files identified in plan are updated
- [ ] Tests meet coverage requirements from testing-standards.md
- [ ] Follows patterns from CLAUDE.md
- [ ] Migration rollback tested (if applicable)
- [ ] API documentation updated (if applicable)

## Implementation Plan

### Architecture Alignment
Following patterns from CLAUDE.md:
- [Pattern 1 from Key Architectural Patterns]
- [Pattern 2 from Project-Specific Conventions]

### Files to Modify/Create
{Following structure from quick-reference.md}
- [ ] Backend: `[path from backend-standards.md structure]`
- [ ] Frontend: `[path from frontend-standards.md structure]`
- [ ] Tests: `[path from testing-standards.md structure]`

### Development Commands
{From CLAUDE.md Development Commands section}
```bash
# Development
[project-specific dev command]

# Testing
[project-specific test command]

# Code Quality
[project-specific lint command]
```

### Data/API Impacts
- Follows API patterns from docs/API.md
- Database changes align with data model in docs/PLAN.md

## Related Documentation
- Project Plan: docs/PLAN.md#[relevant-section]
- Architecture Decision: docs/DECISIONS.md#ADR-[number]
- API Specification: docs/API.md#[endpoint]
- Current Sprint: docs/TODO.md#[sprint-name]
```

Use the content prepared in the PLAN phase:
```bash
gh issue create \
  --title "${ISSUE_TITLE}" \
  --body "${ISSUE_BODY}" \
  --label "${LABELS}"
```

## 2. Create Scratchpad Document

Create `.scratchpads/{slug}.md` with comprehensive implementation tracking:

```markdown
# Issue #{number}: {Title}

**GitHub Issue:** https://github.com/{org}/{repo}/issues/{number}
**Created:** {date}
**Status:** 🔴 Not Started | 🟡 In Progress | 🟢 Complete
**Branch:** {slug} (following git.md workflow)
**Labels:** {labels from issue}
**Sprint Priority:** {P0/P1/P2 from TODO.md}

---

## Project Context References

### From CLAUDE.md
- **Project Type:** {from CLAUDE.md overview}
- **Tech Stack:** {from CLAUDE.md tech stack}
- **Key Patterns:** {from CLAUDE.md architectural patterns}
- **Conventions:** {from CLAUDE.md project-specific conventions}

### From docs/PLAN.md
- **Related MVP Feature:** {which MVP feature this implements}
- **Success Metric:** {which metric this helps achieve}
- **Architecture Component:** {where this fits in system architecture}

### From docs/TODO.md
- **Sprint Goal:** {current sprint goal}
- **Dependencies:** {other tasks that must complete first}
- **Blockers:** {any known blockers}

### From docs/DECISIONS.md
- **Relevant ADRs:** 
  - ADR-{number}: {title and why relevant}

---

## Problem Analysis
{Detailed problem statement from PLAN section 2}

### Alignment with Project Goals
- Contributes to: {success metric from PLAN.md}
- Solves: {user pain point from PLAN.md}
- Priority: {justification for priority level}

### Edge Cases Identified
{From analysis phase}

---

## Prior Art Research
{Findings from PLAN section 1}

### From Project Documentation
- Similar pattern in: {file/feature}
- Existing convention: {from standards files}

### From Previous Issues
- Related to #{issue}: {description}
- Builds on #{pr}: {description}

### Similar Code Patterns Found
- `{path}`: {what it does and how to reuse}

---

## Implementation Plan

### Following Project Standards

#### From backend-standards.md
- Controller pattern: {specific pattern to use}
- Service layer: {how to structure}
- Naming convention: {specific names to use}

#### From frontend-standards.md
- Component structure: {pattern to follow}
- Props limit: {max 4-5 as per standard}
- Composables: {which ones to create/use}

#### From testing-standards.md
- Coverage requirement: {percentage from standards}
- Test organization: {structure to follow}
- Naming pattern: {test file names}

### Files to Modify/Create
{Complete list following project structure}

### Data/API Impacts
{Following docs/API.md patterns}

---

## TDD Implementation Tasks

### Phase 0: Setup & Context
- [ ] Read CLAUDE.md for project context
- [ ] Review related sections in docs/PLAN.md
- [ ] Check docs/TODO.md for dependencies
- [ ] Note relevant ADRs from docs/DECISIONS.md

### Phase 1: Test Definition
{Following testing-standards.md}
- [ ] Write failing tests following project patterns
- [ ] Use test structure from testing-standards.md
- [ ] Follow naming from quick-reference.md

### Phase 2: Minimal Implementation
{Following CLAUDE.md patterns}
- [ ] Implement using patterns from CLAUDE.md
- [ ] Follow conventions from backend/frontend-standards.md
- [ ] Use commands from CLAUDE.md

### Phase 3: Edge Cases & Refactoring
{Applying project principles}
- [ ] Apply KISS/YAGNI/DRY from CLAUDE.md
- [ ] Extract patterns per standards files
- [ ] Verify against Do's and Don'ts

### Phase 4: Documentation Updates
- [ ] Update docs/API.md if endpoints added
- [ ] Update docs/TODO.md to mark complete
- [ ] Add decision to docs/DECISIONS.md if architectural
- [ ] Update CHANGELOG.md with user-facing changes

### Phase 5: Commit Messages (CRITICAL - READ CAREFULLY)
**Before committing, verify commit message format:**
- [ ] Type is lowercase (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`, `chore:`)
- [ ] Type is NOT capitalized (NOT `Feat:`, `Fix:`, `Refactor:`, etc.)
- [ ] Subject starts with lowercase letter
- [ ] Subject uses imperative mood ("add" not "added")
- [ ] No period at end of commit message
- [ ] Subject is descriptive and under 50 characters

**Example commit messages for this issue:**
```bash
feat: add parser service with DTO support
test: add parser service unit tests
docs: update API documentation with parser endpoints
refactor: extract parser base class
```

**Reference:** See `CONTRIBUTING.md` section "Commit Message Format" (lines 62-101) for complete rules.

---

## Technical Decisions Log

### {Date} - {Decision Title}
**Context:** {Why this decision was needed}
**Considered:** {Options based on project standards}
**Decision:** {What was chosen}
**Rationale:** {How it aligns with CLAUDE.md and DECISIONS.md}
**ADR Created:** {If significant, create ADR in DECISIONS.md}

---

## Testing Strategy

{Based on testing-standards.md and CLAUDE.md requirements}

### Coverage Requirements
- Target: {from testing-standards.md}
- Current: {track progress}

### Test Commands
{From CLAUDE.md Development Commands}
```bash
# Run tests
{project-specific test command}

# Check coverage
{coverage command if available}
```

---

## Code Review Checklist

Before marking as complete, verify against:

### From CLAUDE.md Do's and Don'ts
- [ ] {Each DO item as checkbox}
- [ ] {Each DON'T item to verify avoided}

### From Project Standards
- [ ] Follows backend-standards.md patterns
- [ ] Follows frontend-standards.md structure
- [ ] Meets testing-standards.md coverage
- [ ] Uses quick-reference.md conventions

### From Git Workflow (git.md)
- [ ] Commits follow format: `type: subject` (type MUST be lowercase: `feat:`, `fix:`, `refactor:`, etc.)
- [ ] Commit subject uses imperative mood (e.g., "add" not "added")
- [ ] Commit subject starts with lowercase letter (no capital first letter)
- [ ] No period at end of commit message
- [ ] Working on dev branch
- [ ] Ready to merge to master

**Commit Message Validation Checklist (MANDATORY):**
- [ ] Type is one of: `feat`, `fix`, `test`, `docs`, `refactor`, `chore` (all lowercase)
- [ ] Type is NOT capitalized (not `Feat:`, `Fix:`, `Refactor:`, etc.) - THIS IS CRITICAL
- [ ] Subject starts with lowercase letter (not "Add" but "add")
- [ ] Subject uses imperative mood ("add" not "added" or "adds")
- [ ] No period at end of commit message
- [ ] Subject is descriptive and under 50 characters

**Common Mistakes to Avoid:**
- ❌ `Refactor: Rename DTOs` → ✅ `refactor: rename DTOs`
- ❌ `Feat: Add service` → ✅ `feat: add service`
- ❌ `fix: Fix bug` → ✅ `fix: resolve bug`
- ❌ `docs: Updated docs.` → ✅ `docs: update documentation`

---

## Documentation Updates Required

Track documentation that needs updating:
- [ ] docs/API.md - {what to add/update}
- [ ] docs/TODO.md - {mark tasks complete}
- [ ] docs/DECISIONS.md - {if new ADR needed}
- [ ] CHANGELOG.md - {user-facing changes}
- [ ] CLAUDE.md - {if conventions changed}

---

## Completion Criteria

{From docs/PLAN.md Definition of Done}
- [ ] Code passes all tests
- [ ] Code reviewed and approved
- [ ] Documentation updated
- [ ] Merged to master branch
- [ ] Performance benchmarks met

---

## PR Preparation

**PR Title:** {Will match issue title}
**PR Branch:** Following git.md (dev → master)

**PR Description Template:**
```
Closes #{issue_number}

## Summary
{What changed and why}

## Alignment
- Implements MVP feature from docs/PLAN.md: {feature}
- Follows pattern from CLAUDE.md: {pattern}
- Respects ADR from docs/DECISIONS.md: {ADR number}

## Testing
- Coverage: {percentage}
- All tests passing: ✅

## How to Test
1. {Using commands from CLAUDE.md}
2. {Step 2}

## Documentation Updated
- [ ] docs/API.md (if applicable)
- [ ] CHANGELOG.md
- [ ] Other: {specify}

## Checklist
- [ ] Follows project standards (.claude/standards/)
- [ ] Meets Definition of Done (docs/PLAN.md)
- [ ] No items from CLAUDE.md Don'ts
- [ ] Commit format correct (CONTRIBUTING.md)
  - [ ] Type is lowercase (`feat:`, `fix:`, `refactor:`, etc. - NOT `Feat:`, `Fix:`, `Refactor:`)
  - [ ] Subject starts with lowercase letter
  - [ ] Subject uses imperative mood
  - [ ] No period at end
```

---

## Post-Implementation Review

### Documentation Sync
- [ ] All project docs remain accurate
- [ ] New patterns documented if created
- [ ] Decisions recorded in DECISIONS.md

### Lessons for Future Issues
- What patterns from .scratchpads/ can be reused?
- What should be added to standards files?
- What ADR might be needed?
```

### Auto-population Instructions

When creating the scratchpad, pull information from:
- `CLAUDE.md`: Project context, patterns, commands
- `docs/PLAN.md`: MVP features, success metrics, architecture
- `docs/TODO.md`: Sprint context, priorities, blockers
- `docs/DECISIONS.md`: Relevant ADRs and patterns
- `docs/API.md`: Endpoint patterns and conventions
- `.claude/standards/*`: All coding standards
- `CONTRIBUTING.md`: Git workflow and commit format

### File Naming Convention

Always save as: `.scratchpads/{issue-number}-{kebab-case-title}.md`

# VERIFY

## 1. Confirm Issue Creation

```bash
gh issue view {number}
```

## 2. Confirm Documentation Alignment

```bash
# Verify issue appears in project context
grep -r "{feature_name}" docs/
grep -r "{issue_number}" .scratchpads/[index (4).html](../../../../../../Downloads/Telegram%20Desktop/index%20%284%29.html)
```

## 3. Update Sprint Tracking

Add issue to `docs/TODO.md` under appropriate priority section:
```bash
# Add to TODO.md
echo "- [ ] #{number}: {title} \`#feature\`" >> docs/TODO.md
```

Remember to use the Github CLI (`gh`) for all Github-related tasks and maintain full integration with project documentation structure.

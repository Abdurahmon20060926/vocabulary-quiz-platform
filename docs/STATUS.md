# Project Status

**Updated:** 2026-11-26 | **Requirements source:** `quiz-prd-v2.pdf`

## Summary

The project implements the main vocabulary-quiz flows described in the PRD:
Google sign-in, guest play, quiz authoring and publishing, strict and casual
gameplay, voting, Explore and My Plays, creator analytics, and quiz suggestions.
The branch also includes the requested global search and gameplay reliability
improvements.

The existing automated suite, frontend typecheck, and production asset build
have passed. PostgreSQL migrations and owner enforcement were also exercised
against an isolated PostgreSQL 16.15 cluster. Docker Compose execution and the
production image build remain unverified because Docker is unavailable. These
limits are recorded in [POSTGRES_SMOKE_TEST.md](POSTGRES_SMOKE_TEST.md) and
[report.txt](../report.txt).

## Completed

- Google Socialite authentication and guest ownership for play sessions.
- Draft, published, and unpublished quiz lifecycle, including publishing
  validation, duplicate-as-draft, and creator test play.
- Strict server-checked and casual client-checked gameplay with sudden death,
  timing rules, run ownership checks, and transactional result bookkeeping.
- Explore search, filters, and sorting; My Plays; Creator Studio; voting;
  analytics; and the PRD's suggestion scoring rules.
- Regression coverage for authentication, lifecycle, discovery, rate limits,
  ownership, timing, answer validation, and duplicate submissions.
- A follow-up migration that updates the quiz-run owner foreign key and adds
  the PostgreSQL exactly-one-owner check without relying on changes to an
  already-applied create-table migration.
- A manual checklist for browser behavior that is intentionally outside the
  feature-test suite.

## Verification

- `php artisan test --compact`: 46 tests passed, 312 assertions.
- `npm run typecheck`: passed.
- `npm run build`: passed.
- SQLite migration smoke check: fresh migrations, rollback of the new migration,
  and re-application passed.
- PostgreSQL 16.15: fresh migrations, rollback/re-application, existing-run
  upgrade preservation, one-owner/two-owner constraint behavior, and cascade
  deletion passed against an isolated temporary database.
- Browser check: global search navigation and a narrow/mobile layout were
  checked manually.
- Docker Compose workflow and production Docker image build: not run.

## Remaining Work

1. Run the PostgreSQL smoke test in
   [POSTGRES_SMOKE_TEST.md](POSTGRES_SMOKE_TEST.md), including checking for
   existing quiz runs that have zero or two owners before adding the constraint.
2. Build and run the production Compose image on a machine with Docker, then
   validate migrations, HTTPS, Google OAuth callback configuration, logs, and
   backups against the real deployment environment.
3. Perform the remaining manual client checks in
   [MANUAL_TEST_CHECKLIST.md](MANUAL_TEST_CHECKLIST.md), especially keyboard
   controls, touch layout, focus loss, timer/countdown behavior, sound, and
   result screens.
4. Replace the three placeholder game-sound assets when the final audio is
   available.

The production Compose, PHP-FPM, and Nginx configuration is deployment
preparation only; the application has not been deployed or verified in
production.

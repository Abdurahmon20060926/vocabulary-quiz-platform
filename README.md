# QuizVault — Vocabulary Quiz Platform

A gamified vocabulary quiz web app. Anyone can play time-constrained, sudden-death multiple-choice quizzes. Signed-in users can create and publish their own quizzes from a Creator Studio.

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.3)
- **Frontend**: Vue 3.5 + TypeScript + Inertia 3
- **Styling**: Tailwind CSS 4
- **Database**: PostgreSQL (production), SQLite in-memory (tests)
- **Auth**: Google OAuth via Laravel Socialite
- **Build**: Vite 8

## Project Status

The implementation and remaining validation work are summarized in
[docs/STATUS.md](docs/STATUS.md). The detailed plain-English requirements report
is in [report.txt](report.txt). For PostgreSQL migration verification, see
[docs/POSTGRES_SMOKE_TEST.md](docs/POSTGRES_SMOKE_TEST.md).

## Run Locally with Docker

Requirements: Ubuntu/Linux, Docker Engine, and Docker Compose v2. Detailed
beginner-friendly setup and Snap Docker permission troubleshooting are in
[docker.md](docker.md).

On a clean clone, from the repository root:

```bash
cp .env.example .env
# Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to .env.
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Open **http://localhost:8080**. The first run builds the app and PostgreSQL
containers; the database service becomes healthy before the app starts. The
seed command creates five published, listed demo quizzes with both strict and
casual modes.

## Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/).
2. Create or select a project and configure the OAuth consent screen.
3. Under Credentials, create an OAuth Client ID for a Web application.
4. For local development, add `http://localhost:8080` as an Authorized
   JavaScript origin and add this exact Authorized redirect URI:
   `http://localhost:8080/auth/google/callback`.
5. Copy the client ID and secret to `.env`:
   ```
   GOOGLE_CLIENT_ID=xxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxxx
   GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback
   ```

For production, register the production HTTPS origin and callback separately.

## Production Deployment

Production uses Docker Compose with PostgreSQL, PHP-FPM, and Caddy for
automatic HTTPS. Follow the beginner deployment steps in
[docs/GO_LIVE.md](docs/GO_LIVE.md), with additional deployment notes in
[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Running Tests

```bash
docker compose exec app php artisan test --compact
```

The development image includes the Composer development dependencies and runs
tests on SQLite in memory. No external services are needed for the test suite.

## Demo Data

After seeding (`php artisan db:seed`), 5 published quizzes are available:
- Advanced English Vocabulary (casual, 8s)
- Scientific Terminology (strict, 10s)
- French Vocabulary Basics (casual, 7s)
- Medical Terminology (strict, 9s)
- Business English Vocabulary (casual, 8s)

No demo user accounts — sign in with Google to create quizzes. Guest play works without an account.
The demo seeder is idempotent, so `php artisan db:seed` can be run safely again.

## Environment Variables Reference

| Variable | Description | Default |
|----------|-------------|---------|
| `GOOGLE_CLIENT_ID` | Google OAuth app ID | required |
| `GOOGLE_CLIENT_SECRET` | Google OAuth secret | required |
| `GOOGLE_REDIRECT_URI` | OAuth callback URL | `{APP_URL}/auth/google/callback` |
| `QUIZ_GRACE_SECONDS` | Network grace period for strict mode timing | `1.5` |
| `SUGGESTION_W_AUTHOR` | Suggestion scoring: same-author weight | `3` |
| `SUGGESTION_W_STATS` | Suggestion scoring: pass-rate similarity weight | `2` |
| `SUGGESTION_W_VOTES` | Suggestion scoring: upvotes weight | `1` |

# QuizVault — Vocabulary Quiz Platform

A gamified vocabulary quiz web app. Anyone can play time-constrained, sudden-death multiple-choice quizzes. Signed-in users can create and publish their own quizzes from a Creator Studio.

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.3)
- **Frontend**: Vue 3.5 + TypeScript + Inertia 3
- **Styling**: Tailwind CSS 4
- **Database**: PostgreSQL (production), SQLite in-memory (tests)
- **Auth**: Google OAuth via Laravel Socialite
- **Build**: Vite 8

## Local Development Setup

### Prerequisites
- PHP 8.3+ with extensions: pdo_pgsql, pdo_sqlite, mbstring, xml, curl, zip, bcmath
- Composer 2+
- Node.js 18+ and npm
- PostgreSQL 14+

### Steps

```bash
# 1. Clone and install dependencies
git clone <repo-url> quiz
cd quiz
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Edit .env with your DB and Google OAuth credentials:
#    DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
#    GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI

# 4. Run migrations and seed demo data
php artisan migrate
php artisan db:seed

# 5. Build frontend
npm run build
# Or for development with HMR:
npm run dev

# 6. Start server
php artisan serve
```

Visit http://localhost:8000

## Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or use an existing one)
3. Enable the **Google+ API** (or People API)
4. Under Credentials → Create OAuth Client ID → Web application
5. Add Authorized redirect URIs: `https://your-domain.com/auth/google/callback`
6. Copy Client ID and Client Secret to `.env`:
   ```
   GOOGLE_CLIENT_ID=xxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxxx
   GOOGLE_REDIRECT_URI=https://your-domain.com/auth/google/callback
   ```

## Production Deployment

Production uses Docker Compose with PostgreSQL and PHP-FPM, plus host Nginx
for HTTPS termination. Follow the complete fresh-Ubuntu deployment guide in
[docker.md](docker.md).

## Running Tests

```bash
php artisan test
# or
./vendor/bin/pest
```

Tests run on SQLite in-memory. No external services required.

## Demo Data

After seeding (`php artisan db:seed`), 5 published quizzes are available:
- Advanced English Vocabulary (casual, 8s)
- Scientific Terminology (strict, 10s)
- French Vocabulary Basics (casual, 7s)
- Medical Terminology (strict, 9s)
- Business English Vocabulary (casual, 8s)

No demo user accounts — sign in with Google to create quizzes. Guest play works without an account.

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

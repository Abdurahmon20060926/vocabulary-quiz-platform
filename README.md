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

### On a VPS (recommended: DigitalOcean, Hetzner, etc.)

```bash
# Install PHP 8.3, PostgreSQL, Nginx, Node.js

# Clone repo
git clone <repo> /var/www/quizapp
cd /var/www/quizapp

# Configure environment
cp .env.example .env
# Set APP_ENV=production, APP_DEBUG=false, APP_URL=https://yourdomain.com
# Set all DB_ and GOOGLE_ variables
php artisan key:generate

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Database
php artisan migrate --force
php artisan db:seed --force  # optional demo data

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

**Nginx config:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/quizapp/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Railway / Render / Fly.io

Set all environment variables from `.env.example` in the platform dashboard. The build command is:
```
composer install --no-dev && npm ci && npm run build && php artisan migrate --force
```

Start command: `php artisan serve --host=0.0.0.0 --port=$PORT`

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

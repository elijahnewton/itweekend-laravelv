# IT Weekend LMS

A modern **Learning Management System** built for the IT Weekend community. It provides structured courses, markdown-driven content, and a full-featured admin panel — all delivered through a fast, reactive interface.

## Tech Stack.

| Layer | Technology |
|---|---|
| Backend | Laravel 11 (PHP 8.3) |
| Admin Panel | Filament v3 |
| Frontend | Inertia.js + Vue 3 + Tailwind CSS |
| Database | PostgreSQL 16 |
| Cache / Queue | Redis 7 |
| Build Tool | Vite |

## Features

- Course and lesson management with markdown-based content
- Filament v3 admin panel for full content administration
- Reactive SPA frontend powered by Inertia.js and Vue 3
- Background job processing via Redis-backed queues
- Content sync from markdown files (`php artisan content:sync`)
- Legacy data migration from SQLite (`php artisan legacy:migrate`)

---

## Installation.

### Prerequisites

All installation methods require:

- Git
- A copy of the repository: `git clone https://github.com/elijahnewton/itweekend-laravelv.git && cd itweekend-laravelv`

---

### Option 1 — Docker (recommended)

The easiest way to run the full stack locally. Docker Compose starts the app, a background worker, PostgreSQL, and Redis automatically.

**Requirements:** [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Compose plugin on Linux)

```bash
# 1. Copy the environment file
cp .env.example .env

# 2. Build and start all services
docker compose up --build
```

The application will be available at **http://localhost:8000**.

On first start the app container automatically runs migrations, seeds the database, and syncs course content. To bring everything down:

```bash
docker compose down
```

To also remove persistent volumes (database data):

```bash
docker compose down -v
```

---

### Option 2 — Linux (manual)

**Requirements:** PHP 8.3, Composer, Node.js 20+, PostgreSQL 16, Redis

#### 1. Install system dependencies

```bash
# Ubuntu / Debian
sudo apt update && sudo apt install -y \
    php8.3 php8.3-cli php8.3-fpm php8.3-pgsql php8.3-zip \
    php8.3-mbstring php8.3-xml php8.3-curl php8.3-redis \
    postgresql postgresql-client redis-server nodejs npm

# Fedora / RHEL
sudo dnf install -y php8.3 php8.3-pgsql php8.3-zip php8.3-mbstring \
    php8.3-xml php8.3-curl postgresql-server redis nodejs npm
```

#### 2. Set up the database

```bash
sudo -u postgres psql -c "CREATE DATABASE lms;"
sudo -u postgres psql -c "CREATE USER lms_user WITH PASSWORD 'secret';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE lms TO lms_user;"
```

#### 3. Install application dependencies

```bash
composer install
npm install
```

#### 4. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and update the database credentials:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=lms
DB_USERNAME=lms_user
DB_PASSWORD=secret
```

#### 5. Run migrations and seed data

```bash
php artisan migrate --seed
php artisan content:sync
```

#### 6. Build frontend assets

```bash
npm run build
```

#### 7. Start the development server

```bash
composer run dev
```

This starts the Laravel dev server, queue worker, log watcher, and Vite in one command. The application will be at **http://localhost:8000**.

---

### Option 3 — Laravel Cloud

[Laravel Cloud](https://cloud.laravel.com) is the official managed hosting platform for Laravel applications.

#### 1. Push your code to a GitHub repository

Ensure your code is pushed to a GitHub repo that Laravel Cloud can access.

#### 2. Create a new project on Laravel Cloud

1. Log in at [cloud.laravel.com](https://cloud.laravel.com).
2. Click **New Project** and connect your GitHub repository.
3. Select the branch to deploy (e.g. `main`).

#### 3. Configure environment variables

In the Laravel Cloud dashboard, add the following environment variables under **Settings → Environment**:

```dotenv
APP_NAME="IT Weekend LMS"
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=pgsql
DB_HOST=<your-cloud-db-host>
DB_PORT=5432
DB_DATABASE=lms
DB_USERNAME=<your-db-user>
DB_PASSWORD=<your-db-password>

REDIS_HOST=<your-redis-host>
REDIS_PORT=6379

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

#### 4. Set the build and release commands

| Stage | Command |
|---|---|
| Build | `npm ci && npm run build` |
| Release | `php artisan migrate --force && php artisan content:sync` |

#### 5. Deploy

Click **Deploy** in the dashboard. Laravel Cloud will build the application, run the release commands, and make it live on your assigned domain.

---

## Useful Artisan Commands

| Command | Description |
|---|---|
| `php artisan content:sync` | Sync markdown course content into the database |
| `php artisan legacy:migrate` | Migrate data from a legacy SQLite database |
| `php artisan migrate --seed` | Run all migrations and seed the database |
| `php artisan queue:work` | Start processing background jobs |
| `php artisan tinker` | Open an interactive REPL |

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

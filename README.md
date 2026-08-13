# 🧑‍💼 HRIS – Human Resource Information System

> ⚠️ **Work In Progress** – This project is under active development

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="320" alt="Laravel Logo">
</p>

<p align="center">
  <strong>Built with Laravel</strong>
</p>

---

## 📌 Overview

This project is a **Human Resource Information System (HRIS)** built using **Laravel**.

The system is designed to be **modular, scalable, and maintainable**, following clean architecture principles and enterprise-ready patterns such as:

- Module-based structure
- Repository pattern
- Request validation
- Separation of concerns

---

## 🏗️ Architecture & Folder Structure

The application follows a **module-first architecture**.

```
app/
├── Http/
│   ├── Controllers/
│   │   └── {Module}/
│   │       └── {Module}Controller.php
│   ├── Requests/
│   │   └── {Module}Request.php
├── Repository/
│   └── {Module}/
│       ├── {Module}Repository.php
│       └── {Module}RepositoryInterface.php
routes/
└── api/
    └── {module}/
        └── {module}.php
database/
└── migrations/
    └── {timestamp}_create_{module_plural}_table.php
```

### Example: Department Module

```
app/Http/Controllers/Department/DepartmentController.php
app/Http/Requests/DepartmentRequest.php
app/Repository/Department/DepartmentRepository.php
app/Repository/Department/DepartmentRepositoryInterface.php
routes/api/department/department.php
database/migrations/{timestamp}_create_departments_table.php
```

---

## 🧩 Modules

### Current / Planned Modules

- Employee
- Department
- Position
- Employment Status
- Roles
- Permissions
- Audit Logs

Each module includes:

- Controller
- Form Request
- Repository + Interface
- API route file

---

## 🛠️ Custom Artisan Commands

### Create a New Module

```bash
php artisan make:module {ModuleName}
```

**Example:**

```bash
php artisan make:module Department
```

This command safely creates (only if missing):

- Controller
- Request
- Repository
- Repository Interface
- API route file
- Model
- Migration

It also auto-registers the module's API route include in `routes/api.php` and its repository binding in `app/Providers/RepositoryServiceProvider.php`.

**Note:** Existing files and folders are never overwritten. The migration is matched by table name (not the timestamped filename), so re-running the command won't create a duplicate migration for the same module.

After scaffolding, remember to run:

```bash
php artisan migrate
```

---

## 📡 API Routing

Each module has its own API route file:

```
routes/api/{module}/{module}.php
```

**Examples:**

```
routes/api/department/department.php
routes/api/employee/employee.php
```

All module routes should be included in `routes/api.php`.

---

## 🗄️ Database & Migrations

**Run migrations:**

```bash
php artisan migrate
```

**Rollback last migration:**

```bash
php artisan migrate:rollback
```

**Reset and re-run all migrations:**

```bash
php artisan migrate:fresh
```

---

## 🔐 Authentication & Authorization

- Laravel authentication
- Role-based access control (RBAC)
- Roles and permissions stored in the database
- Repositories abstract all data access

---

## 🧰 Requirements

Make sure the following services are installed and running before starting the system:

- PHP >= 8.3 for a fresh install using the current dependency lockfile
- Composer
- MySQL / MariaDB (or your configured DB driver)
- **Redis** (used for the queue worker)
- Node.js & NPM (for frontend assets, if applicable)

---

## 🚀 Installation & Setup

```bash
git clone <repository-url>
cd hris
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Redis Setup

For shared or multi-instance deployments, this project uses **Redis** for queues and application caching. The file cache remains suitable for a single local process.

**1. Install Redis**

```bash
# macOS (Homebrew)
brew install redis
brew services start redis

# Ubuntu/Debian
sudo apt install redis-server
sudo systemctl enable --now redis-server

# Or via Docker
docker run -d --name hris-redis -p 6379:6379 redis:alpine
```

**2. Configure `.env`**

```env
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**3. Install the PHP Redis client** (if not already available)

```bash
composer require predis/predis
```

> If you prefer the native `phpredis` PHP extension instead of `predis`, install it via PECL/your OS package manager and set `REDIS_CLIENT=phpredis`.

**4. Run the queue worker**

```bash
php artisan queue:work redis
```

---

## ▶️ Running the Full System

To bring the whole system up, you'll typically need these running together (e.g. in separate terminals, or via a process manager / Docker Compose):

```bash
# 1. Start Redis (if not already running as a service)
redis-server

# 2. Start the Laravel app
php artisan serve

# 3. Start the queue worker
php artisan queue:work redis

# 4. (Optional) Start the scheduler, if the app uses scheduled tasks
php artisan schedule:work
```

---

## 🧪 Testing

**Run tests:**

```bash
php artisan test
```

---

## 🧠 Development Conventions

- Modules are isolated and self-contained
- Controllers stay thin
- Business logic lives in repositories/services
- Validation is handled via Form Requests
- API routes are organized per module

---

## 📄 License

This project is open-sourced software licensed under the MIT license.

---

## ❤️ Credits

**Laravel Framework** — [https://laravel.com](https://laravel.com)

---

## ⚙️ Additional Local Services

The current app also uses a Vue frontend, Laravel Reverb for real-time messages, and scheduled tasks for leave credits.

### One-time setup

```bash
# Backend, from hris-backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed

# Frontend, from hris-frontend
npm.cmd install
```

Set your MySQL/MariaDB credentials in `hris-backend/.env`. The local API base URL is `http://localhost:8000/backend/api/v1`.

### Required running processes

```bash
# Laravel API
php artisan serve

# Real-time message server
php artisan reverb:start --host=127.0.0.1 --port=8080

# Required for Scheduled Tasks and Leave Accrual
php artisan schedule:work

# Required only when QUEUE_CONNECTION is not sync
php artisan queue:work --tries=3
```

Start the frontend separately:

```bash
npm.cmd run dev
```

The `Batch Files/hris-backend.bat` and `Batch Files/hris-frontend.bat` files launch these local development processes. Start the backend batch file first, then the frontend batch file.

For real-time messaging, keep `BROADCAST_DRIVER=reverb` and the generated `REVERB_*` values in the backend `.env`; do not commit that file.

---

## Docker Compose setup

The repository includes a complete local Docker stack. It starts the Vue frontend, Laravel API, MySQL, Redis, Reverb, queue worker, scheduler, database migrations/seeders, and Mailpit.

### Requirements

- Docker Desktop or Docker Engine with Docker Compose v2
- Both `hris-backend` and `hris-frontend` in the same parent directory

Expected directory layout:

```text
HRIS/
|-- hris-backend/
|   `-- docker-compose.yml
`-- hris-frontend/
    `-- Dockerfile
```

### Start the complete application

Run these commands from `hris-backend`:

```bash
docker compose up --build -d
docker compose ps
```

Open:

- Frontend: `http://localhost:3000`
- Backend health check: `http://localhost:8000/backend/api/v1/health`
- Reverb WebSocket server: `http://localhost:8080`
- Mailpit inbox: `http://localhost:8025`
- MySQL from the host: `127.0.0.1:3307`
- Redis from the host: `127.0.0.1:6380`

The initial local administrator created by the seeder is:

```text
Email: admin@base.com
Password: secret
```

Change this password immediately outside local development.

### Services started by Compose

| Service | Purpose |
| --- | --- |
| `frontend` | Builds Vue and serves the SPA through Nginx |
| `backend` | Runs the Laravel HTTP API |
| `migrate` | Runs migrations and idempotent seeders before the app starts |
| `reverb` | Delivers real-time messaging events |
| `queue` | Processes Redis-backed jobs |
| `scheduler` | Runs Laravel scheduled tasks and leave accrual |
| `mysql` | Stores application data |
| `redis` | Stores queues, cache, and sessions |
| `mailpit` | Captures development email |

Useful commands:

```bash
# Follow application logs
docker compose logs -f backend reverb queue scheduler frontend

# Run an Artisan command
docker compose exec backend php artisan about

# Re-run migrations and seeders
docker compose run --rm migrate

# Stop the stack while keeping database and uploaded-file volumes
docker compose down

# Rebuild after dependency or Dockerfile changes
docker compose up --build -d
```

The Compose file contains local-development defaults. Docker-specific overrides can be placed in the backend `.env` using `DOCKER_APP_KEY`, `DOCKER_DB_DATABASE`, `DOCKER_DB_USERNAME`, `DOCKER_DB_PASSWORD`, and `DOCKER_DB_ROOT_PASSWORD`; these names intentionally do not reuse the native local database variables. For a shared or production environment, also configure unique Reverb credentials, allowed origins, TLS, backups, and secret management. Do not use `docker compose down -v` unless you intentionally want to delete the Docker database and uploaded files.

---

## Workplace Hub

Workplace Hub combines room reservations and meeting operations in one permission-aware module.

Included functions:

- Meeting-room directory with capacity, location, amenities, and active/inactive status
- Conflict-safe room reservations; overlapping active bookings are rejected by the API
- One-time, daily, weekday, and weekly recurring meetings
- Daily stand-ups, team meetings, planning, reviews, one-on-ones, training, and custom meeting types
- Organizer and attendee access controls
- Meeting agenda, minutes, decisions, completion, and cancellation
- Collaborative action items with owner, due date, priority, and status
- Private meeting attachments with authenticated download and audit logging
- Company-wide meeting and room-management permissions for authorized managers

Relevant permissions:

- `view-workplace-hub`
- `create-meetings`
- `manage-company-meetings`
- `manage-meeting-rooms`

After pulling the module into an existing installation, run:

```bash
php artisan migrate
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=RolePermissionSeeder
```

### Payroll demo data

To test payroll calculations with realistic attendance scenarios, run the optional demo seeder manually:

```bash
php artisan db:seed --class=PayrollDemoSeeder
```

It creates six linked demo users and employees, a completed semi-monthly attendance period, paid leave, approved overtime, late/undertime examples, one absence, and a draft payroll period ready to generate from the Payroll module. It is idempotent and is intentionally excluded from the normal production seed sequence.

Demo employee credentials use `payroll.demo.1@hris.test` through `payroll.demo.6@hris.test`, all with password `password`.

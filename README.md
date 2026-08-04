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

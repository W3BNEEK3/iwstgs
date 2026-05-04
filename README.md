# IWSTGS 🚀
**Intelligent Web-Based Simulation & Training System**

A modular, domain-driven Laravel 13 application for simulation-based learning, competency evaluation, and AI-assisted feedback.

---

<div align="center">

**Because real skills are forged through experience, not instruction.**

[![Status](https://img.shields.io/badge/status-in_development-blue)]()
[![Type](https://img.shields.io/badge/type-final_year_project-green)]()
[![License](https://img.shields.io/badge/license-academic-orange)]()

</div>

## 🧠 Architecture Overview

Domain-Driven Design modular monolith. Each module contains: Domain, Application, Infrastructure, and Presentation layers.

```
src/
├── Shared/
├── Identity/
├── Organizations/
├── Content/
├── Simulation/
├── SimExecution/
├── Competency/
├── EvalEngine/
├── Submission/
├── AIMediation/
└── Reporting/
```

---

## ⚙️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | Blade + HTMX + _Hyperscript |
| Database | MySQL (recommended) / SQLite (local) |
| Build | Vite (optional for MVP) |
| Architecture | Modular Monolith (DDD) |

---

## 📦 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/W3BNEEK3/iwstgs.git
cd iwstgs
```

---

## 🛠️ Setup

### Termux (Android)

```bash
pkg update && pkg upgrade -y
pkg install php composer git unzip curl nodejs -y
```

### Acode / Alpine Linux

```bash
apk update
apk add php83 php83-phar php83-openssl php83-pdo php83-pdo_sqlite \
  php83-mbstring php83-tokenizer php83-xml php83-session \
  php83-fileinfo php83-dom php83-zip git curl unzip nodejs npm composer
```

### Verify versions

```bash
php -v && composer -V && node -v && npm -v
```

---

## 🔑 Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

---

## 🗄️ Database Setup

### Option A — SQLite (Quick Start)

```bash
touch database/database.sqlite
```

In `.env`:

```
DB_CONNECTION=sqlite
```

### Option B — Remote MySQL (Recommended)

In `.env`:

```
DB_CONNECTION=mysql
DB_HOST=your-host
DB_PORT=3306
DB_DATABASE=your-db
DB_USERNAME=your-user
DB_PASSWORD=your-password
```

### Run Migrations

```bash
php artisan migrate
```

---

## 🧱 Project Structure Setup

```bash
mkdir -p src/{Shared,Identity,Organizations,Content,Simulation,SimExecution,Competency,EvalEngine,Submission,AIMediation,Reporting}
composer dump-autoload
```

---

## 🚀 Running the App

```bash
# Backend
php artisan serve

# Frontend (optional — skip on Android)
npm install && npm run dev
```

> ⚠️ **Note (Android/Termux):** Vite may fail due to file watcher limits. Use Blade + HTMX only if this happens.

---

## 🧪 Useful Artisan Commands

### Cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Migrations

```bash
php artisan migrate
php artisan migrate:fresh
php artisan migrate:rollback
```

### Generators

```bash
php artisan make:model User
php artisan make:controller UserController
php artisan make:migration create_users_table
php artisan make:seeder UserSeeder
php artisan make:middleware AuthMiddleware
php artisan make:provider DomainServiceProvider
```

### Other

```bash
php artisan tinker       # REPL
php artisan queue:work   # Queue worker
php artisan test         # Run test suite
```

---

## 🔀 Git Workflow

### First-Time Setup

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/W3BNEEK3/iwstgs.git
git push -u origin main
```

> When prompted: Username = your GitHub username, Password = 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

### SSH Key (Optional)

```bash
ssh-keygen -t ed25519 -C "your-email"
cat ~/.ssh/id_ed25519.pub   # paste into GitHub → SSH keys
```

---

## ⚠️ Android / Termux Limitations

| Issue | Status | Workaround |
|-------|--------|-----------|
| Symlinks in shared storage | ❌ Broken | Work in Termux home only |
| Vite file watchers | ❌ May crash | Use Blade + HTMX, skip Vite |
| npm outside Termux home | ⚠️ Unreliable | Keep project in ~/ |
| Full Vite dev experience | ✅ On laptop | Clone repo, run npm normally |

### Recommended Tool Split

| Task | Tool |
|------|------|
| Editing code | Acode |
| Running backend | Termux |
| Frontend builds | Laptop |
| Database | Remote MySQL |

---

## 📊 Core Domains

| Domain | Responsibility |
|--------|---------------|
| Identity | Auth, RBAC |
| Organizations | Multi-tenancy |
| Simulation | Scenario definitions |
| SimExecution | Live sessions |
| Competency | Skill tracking |
| EvalEngine | Scoring logic |
| Submission | Code execution |
| AIMediation | AI feedback |
| Reporting | Analytics |

---

## 🧭 Roadmap

- [ ] Domain base classes (AggregateRoot, ValueObject, etc.)
- [ ] Module service providers
- [ ] Feature toggle system per module
- [ ] AI integration layer (Claude API)
- [ ] Docker-based code execution sandbox
- [ ] Reporting dashboards

---

## 🤖 AI Integration (Planned)

- Claude API for prompt-based evaluation
- Automated feedback generation
- Submission interpretation & scoring

---

## 🔧 Feature Toggles (Planned)

- Active / Inactive projects
- Coming Soon pages
- Maintenance mode per module
- Disabled feature flags

---

## 👨‍💻 Author

**W3BNEEK3**

---

## 📄 License

Proprietary – Internal Project

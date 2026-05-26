# CRM Meta

CRM with **Meta Lead Ads** integration (webhooks from Facebook/Instagram Lead Ads).
Built with **Laravel 13 + Filament 5**, containerized with **Docker**, and designed
to deploy on **Coolify** over a VPS.

## Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13 (PHP 8.4) |
| Admin / CRM UI | Filament 5 |
| Database | MySQL 8 |
| Cache / Queue / Sessions | Redis 7 |
| Web server | Nginx 1.27 |
| Containers | Docker (multi-stage) + Compose |
| Deploy | Coolify (self-hosted) |

## Container architecture

```
┌──────────┐   ┌──────────────┐   ┌───────────┐
│  nginx   │──▶│ app (php-fpm)│──▶│   mysql   │
└──────────┘   └──────────────┘   └───────────┘
                       │
                       ▼
                 ┌───────────┐
                 │   redis   │
                 └───────────┘
```

- **nginx** — web server, serves static files and proxies PHP requests to `app:9000`.
- **app** — PHP-FPM 8.4. In dev includes Composer + Node (Dockerfile `dev` target).
- **mysql** — primary persistence.
- **redis** — cache, queue (Meta webhooks processing), sessions.

### Design decisions

- **No workspace container** — the Dockerfile `dev` target already includes Composer
  and Node. Laradock-style workspace pattern intentionally avoided; multi-stage is the
  current best practice.
- **Nginx separated from PHP-FPM** — mirrors production setup, cleaner logs, no
  supervisor process juggling.
- **No dedicated queue worker yet** — added when Meta webhook processing becomes async
  (`php artisan queue:work` in a separate service).
- **Single `.env` shared by Docker Compose and Laravel** — one source of truth.

## Bootstrap (first time)

### 1. Configure environment

```bash
cp .env.example .env
```

Adjust UID/GID if your host user is not 1000:

```bash
echo "UID=$(id -u)" >> .env
echo "GID=$(id -g)" >> .env
```

### 2. Scaffold Laravel inside the directory

The repo ships with Docker files but no Laravel code (intentional to keep history clean).
Install Laravel into a temp folder and merge without overwriting existing files:

```bash
docker run --rm \
  -v "$PWD:/app" -w /app \
  --user "$(id -u):$(id -g)" \
  composer:2 create-project "laravel/laravel:^13.0" _laravel --prefer-dist --no-interaction

shopt -s dotglob
cp -rn _laravel/. .
rm -rf _laravel
```

### 3. Build and start the stack

```bash
docker compose up -d --build
```

### 4. Generate app key and run migrations

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 5. Install Filament

```bash
docker compose exec app composer require filament/filament:"^5.0" -W
docker compose exec app php artisan filament:install --panels
docker compose exec app php artisan make:filament-user
```

### 6. Build assets (Vite)

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

For development with HMR:

```bash
docker compose exec app npm run dev
```

### 7. Access

- App: http://crm-meta.localhost
- Filament admin: http://crm-meta.localhost/admin
- MySQL: `localhost:3307` (user `crm`, password from `.env`)
- Redis: `localhost:6380`

> `crm-meta.localhost` resolves to `127.0.0.1` automatically via glibc NSS /
> systemd-resolved (RFC 6761 reserves all `*.localhost`). **No `/etc/hosts` entry
> required.**

## Daily commands

```bash
# Logs
docker compose logs -f app
docker compose logs -f nginx

# Artisan
docker compose exec app php artisan <cmd>

# Composer
docker compose exec app composer <cmd>

# Shell inside the app container
docker compose exec app sh

# Restart
docker compose restart

# Stop everything
docker compose down

# Stop and remove volumes (wipes data)
docker compose down -v
```

## Architecture & Conventions

### Language

The entire codebase — source code, DB schema, model names, variables, comments,
commit messages — is in **English**. Plain, simple English; readability over cleverness.

### Naming

| Item | Convention | Example |
|------|-----------|---------|
| DB tables | `snake_case`, plural | `leads`, `meta_forms` |
| DB columns | `snake_case` | `first_name`, `captured_at` |
| Foreign keys | `{singular}_id` | `contact_id` |
| Boolean columns | prefix `is_` / `has_` | `is_processed`, `has_consent` |
| Timestamp columns | suffix `_at` | `captured_at`, `contacted_at` |
| PHP classes | `PascalCase` | `Lead`, `ProcessMetaLead` |
| PHP methods | `camelCase` | `markAsContacted()` |
| Constants | `SCREAMING_SNAKE_CASE` | `MAX_RETRIES` |
| Enums | `PascalCase` with `PascalCase` cases | `LeadStatus::Pending` |
| Routes | `kebab-case` | `/meta-webhooks/leads` |
| Filament Resources | `{Model}Resource` | `LeadResource` |
| Jobs | imperative verb | `ProcessMetaLead` |
| Services | `{Domain}Service` | `MetaGraphService` |
| Form Requests | `{Verb}{Model}Request` | `StoreLeadRequest` |

### Project structure (additions to Laravel defaults)

```
app/
├── Actions/       # Single-purpose actions (e.g., ConvertLeadToContact)
├── Enums/         # Typed enums (LeadStatus, DealStage)
├── Filament/      # Resources, Pages, Widgets
├── Observers/     # Eloquent event observers
├── Services/      # External API wrappers (MetaGraphService)
└── Support/       # Helpers, traits, value objects
```

### Models

- Explicit `$fillable` (allowlist) — never `$guarded = []`.
- Casts for `json`, `datetime`, `decimal`, and enums.
- Class-level PHPDoc documents the model's purpose and field semantics.
- Relationships declared with explicit return type:
  `public function contact(): BelongsTo { ... }`.
- Scopes prefixed with `scope`: `scopeProcessed()`.

### Migrations

- One migration per concept.
- Explicit foreign keys:
  `->foreignId('contact_id')->nullable()->constrained()->cascadeOnDelete()`.
- Indexes on lookup columns: `->index()` on FKs, `->unique()` on business keys.
- `->comment()` only when the column meaning is ambiguous **and** no enum applies.

### Documentation strategy

- **Models** are the source of truth for "what each field means" (PHPDoc).
- **Enums** document finite-value fields (better than strings + comments).
- **DB column comments** only when the field is ambiguous and not covered by an enum.
- **Schema dump** (`database/schema/mysql-schema.sql`) regenerated after major schema
  changes via `php artisan schema:dump --prune`.

### Validation

- Form Requests for any endpoint accepting user input.
- Webhooks validated via Form Request as well — never inline in controllers.

### Testing

- Pest (Laravel 13 default).
- Feature tests over Unit tests; cover behavior end-to-end.
- Factories for every model.
- SQLite `:memory:` for fast test runs (default `phpunit.xml`).

### Git workflow

- **Conventional Commits**: `feat:`, `fix:`, `chore:`, `refactor:`, `docs:`, `test:`.
- Branches: `feat/short-description`, `fix/short-description`.
- Small, focused commits that tell a story.

### Code style

- Laravel Pint with the `laravel` preset. No custom rules at the start.

### Comments

- WHAT is not commented (the code says it).
- WHY is commented when constraints are non-obvious or the choice is counter-intuitive.
- PHPDoc on classes and public methods; on private only when the name isn't enough.

## Deploy on Coolify

1. Push the repo to GitHub.
2. In Coolify: create a new application of type **Docker Compose** pointing at the repo.
3. Configure environment variables in the Coolify UI (based on `.env.example`,
   setting `APP_ENV=production`, `APP_DEBUG=false`, real credentials, etc.).
4. Coolify builds `Dockerfile` with `--target=prod`. Production overrides via
   `docker-compose.prod.yml` (to be added on first real deploy).
5. Configure domain + Let's Encrypt automatically in Coolify.

## Repository layout

```
.
├── Dockerfile                 # Multi-stage: base / dev / build / prod
├── docker-compose.yml         # Development stack
├── docker/
│   ├── nginx/default.conf     # Laravel vhost
│   └── php/php.ini            # PHP / OPcache tuning
├── .env.example               # Shared template for Docker + Laravel
├── .gitignore
├── .dockerignore              # Speeds up builds, prevents secret leakage
├── README.md
├── CLAUDE.md                  # Architectural decisions log (working doc)
└── (Laravel files...)
```

## Roadmap

- [ ] `Lead` model with migration tailored for Meta `field_data` (JSON column).
- [ ] `/webhooks/meta/leads` endpoint with `X-Hub-Signature-256` validation.
- [ ] `ProcessMetaLead` job with Meta Graph API fetch.
- [ ] Filament Resource for `Lead` with pipeline kanban.
- [ ] Service for long-lived Page Access Token.
- [ ] Queue worker container (`php artisan queue:work`) once Meta flow is live.
- [ ] `docker-compose.prod.yml` with `prod` target for Coolify.

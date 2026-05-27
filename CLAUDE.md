# CRM Meta — Contexto para Claude

> Este archivo se carga automáticamente cuando Claude trabaja en este repo.
> Mantiene continuidad entre sesiones: decisiones, estado, convenciones.

## Qué es este proyecto

CRM con integración a **Meta Lead Ads** (webhooks de leads de Facebook/Instagram → CRM).

**Propósito**: pieza de portfolio para la búsqueda laboral Backend/Laravel de Dario.
Debe demostrar buen criterio técnico sin sobre-ingeniería.

## Stack

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Lenguaje | PHP | 8.4 |
| Backend | Laravel | 13 |
| Admin / CRM UI | Filament | 5 |
| Base de datos | MySQL | 8.0 |
| Cache / Queue / Sessions | Redis | 7 |
| Web server | Nginx | 1.27 |
| Contenedores | Docker | multi-stage + Compose |
| Deploy target | Coolify | self-hosted en VPS |

## Arquitectura de contenedores

**4 containers en dev**: nginx, app, mysql, redis.

- `nginx`, `mysql`, `redis` → imágenes oficiales (puras, solo config/env).
- `app` → build propio desde `Dockerfile` multi-stage.
  - target `dev`: PHP-FPM 8.4 + composer + node + npm + user no-root con UID/GID del host
  - target `build`: stage intermedio que corre `composer install --no-dev` + `npm run build`
  - target `prod`: imagen slim final (sin composer ni node), la que builda Coolify

```
[ nginx :80 ] ──fastcgi──▶ [ app :9000 (php-fpm) ] ──▶ [ mysql :3306 ]
                                                  └──▶ [ redis :6379 ]
```

## Decisiones tomadas (con porqué)

### Multi-stage Dockerfile, sin container `workspace`
Reemplaza el patrón Laradock (workspace separado para tools de dev). El target `dev`
ya trae composer + node, accedido vía `docker compose exec app ...`. Beneficio adicional:
**imposible que dev y prod tengan distinta versión de PHP/extensions** porque ambos
parten del mismo stage `base`. Menos containers, menos drift.

### Nginx + PHP-FPM separados (no supervisor en un solo container)
Patrón productivo real. Logs limpios por servicio. Cada uno escalable independiente.

### Sin queue worker dedicado **todavía**
YAGNI. Se agrega como service nuevo en `docker-compose.yml` cuando empecemos a
procesar webhooks Meta async (corriendo `php artisan queue:work`).

### Redis configurado desde el inicio para cache + queue + sessions
Coherente con que vamos a procesar webhooks Meta en jobs asíncronos. Una sola pieza
para tres responsabilidades. Si el load lo justifica después, se separa.

### Laravel 13 + Filament 5 + PHP 8.4
Versiones estables actuales (mayo 2026). Laravel 11 EOL marzo 2026 fue descartado.

### Misma `.env` para Docker Compose y Laravel
Una sola fuente de verdad. Variables `DB_*` las leen ambos.

### `.dockerignore` cuidado
Excluye `vendor`, `node_modules`, `storage/logs`, `.env`, `tests` del contexto de build.
Builds más rápidos y sin riesgo de filtrar secretos en imagen prod.

### Extensión PHP `redis` (phpredis) en lugar de Predis
Instalada vía `pecl install redis` en el stage `base` del Dockerfile. Es la opción
performante para producción (extension nativa C, no PHP puro). Predis se descartó por
ser más lento y porque la imagen ya tiene que tener herramientas para compilar otras
extensiones, así que el costo marginal es bajo.

## Estructura del repo

```
.
├── CLAUDE.md                  # Este archivo
├── Dockerfile                 # Multi-stage: base / dev / build / prod
├── docker-compose.yml         # Stack dev (4 services)
├── docker/
│   ├── nginx/default.conf     # Vhost Laravel
│   └── php/php.ini            # Tuning PHP/OPcache
├── .env.example               # Plantilla compartida Docker + Laravel
├── .env                       # (gitignored) credenciales reales
├── .gitignore
├── .dockerignore
├── README.md                  # Bootstrap para humanos
└── [Laravel app files...]     # app/, config/, database/, routes/, etc.
```

## Comandos del día a día

```bash
# Levantar el stack
docker compose up -d

# Ver logs
docker compose logs -f app
docker compose logs -f nginx

# Artisan
docker compose exec app php artisan <cmd>

# Composer
docker compose exec app composer <cmd>

# Shell en el container app
docker compose exec app sh

# Vite dev server (HMR)
docker compose exec app npm run dev

# Build assets producción
docker compose exec app npm run build

# Parar todo
docker compose down

# Reset completo (borra volúmenes / datos)
docker compose down -v
```

## Variables de entorno clave

Ver `.env.example`. Lo importante:

- `UID` / `GID`: matching con el host para que archivos creados en el container
  no queden owned por root.
- `DB_HOST=mysql`, `REDIS_HOST=redis`: hostnames internos de la red Docker.
- `APP_PORT=80`: puerto expuesto en el host. Convive con el patrón `*.local` en
  `/etc/hosts` (ver sección URL local).
- `META_VERIFY_TOKEN`, `META_APP_SECRET`, `META_PAGE_ACCESS_TOKEN`: para Meta Lead Ads.

## URL local

`APP_URL=http://localhost` (canónica). Cambiada desde `crm-meta.localhost` porque
**Google OAuth no acepta subdominios `*.localhost`** — solo `localhost` literal o TLDs
públicos. Nginx tiene `server_name _;` así que el container responde a cualquier host
en :80, así que `http://crm-meta.localhost` también funciona pero los links generados
por Laravel (redirects, emails) usan `localhost`.

Como nginx escucha en puerto 80 del host, **solo un proyecto a la vez puede correr
en :80**. Si tenés otro proyecto Docker que usa :80 (ej. dropsync), `docker compose stop`
ese antes de levantar este.

Auto-start: los containers tienen `restart: unless-stopped`, así que se levantan
solos cuando arranca el Docker daemon (que está habilitado al boot del sistema).

### Hot reload (Vite dev server)

Para que las ediciones a Blade / theme.css se reflejen sin rebuildear:

```bash
docker compose exec app npm run dev   # dejá esto corriendo en una terminal
```

Vite escucha en :5173 (puerto expuesto en `docker-compose.yml`). El archivo
`public/hot` le indica a Laravel que sirva los assets desde Vite en lugar del build
estático.

## Roadmap

### Hecho (consolidado al 2026-05-27)

**Infra y framework:**
- [x] Docker multi-stage (base/dev/build/prod) + Compose
- [x] Laravel 13 + Filament 5 + PHP 8.4 + MySQL 8 + Redis 7
- [x] Spatie Permission (roles admin/manager/sales) + Activity Log
- [x] IDE Helper (docblocks auto-gen) + schema dump consolidado
- [x] Filament theme custom (`resources/css/filament/admin/theme.css`) — escanea
      `app/Filament/**` y `resources/views/filament/**` con `@source`
- [x] Vite HMR para Docker (puerto 5173, `hmr.host=localhost`)
- [x] Top navigation en admin panel
- [x] `APP_URL=http://localhost` (Google OAuth lo exige; ver "URL local")

**Dominio (CRM core):**
- [x] Migrations: agencies, users (extended), contacts, contact_phones (BIGINT phone),
      contact_emails, leads, contact_events (polymorphic), notes
- [x] Modelos: Agency, User, Contact, ContactPhone, ContactEmail, Lead, ContactEvent, Note
- [x] Enum `LeadStatus` (Pending/Processing/Processed/Failed/Skipped)
- [x] `BelongsToAgencyScope` global scope en Contact/Lead/ContactEvent (skip si super-admin)
- [x] Hook `creating` en Contact + Lead → auto-fill `agency_id` desde user autenticado
- [x] `LeadContactResolver` service: find-or-create por email/phone normalizado (digits-only),
      scoped por agency
- [x] `ProcessLead` job (queue) + `LeadObserver` dispatch on creating (afterCommit)

**Admin panel (Filament Resources):**
- [x] AgencyResource (super-admin only) — name, slug auto-gen, counts de users/contacts/leads
- [x] UserResource (admin/manager) — Approve action inline, role badges, avatar circular
- [x] ContactResource — Phones/Emails/Events RelationManagers
- [x] ViewContact custom page: tarjeta info izquierda, textarea+timeline derecha
      (`resources/views/filament/resources/contacts/pages/view-contact.blade.php`)
- [x] LeadResource — campos read-only (datos vienen de webhook), status badge con colores,
      Select para link manual al contact
- [x] `agency_id` field en form: Select visible solo para super-admin; el resto lo
      autocompleta el creating hook
- [x] Avatar columns como `ImageColumn::circular()` con fallback a ui-avatars.com

**Auth:**
- [x] Laravel Socialite + dutchcodingcompany/filament-socialite
- [x] Botón "Continue with Google" en `/admin/login`
- [x] Provider en **stateless mode** (Google valida; bypassea problemas de session/cookie
      cuando el redirect cruza dominios)
- [x] Google OAuth credentials en `.env` (NUNCA en `.env.example`)
- [x] Redirect URI registrado: `http://localhost/admin/oauth/callback/google`
- [x] `createUserUsing` callback: nuevos users → pending (agency_id null, approved_at null)
- [x] `User` implementa `FilamentUser` (gating canAccessPanel: super-admin OR active+approved)
      y `HasAvatar` (avatar en top-right menu)

### Pendiente

- [ ] Endpoint webhook `/webhooks/meta/leads`:
  - GET verification con `hub.challenge`
  - POST con validación HMAC-SHA256 (`X-Hub-Signature-256`) usando META_APP_SECRET
  - Responder 200 < 5s, despachar Job para fetch real
- [ ] Job `ProcessMetaLead` (distinto de `ProcessLead`, este es el que hace GET a Graph API)
- [ ] `MetaGraphService` para wrap del Graph API + Page Access Token de larga duración
- [ ] Filament Policies por Resource (lock down create/delete según rol; hoy solo
      navegación está restringida)
- [ ] Filament Tenancy formal (`->tenant(Agency::class)`) — hoy el aislamiento es por
      Global Scope, funciona pero la integración Filament-nativa sería más limpia
- [ ] `docker-compose.prod.yml` + service `queue_worker` dedicado para Coolify deploy
- [ ] Filament Widgets / Dashboard stats (leads esta semana, contactos nuevos, etc.)
- [ ] Pest feature tests del flujo lead → contact (al menos 4: e2e, dedup, cross-tenant
      isolation, skipped on empty payload)
- [ ] Login agency-scoped: ruta `/agencies/{slug}/login` que prefilla `agency_id`

### Sin commitear (working tree al cerrar la sesión 2026-05-27)

- ImageColumn de avatar + HasAvatar en User (UsersTable, ContactsTable, User model)
- Sugerencia de mensaje: `feat: render user and contact avatars as circular images`

## Deploy en Coolify

1. Repo en GitHub/GitLab.
2. En Coolify: app tipo **Docker Compose**, apuntar al repo.
3. Variables de entorno en la UI (basadas en `.env.example`, con `APP_ENV=production`,
   `APP_DEBUG=false`, credenciales reales).
4. Coolify builda `Dockerfile` con `--target=prod`.
5. Migrations como **post-deployment command** en Coolify.
6. Dominio + Let's Encrypt automático.

## Sobre Dario (el usuario)

- PHP/Laravel developer, 5 años de experiencia.
- Buscando trabajo Backend/Laravel.
- Email: darmarioar@gmail.com
- Timezone: Argentina (America/Argentina/Buenos_Aires)
- Idioma de preferencia para conversación: **español**.

## Convenciones del proyecto

### Idioma
- **Código, DB, modelos, variables, comments**: inglés. Simple y claro, prioridad
  legibilidad sobre estilo.
- **Commits, branches, PRs**: inglés.
- **`README.md`**: inglés (público, portfolio-facing).
- **`CLAUDE.md`** (este archivo): español (working doc personal de Dario).
- **Conversación Claude ↔ Dario**: español.

### Naming
| Item | Convención | Ejemplo |
|------|-----------|---------|
| Tablas DB | snake_case plural | `leads`, `meta_forms` |
| Columnas DB | snake_case | `first_name`, `captured_at` |
| Foreign keys | `{singular}_id` | `contact_id`, `meta_form_id` |
| Booleanos DB | prefix `is_` / `has_` | `is_processed`, `has_consent` |
| Timestamps DB | suffix `_at` | `captured_at`, `contacted_at` |
| Clases PHP | PascalCase | `Lead`, `ProcessMetaLead` |
| Métodos | camelCase | `markAsContacted()` |
| Constantes | SCREAMING_SNAKE_CASE | `MAX_RETRIES` |
| Enums | PascalCase + cases PascalCase | `LeadStatus::Pending` |
| Rutas | kebab-case | `/meta-webhooks/leads` |
| Filament Resources | `{Model}Resource` | `LeadResource` |
| Jobs | Verbo imperativo | `ProcessMetaLead` |
| Services | `{Domain}Service` | `MetaGraphService` |
| Form Requests | `{Verb}{Model}Request` | `StoreLeadRequest` |

### Estructura de directorios extra (sobre Laravel default)
```
app/
├── Actions/       # Operaciones puntuales (ej: ConvertLeadToContact)
├── Enums/         # Enums tipados (LeadStatus, DealStage)
├── Filament/      # Resources, Pages, Widgets
├── Observers/     # Eloquent event observers (para activity log y side-effects)
├── Services/      # Wrappers de APIs externas (MetaGraphService)
└── Support/       # Helpers, traits, value objects
```

### Modelos
- `$fillable` explícito (allowlist) — nunca `$guarded = []`.
- Casts para `json`, `datetime`, `decimal`, enums.
- PHPDoc a nivel clase explicando el rol del modelo y semántica de campos.
- Relaciones con return type explícito: `public function contact(): BelongsTo`.
- Scopes con prefijo `scope`: `scopeProcessed()`.

### Migrations
- Una migración por concepto (no mezclar tablas distintas).
- FK explícita: `->foreignId('contact_id')->nullable()->constrained()->cascadeOnDelete()`.
- Índices: `->index()` en FKs, `->unique()` en business keys (`meta_lead_id`).
- `->comment()` solo cuando el campo es ambiguo **y** no hay enum.

### Documentación
- **Modelos** = source of truth para significado de campos (vía PHPDoc).
- **Enums** = source of truth para valores finitos (mejor que strings + comments).
- **DB column comments** = solo si el campo es ambiguo y no hay enum.
- **Schema dump** (`database/schema/mysql-schema.sql`) regenerado tras cambios grandes
  vía `php artisan schema:dump --prune` (fuente única para que Claude lea schema rápido).
- **CLAUDE.md** = decisiones + estado (working doc).
- **README.md** = onboarding público (inglés).

### Validación
- Form Requests para todo endpoint con input de usuario.
- Webhooks: validar en Form Request, no inline en controller.

### Testing
- Pest (default Laravel 13).
- Feature tests > Unit tests. Apuntar a comportamiento end-to-end.
- Factories para todos los modelos.
- DB SQLite `:memory:` para velocidad (default phpunit.xml).

### Git
- **Conventional Commits**: `feat:`, `fix:`, `chore:`, `refactor:`, `test:`, `docs:`.
- Branch por feature: `feat/meta-webhook-endpoint`, `fix/duplicate-leads`.
- Commits pequeños que cuenten una historia.
- PRs aunque sea repo personal — práctica de portfolio.

### Code style
- Laravel Pint con preset `laravel`. Sin custom rules al inicio.

### Comments en código
- WHAT no se comenta (el nombre lo dice).
- WHY sí (constraints no obvios, decisiones contraintuitivas).
- PHPDoc en clases y métodos públicos. En privados solo si el nombre no es claro.

## Recordatorios para Claude

### Reglas duras

- **Git**: nunca ejecutar `git commit` o `git push` sin pedido explícito. Dario lo
  maneja a mano. Hacer el trabajo de archivos y decir "listo para commit". (Ver memoria
  `feedback-git-manual`.)
- **`.env.example`**: jamás escribir valores reales ahí — solo placeholders. Las
  credenciales (Google OAuth, META_APP_SECRET, etc.) van en `.env` (gitignored).
- **No agregar Docker workspace container**: decisión deliberada, multi-stage lo cubre.
- **No agregar queue worker dedicado antes del primer webhook real**: YAGNI.
- **No agregar mailpit / scheduler / debug bar** sin pedido explícito: scope creep.
- **Este proyecto es portfolio**: cada decisión técnica debe poder explicarse en
  entrevista. Si algo no se justifica, mejor no lo agregues.

### Gotchas aprendidos (no repetir)

1. **Spatie ActivityLog v5 cambió namespaces**:
   - Trait: `Spatie\Activitylog\Models\Concerns\LogsActivity` (NO `Traits\`)
   - LogOptions: `Spatie\Activitylog\Support\LogOptions` (NO root del namespace)

2. **`contact_phones.phone` es `unsignedBigInteger`** (decisión explícita del user).
   Cualquier código que reciba phones de afuera (webhooks, formularios externos) debe
   `preg_replace('/\D/', '', $raw)` y castear a `int` antes de guardar/buscar.
   `LeadContactResolver::normalizePhone()` lo hace ya para el flujo de leads.

3. **Google OAuth rechaza `*.localhost`** subdominios. Solo acepta `localhost` literal
   o TLDs públicos. Por eso `APP_URL=http://localhost` y NO `crm-meta.localhost`.

4. **Filament 5 estructura nueva**: `Resources/{Model}/{Pages,Schemas,Tables,RelationManagers}/`.
   Las clases form/table viven en archivos separados (`{Model}Form`, `{Model}sTable`). El
   Resource main solo delega.

5. **CSS de Filament es pre-compilado**: clases Tailwind custom en views propias NO se
   ven a menos que crees un theme custom y rebuildées con `npm run build`. El theme está
   en `resources/css/filament/admin/theme.css` con directivas `@source` que escanean
   `app/Filament/**` y `resources/views/filament/**`.

6. **Filament 5: `Hidden::make` + `default()` con `auth()->user()->agency_id`** se rellena
   con NULL si el user es super-admin (no tiene agency). Bug clásico. Solución que
   aplicamos: `Select::make('agency_id')->visible(super-admin only)` + `creating` hook
   en el modelo que rellena para users regulares.

7. **OAuth provider stateless**: si dejás `->stateless(false)`, el redirect Google→callback
   puede perder la session cookie y tirar `InvalidStateException`. Stateless OK porque
   Google igual valida el `code` con el `client_secret`.

8. **Avatar de Google es de 1000+ chars**. `users.avatar` y `contacts.avatar` están en
   `TEXT`, no `VARCHAR(255)`.

9. **`->topNavigation()` en Filament 5** convierte navigation groups en dropdowns en la
   top bar. Si querés volver al sidebar, comentá esa línea en `AdminPanelProvider`.

10. **`name` column en `users`**: se mantiene por compatibilidad con `make:filament-user`,
    se sincroniza desde `first_name + last_name` en un saving hook del User model.

### Cuándo cuestionar al user

- Si pide una migración destructiva (drop column, type change con riesgo de pérdida),
  preguntar primero.
- Si pide algo que toca `.env` o credenciales, verificar que vaya en `.env` no en `.env.example`.

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

App accesible en `http://crm-meta.localhost` (configurado en `APP_URL` del `.env`).

**No requiere `/etc/hosts`**: el TLD `.localhost` está reservado por RFC 6761 para
resolverse a `127.0.0.1` automáticamente. Glibc NSS y systemd-resolved (en Linux moderno)
y los browsers más comunes lo respetan. Elegido por sobre `.local` porque `.local` está
reservado para mDNS (RFC 6762) y puede comportarse de forma inconsistente.

Como nginx escucha en puerto 80 del host, **solo un proyecto a la vez puede correr
en :80**. Si tenés otro proyecto Docker que usa :80 (ej. dropsync), `docker compose stop`
ese antes de levantar este.

Auto-start: los containers tienen `restart: unless-stopped`, así que se levantan
solos cuando arranca el Docker daemon (que está habilitado al boot del sistema).

## Roadmap

### Hecho
- [x] Estructura Docker (Dockerfile multi-stage, compose, configs nginx/php)
- [x] `.env.example`, `.gitignore`, `.dockerignore`
- [x] `README.md` con instrucciones bootstrap
- [x] `CLAUDE.md` (este archivo)
- [x] Scaffold Laravel 13 (`composer create-project`)
- [x] APP_KEY + migrations iniciales contra MySQL
- [x] Filament 5 instalado + panel `/admin` scaffoldeado
- [x] Assets compilados (Vite)
- [x] Stack levantado y respondiendo HTTP 200 en `/` y `/admin/login`

### Siguiente
- [ ] Crear primer usuario admin: `docker compose exec app php artisan make:filament-user`
  (es interactivo: pide name/email/password)
- [ ] Modelo `Lead` con migración (incluir column JSON para `field_data` de Meta)
- [ ] Endpoint webhook `/webhooks/meta/leads`:
  - GET para verificación inicial (`hub.challenge`)
  - POST con validación de firma `X-Hub-Signature-256` (HMAC-SHA256 con App Secret)
  - Responder 200 < 5s, despachar Job para fetch real
- [ ] Job `ProcessMetaLead`:
  - Recibe `leadgen_id` + `form_id` + `page_id`
  - GET a `https://graph.facebook.com/v19.0/{leadgen_id}?access_token=...`
  - Mapear `field_data` → tabla `leads`
  - Idempotencia: índice único sobre `leadgen_id`
- [ ] Service para Page Access Token de larga duración (System User token en Meta Business)
- [ ] Filament Resource para `Lead`:
  - Form, table, filtros por form_id / campaign / estado
  - Pipeline kanban (plugin de Filament o custom)
  - Asignación a vendedor (relación con `User`)
  - Actions: marcar contactado, cerrar ganado/perdido
- [ ] Roles + permisos (Spatie Permission): admin, vendedor, manager
- [ ] Activity log (Spatie) para auditoría de cambios en leads
- [ ] `docker-compose.prod.yml` con target `prod` para Coolify
- [ ] Service `queue` dedicado en compose cuando se active flujo Meta real

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

- **No agregar Docker workspace container**: decisión deliberada, ya documentada.
- **No agregar queue worker antes del primer webhook real**: YAGNI.
- **No agregar mailpit / scheduler / debug bar** sin pedido explícito: scope creep.
- **Verificar versiones actuales** antes de pinear (Laravel 13 / Filament 5 al momento
  de escribir esto, pero conviene re-verificar en sesiones nuevas).
- **Este proyecto es portfolio**: cada decisión técnica debe poder explicarse en
  entrevista. Si algo no se justifica, mejor no lo agregues.

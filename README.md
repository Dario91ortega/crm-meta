# CRM Meta

CRM con integración a **Meta Lead Ads** (webhooks de leads de Facebook/Instagram).
Construido con **Laravel 13 + Filament 5**, contenedorizado con **Docker** y diseñado
para deployar en **Coolify** sobre un VPS.

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 13 (PHP 8.4) |
| Admin / CRM UI | Filament 5 |
| Base de datos | MySQL 8 |
| Cache / Queue / Sessions | Redis 7 |
| Web server | Nginx 1.27 |
| Contenedores | Docker (multi-stage) + Compose |
| Deploy | Coolify self-hosted |

## Arquitectura de contenedores

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

- **nginx**: web server, sirve estáticos y proxea PHP a app:9000.
- **app**: PHP-FPM 8.4. En dev incluye composer + node (target `dev` del Dockerfile).
- **mysql**: persistencia principal.
- **redis**: cache, queue (webhooks de Meta), sessions.

### Decisiones de diseño

- **Sin workspace container**: el target `dev` del Dockerfile ya trae composer y node.
  Patrón Laradock evitado a propósito — multi-stage es la práctica actual.
- **Nginx separado de PHP-FPM**: refleja producción real, logs limpios, sin supervisor.
- **Sin queue worker dedicado todavía**: se agrega cuando empecemos a consumir webhooks
  de Meta (`php artisan queue:work` en un service aparte).
- **Misma `.env` para Docker Compose y Laravel**: una sola fuente de verdad.

## Bootstrap (primera vez)

### 1. Configurar variables de entorno

```bash
cp .env.example .env
```

Ajustar UID/GID si tu usuario host no es 1000:

```bash
echo "UID=$(id -u)" >> .env
echo "GID=$(id -g)" >> .env
```

### 2. Instalar Laravel dentro del directorio

Como el directorio ya tiene los archivos de Docker, instalamos Laravel en una carpeta
temporal y movemos el contenido sin sobrescribir lo existente:

```bash
docker run --rm \
  -v "$PWD:/app" -w /app \
  --user "$(id -u):$(id -g)" \
  composer:2 create-project "laravel/laravel:^13.0" _laravel --prefer-dist --no-interaction

# Mover Laravel a la raíz, no pisar archivos Docker existentes
shopt -s dotglob
cp -rn _laravel/. .
rm -rf _laravel
```

### 3. Levantar los contenedores

```bash
docker compose up -d --build
```

### 4. Instalar dependencias y generar key

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 5. Instalar Filament

```bash
docker compose exec app composer require filament/filament:"^5.0" -W
docker compose exec app php artisan filament:install --panels
docker compose exec app php artisan make:filament-user
```

### 6. Compilar assets (Vite)

```bash
docker compose exec app npm install
docker compose exec app npm run build
```

Para desarrollo con HMR:

```bash
docker compose exec app npm run dev
```

### 7. Acceder

- App: http://crm-meta.localhost
- Panel Filament: http://crm-meta.localhost/admin
- MySQL: localhost:3307 (user `crm`, pass según `.env`)
- Redis: localhost:6380

> `crm-meta.localhost` resuelve a `127.0.0.1` automáticamente vía glibc NSS / systemd-resolved
> (RFC 6761 reserva todo `*.localhost`). **No requiere entrada en `/etc/hosts`.**

## Comandos del día a día

```bash
# Logs
docker compose logs -f app
docker compose logs -f nginx

# Artisan
docker compose exec app php artisan <cmd>

# Composer
docker compose exec app composer <cmd>

# Bash dentro del container app
docker compose exec app sh

# Reiniciar
docker compose restart

# Parar todo
docker compose down

# Parar y borrar volúmenes (datos!)
docker compose down -v
```

## Deploy en Coolify

1. Push del repo a GitHub/GitLab.
2. En Coolify: crear nueva aplicación tipo **Docker Compose** apuntando al repo.
3. Configurar variables de entorno en la UI (basadas en `.env.example`, ajustando
   `APP_ENV=production`, `APP_DEBUG=false`, credenciales reales, etc.).
4. Coolify construye `Dockerfile` con `target=prod`. Override en `docker-compose.prod.yml`
   (a agregar cuando se haga el primer deploy real).
5. Configurar el dominio + Let's Encrypt en Coolify.

## Estructura del repo

```
.
├── Dockerfile                 # Multi-stage: base / dev / build / prod
├── docker-compose.yml         # Stack de desarrollo
├── docker/
│   ├── nginx/
│   │   └── default.conf       # Vhost para Laravel
│   └── php/
│       └── php.ini            # Tuning PHP / OPcache
├── .env.example               # Plantilla compartida Docker + Laravel
├── .gitignore
├── .dockerignore              # Acelera builds, evita filtrar secretos
└── README.md
```

## Próximos pasos

- [ ] Setup inicial Laravel + Filament (sección Bootstrap)
- [ ] Modelo `Lead` con migración pensada para `field_data` de Meta (JSON column)
- [ ] Endpoint `/webhooks/meta/leads` con verificación de firma `X-Hub-Signature-256`
- [ ] Job `ProcessMetaLead` con fetch a Graph API
- [ ] Filament Resource para `Lead` con pipeline kanban
- [ ] Service para `Page Access Token` de larga duración
- [ ] Container queue worker (`php artisan queue:work`) cuando se active el flujo Meta
- [ ] `docker-compose.prod.yml` con target `prod` para Coolify

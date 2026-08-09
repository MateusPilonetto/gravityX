# GravityX

GravityX is a full-stack social application with a Vue frontend and a Laravel REST API.

## Project structure

- `gravityX-frontend/`: Vue 3 + Vite application.
- `gravityX-api/`: Laravel API and database migrations.

## Development environment (Docker)

Prerequisite: Docker Engine with the Docker Compose plugin.

From the repository root, start the development stack with:

```bash
docker compose -f compose.dev.yaml up --build
```

This starts the API with the source code mounted for development and the Vite dev server with hot reload. The default URLs are:

- Frontend: `http://localhost:5173`
- API: `http://localhost:8001`
- Swagger UI: `http://localhost:8001/api/documentation`

Run it in the background by adding `-d`:

```bash
docker compose -f compose.dev.yaml up -d --build
```

Useful development commands:

```bash
docker compose -f compose.dev.yaml ps
docker compose -f compose.dev.yaml logs -f
docker compose -f compose.dev.yaml down
```

The development stack keeps its database, dependencies, and application state in Docker volumes. To remove that local development state and start over, run the following destructive command:

```bash
docker compose -f compose.dev.yaml down -v
```

### Swagger UI

Open `http://localhost:8001/api/documentation` after the development API is running. The OpenAPI document is generated automatically in the development container.

To regenerate it manually after changing the API documentation attributes, run:

```bash
docker compose -f compose.dev.yaml exec api php artisan l5-swagger:generate
```

To call protected endpoints from Swagger UI:

1. Use `POST /api/login` (or `POST /api/register`) and execute the request.
2. Copy the `token` value from the response.
3. Click **Authorize** in Swagger UI and paste the token. The UI adds the `Bearer` prefix automatically.

## Run with Docker (recommended)

Prerequisite: Docker with the Docker Compose plugin installed.

From the repository root, run:

```bash
docker compose up -d --build
```

The full environment is available at:

- Frontend: `http://localhost:8080`
- API: `http://localhost:8000`

Compose uses SQLite in a named volume. On the first run, it creates the application key and database, applies migrations, and creates the public-files symlink automatically. No `.env` files are required for this setup.

Useful commands:

```bash
docker compose ps
docker compose logs -f
docker compose down
```

Ports `8000` and `8080` must be available. To also remove the local Docker data and start from scratch, use the command below — it is destructive to local GravityX data:

```bash
docker compose down -v
```

## Run without Docker

Use two terminals. The backend uses local SQLite, and the frontend connects to `http://localhost:8000/api` by default.

In the first terminal:

```bash
cd gravityX-api
cp .env.example .env
touch database/database.sqlite
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

In the second terminal:

```bash
cd gravityX-frontend
cp .env.example .env
npm ci
npm run dev
```

The development frontend will be available at `http://localhost:5173`.

## Production on Render

The project uses SQLite for local development and supports PostgreSQL in production. For the API, use `gravityX-api/Dockerfile` with the repository root as the build context, because it copies files from that location. Configure at least:

- `APP_ENV=production` and `APP_DEBUG=false`
- `APP_KEY` (generate it once with `php artisan key:generate --show`)
- `APP_URL` with the public API URL
- `FRONTEND_URL` with the public frontend URL
- `DB_CONNECTION=pgsql` and the PostgreSQL-provided `DATABASE_URL`

For the frontend, deploy `gravityX-frontend` as a static site with `npm ci && npm run build` and use `dist` as the publish directory. Set `VITE_API_URL` during the build to `https://<your-api>/api`; without it, the production frontend will attempt to use the local API.

# GravityX

GravityX é uma aplicação social full-stack, com frontend em Vue e uma API REST em Laravel.

## Estrutura

- `gravityX-frontend/`: aplicação Vue 3 + Vite.
- `gravityX-api/`: API Laravel e migrations do banco.

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

## Iniciar com Docker (recomendado)

Pré-requisito: Docker com o plugin Docker Compose instalado.

Na raiz do repositório, execute:

```bash
docker compose up -d --build
```

O ambiente completo fica disponível em:

- Frontend: `http://localhost:8080`
- API: `http://localhost:8000`

O Compose usa SQLite em um volume nomeado. Na primeira execução ele cria a chave da aplicação, o banco, aplica as migrations e cria o link de arquivos públicos automaticamente. Não é necessário criar arquivos `.env` para essa forma de uso.

Comandos úteis:

```bash
docker compose ps
docker compose logs -f
docker compose down
```

As portas `8000` e `8080` precisam estar livres. Para apagar também os dados locais do Docker e começar do zero, use o comando abaixo — ele é destrutivo para os dados locais do GravityX:

```bash
docker compose down -v
```

## Iniciar sem Docker

Use dois terminais. O backend usa SQLite local e o frontend se conecta à API em `http://localhost:8000/api` por padrão.

No primeiro terminal:

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

No segundo terminal:

```bash
cd gravityX-frontend
cp .env.example .env
npm ci
npm run dev
```

O frontend de desenvolvimento ficará em `http://localhost:5173`.

## Produção no Render

O projeto mantém SQLite para o desenvolvimento local e aceita PostgreSQL em produção. Para a API, use o Dockerfile `gravityX-api/Dockerfile` com o contexto de build na raiz do repositório, pois ele copia arquivos a partir dela. Configure ao menos:

- `APP_ENV=production` e `APP_DEBUG=false`
- `APP_KEY` (gere uma vez com `php artisan key:generate --show`)
- `APP_URL` com a URL pública da API
- `FRONTEND_URL` com a URL pública do frontend
- `DB_CONNECTION=pgsql` e `DATABASE_URL` fornecida pelo PostgreSQL

Para o frontend, publique `gravityX-frontend` como site estático, com `npm ci && npm run build` e diretório de publicação `dist`. Defina `VITE_API_URL` durante o build como `https://<sua-api>/api`; sem essa variável, o frontend de produção tentará usar a API local.

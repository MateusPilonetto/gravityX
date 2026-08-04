# Gravityly - Instagram Clone 📸

Final project for the web development module. Gravityly is a full-stack web application inspired by Instagram, reproducing its core social interactions and navigation experiences. 

This repository is a monorepo containing both the Frontend (Vue.js) and the Backend (Laravel REST API), fully containerized with Docker.

## 📂 Project Structure

* **`/gravityly-frontend`**: Vue 3 + Vite application (Single Page Application).
* **`/gravityly-api`**: Laravel 13 REST API with MSC architecture.

> **Note:** Each directory contains its own detailed `README.md` with specific instructions for that environment.

## 🛠 Tech Stack

* **Frontend:** Vue.js 3 (Composition API), Vue Router, Vite.
* **Backend:** PHP, Laravel, Laravel Sanctum (Authentication).
* **Database:** SQLite (Configured for local development).
* **Infrastructure:** Docker, Docker Compose, Nginx.

## 🚀 Quick Start (How to run the entire project)

Ensure you have [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/) installed on your machine.

### 1. Starting the Backend (API & Database)
Open your terminal and execute the following commands to spin up the API:

```bash
cd gravityly-api
cp .env.example .env
docker compose -f compose.dev.yaml up -d --build
docker compose -f compose.dev.yaml exec app composer install
docker compose -f compose.dev.yaml exec app php artisan key:generate
docker compose -f compose.dev.yaml exec app php artisan storage:link
# Migrations run automatically when the development container starts.
# To apply pending migrations without resetting local data:
docker compose -f compose.dev.yaml exec app php artisan migrate --force
```

The API will be available at `http://localhost:8000`.

### 2. Starting the Frontend
In a separate terminal, from the project root:

```bash
cd gravityly-frontend
cp .env.example .env
docker compose up -d --build
```

The app will be available at `http://localhost:8080`.

> **Tip:** while actively developing the frontend, running it directly with `npm install && npm run dev` (instead of through Docker) gives you hot-reload on `http://localhost:5173`.

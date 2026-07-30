# Gravityly - Backend (REST API)

This is the REST API for the Gravityly project (Instagram Clone). Developed in **Laravel** using the **MSC (Model-Service-Controller)** architecture, the application is containerized with Docker to ensure consistency across any environment.

## 🛠 Technologies & Tools

* PHP / Laravel
* Database (SQLite/PostgreSQL/MySQL)
* Docker & Docker Compose
* Laravel Sanctum (SPA Authentication)
* Swagger UI (API Documentation)

## 🚀 How to Run the Development Environment

The local environment is managed by Docker (using `compose.yaml` and `Dockerfile.dev`). Follow the steps below:

1. Clone the repository and access the API folder:
   ```bash
   cd gravityly-api
2. Create a copy of the environment variables file:
    ```bash
    cp .env.example .env
3. Build the images and start the containers in the background (this will spin up both Laravel and the Database):
    ```bash
    docker compose up -d --build
4. Install the PHP dependencies inside the application container:
    ```bash
    docker compose exec app composer install
5. Generate the Laravel application key:
    ```bash
    docker compose exec app php artisan key:generate
6. Run the migrations and seeders to structure and populate the database with test data:
    ```bash
    docker compose exec app php artisan migrate:fresh --seed
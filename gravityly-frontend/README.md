# Gravityly - Frontend

This is the frontend of the Gravityly web application, inspired by Instagram. Developed with **Vue 3** and **Vite**, the project is containerized to ensure standardized execution in any environment.

## 🛠 Technologies Used

* [Vue.js 3](https://vuejs.org/) (Composition API)
* [Vue Router](https://router.vuejs.org/)
* [Vite](https://vitejs.dev/)
* HTML5 / CSS3
* Docker & Docker Compose / Nginx

## 🚀 Prerequisites

To run this project, you will need to have installed on your machine:
* [Docker](https://www.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/)

## ⚙️ How to Run the Project

The project uses a multi-stage Dockerfile (Node.js for build and Nginx to serve the application). 

1. Clone the repository and access the frontend folder:
   ```bash
   cd gravityly-frontend
   ```bash
   docker compose build
   ```bash
   docker compose up -d
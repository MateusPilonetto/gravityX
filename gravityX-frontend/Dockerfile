# STAGE 1: Application Build
FROM node:22-alpine AS builder

WORKDIR /app

# Copy dependency manifests and install
COPY package*.json ./
RUN npm install

# Copy the rest of the source and run the Vite build
COPY . .

# Allows the API URL to be set at build time (defaults to the local dev API)
ARG VITE_API_URL=http://localhost:8000/api
ENV VITE_API_URL=$VITE_API_URL

RUN npm run build

# STAGE 2: Nginx Web Server
FROM nginx:alpine

# Copy the files generated in the build stage into Nginx
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy the custom Nginx config so client-side routing (Vue Router) works
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expose the container's port 80
EXPOSE 80

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]

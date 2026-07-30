# ESTÁGIO 1: Build da Aplicação
FROM node:20-alpine AS builder

WORKDIR /app

# Copia os arquivos de dependência e instala
COPY package*.json ./
RUN npm install

# Copia o restante do código e executa o build do Vite
COPY . .
RUN npm run build

# ESTÁGIO 2: Servidor Web Nginx
FROM nginx:alpine

# Copia os arquivos gerados no estágio de build para o Nginx
COPY --from=builder /app/dist /usr/share/nginx/html

# Copia a configuração personalizada do Nginx para suportar o Vue Router
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expõe a porta 80 do contêiner
EXPOSE 80

# Inicia o Nginx
CMD ["nginx", "-g", "daemon off;"]
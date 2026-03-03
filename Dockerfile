# ---- Stage 1: Build the React frontend ----
FROM node:18-alpine AS frontend-builder

WORKDIR /app

COPY frontend/package*.json ./
RUN npm install

COPY frontend/ ./

# API is served from the same origin at /api so a relative path works on any domain
ENV REACT_APP_API_URL=/api
RUN npm run build

# ---- Stage 2: PHP + Apache with PostgreSQL support ----
FROM php:8.2-apache

# Install the PostgreSQL client library and PDO extension
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules needed for routing
RUN a2enmod rewrite alias

# Replace the default Apache virtual-host config
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# PHP backend
COPY backend/ /var/www/backend/

# Pre-built React app (served as static files)
COPY --from=frontend-builder /app/build /var/www/html

# Entrypoint: initialise DB schema then hand off to apache2-foreground
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R www-data:www-data /var/www

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]

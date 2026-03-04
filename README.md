# EnebaHW – Game Store

A full-stack web application that displays games and supports fuzzy search via **Levenshtein distance**.

| Layer    | Technology |
|----------|-----------|
| Frontend | React (CRA) |
| Backend  | PHP 8 + Slim Framework 4 |
| Database | PostgreSQL |

---

## Quick Start

### 1. Install backend dependencies

```bash
cd backend
composer install
```

### 2. Initialize the database

```bash
php init_db.php
```

This creates the `platforms`, `regions`, and `games` tables in PostgreSQL and seeds them with data.

### 3. Start the PHP development server

```bash
cd backend/public
php -S localhost:8080
```

The API is now available at `http://localhost:8080/api/`.

### 4. Start the React dev server

In a separate terminal:

```bash
cd frontend
npm install   # first time only
npm start
```

Open `http://localhost:3000` in your browser.

---

## API Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/games?page=1&limit=20` | Paginated game list |
| GET | `/api/list?page=1&limit=20` | Paginated game list |
| GET | `/api/list?search=<query>` | Levenshtein-distance search |
| GET | `/api/search?q=<query>` | Levenshtein-distance search |

---

## Database Schema

```sql
CREATE TABLE platforms (
    platform_id   SERIAL PRIMARY KEY,
    platform_name VARCHAR(255) NOT NULL
);

CREATE TABLE regions (
    region_id   SERIAL PRIMARY KEY,
    region_name VARCHAR(255) NOT NULL
);

CREATE TABLE games (
    game_id     SERIAL PRIMARY KEY,
    game_name   VARCHAR(255) NOT NULL,
    price       FLOAT        NOT NULL,
    discount    INTEGER,
    details     VARCHAR      NOT NULL,
    image_url   VARCHAR(512),
    platform_id INTEGER      NOT NULL,
    region_id   INTEGER      NOT NULL,
    FOREIGN KEY (platform_id) REFERENCES platforms(platform_id),
    FOREIGN KEY (region_id)   REFERENCES regions(region_id)
);
```

Mock data includes three games across multiple platforms and regions:
- **EA SPORTS FIFA 23**
- **Red Dead Redemption 2**
- **Split Fiction**

---

## Features

- 🔍 **Search** – real-time Levenshtein-distance fuzzy matching (backend)
- 📐 **Grid layout** – 4 cards per row, 5 rows shown initially
- 📱 **Responsive** – adapts to mobile/tablet/desktop via CSS Grid
- ⬇️ **Load More** – pagination for browsing additional games

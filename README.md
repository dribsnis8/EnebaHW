# EnebaHW – Game Store

A full-stack web application that displays games and supports fuzzy search via **Levenshtein distance**.

| Layer    | Technology |
|----------|-----------|
| Frontend | React (CRA) |
| Backend  | PHP 8 |
| Database | SQLite 3 |

---

## Quick Start

### 1. Initialize the database

```bash
cd backend
php init_db.php
```

This creates `backend/database/games.db` and seeds it with platforms and games.

### 2. Start the PHP development server

```bash
cd backend
php -S localhost:8080
```

The API is now available at `http://localhost:8080/api/`.

### 3. Start the React dev server

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
| GET | `/api/games.php?page=1&limit=20` | Paginated game list |
| GET | `/api/search.php?q=<query>` | Levenshtein-distance search |

---

## Database Schema

```sql
CREATE TABLE platforms (
    platform_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    platform_name VARCHAR(255) NOT NULL
);

CREATE TABLE games (
    game_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    game_name   VARCHAR(255) NOT NULL,
    region      VARCHAR(255) NOT NULL,
    price       FLOAT        NOT NULL,
    discount    INTEGER,               -- nullable
    details     VARCHAR      NOT NULL,
    platform_id INTEGER      NOT NULL,
    FOREIGN KEY (platform_id) REFERENCES platforms(platform_id)
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

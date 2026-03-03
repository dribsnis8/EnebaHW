#!/bin/sh
set -e

# Initialize (or verify) the PostgreSQL schema and seed data
echo "Running database initialisation..."
if ! php /var/www/backend/init_db.php; then
    echo "ERROR: Database initialisation failed" >&2
    exit 1
fi

# Start Apache
exec "$@"

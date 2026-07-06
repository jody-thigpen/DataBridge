# Environment Variables

Reference for DataBridge-specific `.env` keys. See `.env.example` for the full template.

**Never commit `.env` or secret files.**

## Application

| Variable | Purpose |
|----------|---------|
| `APP_NAME` | Display name (default: DataBridge) |
| `APP_URL` | Canonical URL — must match production domain over HTTPS |
| `APP_DEBUG` | `false` in production |

## Databases

DataBridge uses two MariaDB/MySQL databases on the same remote host for development (pattern matches Shoeventory's remote dev DB). Production may move the data database to a separate provider later.

| Variable | Purpose |
|----------|---------|
| `DB_CONNECTION` | `mysql` for dev/production |
| `DB_HOST`, `DB_PORT` | Remote MariaDB host (shared by both databases in dev) |
| `DB_DATABASE` | App database (`saffhire2_db_app`) — users, orgs, orders, billing refs |
| `DB_USERNAME`, `DB_PASSWORD` | Credentials for the app database |
| `MYSQL_ATTR_SSL_CA` | Optional CA bundle for remote DB SSL |
| `DB_DATA_CONNECTION` | Laravel connection name for PII data (`data`) |
| `DB_DATA_DATABASE` | Secured data database (`saffhire2_db_data`) |
| `DB_DATA_USERNAME`, `DB_DATA_PASSWORD` | Credentials for the data database (can match app in dev) |
| `DB_DATA_HOST`, `DB_DATA_PORT` | Override only when data DB moves to a different host |

PHPUnit uses in-memory SQLite for both connections via `phpunit.xml` — no `.env` change needed for tests.

## Session & queue

| Variable | Purpose |
|----------|---------|
| `SESSION_DRIVER` | `database` (stored on app DB) |
| `QUEUE_CONNECTION` | `database` for dev |
| `CACHE_STORE` | `database` for dev |

## Migrations

```bash
# App tables (default connection)
php artisan migrate

# Secured PII / consumer data tables
php artisan migrate --database=data --path=database/migrations/data

# Both
php artisan migrate:all
```

## Related

- [DEVELOPMENT.md](DEVELOPMENT.md) — local setup

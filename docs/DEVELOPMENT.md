# Development

## Requirements

- PHP 8.3+
- Composer
- Node.js (for Vite assets)
- Access to remote MariaDB (same host pattern as Shoeventory)

## First-time setup

1. Copy `.env.example` to `.env` if needed and set database credentials.
2. `composer install`
3. `php artisan key:generate` (if `APP_KEY` is empty)
4. `php artisan migrate:all`
5. `npm install && npm run dev` (optional, for frontend assets)

## Databases

| Connection | Database | Stores |
|------------|----------|--------|
| `mysql` (default) | `saffhire2_db_app` | Application data |
| `data` | `saffhire2_db_data` | Secured consumer / PII payloads |

Both databases live on the remote MariaDB server during development. The data connection can be pointed at a different host when you move to a dedicated PII provider.

Models that store secured consumer data should use `App\Models\Concerns\UsesDataConnection` and live under `app/Models/Data/`. Migrations for those tables go in `database/migrations/data/`.

## Tests

PHPUnit uses in-memory SQLite for both connections. Run:

```bash
php artisan test
```

## Application UI

Login at `/login` (public self-registration is disabled).

```bash
php artisan serve
```

Open http://127.0.0.1:8000/login and sign in with your seeded super admin account (`SUPER_ADMIN_EMAIL` in `.env`).

### Navigation

- **Desktop / large tablet:** fixed left sidebar
- **Mobile:** hamburger menu with slide-in sidebar

### Areas

| Audience | Features |
|----------|----------|
| **Saffhire staff** | Clients list, platform user management, masquerade as any client user |
| **Client users** | Report requests, reports (placeholder), org profile, billing (placeholder), team users |

Platform staff click **Enter organization** on a client to work in that client's context, or **Masquerade** to sign in as a specific client user.

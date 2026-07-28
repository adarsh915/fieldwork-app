# AMPL Fieldwork — Laravel Backend

Laravel 12 admin panel and API for the AMPL Fieldwork field-sales CRM. Uses **Spatie laravel-permission** for role-based access control.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+
- Node.js (for Vite assets)

## Initial setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` database settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=field_work_crm
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations, seeders, and link storage:

```bash
php artisan migrate
php artisan db:seed --class=TourMasterDataSeeder
php artisan db:seed --class=DayStartPermissionsSeeder
php artisan storage:link
npm install
npm run build
php artisan serve
```

Admin panel: `http://127.0.0.1:8000/admin/login`

---

## Day Start / Tour Plan module

Full implementation of the Day Start / Tour Plan workflow for field-sales employees.

### Features

- Employees submit a daily tour plan (one record per user per day)
- Conditional form fields driven by master-data flags (not hardcoded in JS)
- Browser geolocation captured automatically on submit
- Opening KM photo upload with preview
- Manager receives a database notification when a team member submits
- Role-based list scoping: employee → own, manager → team, admin → all

### Database changes

| Migration | Description |
|-----------|-------------|
| `2026_06_19_000001_add_reporting_to_to_users_table` | Adds nullable `reporting_to` FK on `users` for manager hierarchy |
| `2026_06_19_000002_create_tour_purposes_table` | Master data: `name`, `requires_party`, `is_active` |
| `2026_06_19_000003_create_tour_types_table` | Master data: `name`, `requires_vehicle`, `is_active` |
| `2026_06_19_000004_create_vehicle_types_table` | Master data: `name`, `requires_vehicle_no`, `is_active` |
| `2026_06_19_000005_create_day_starts_table` | Tour plan records with unique `(user_id, date)` |
| `2026_06_19_000006_create_notifications_table` | Laravel database notifications |
| `2026_06_19_000007_create_customer_user_table` | Pivot: assign parties (customers) to users |

**`day_starts` columns:** `user_id`, `date`, `tour_purpose_id`, `tour_type_id`, `vehicle_type_id`, `vehicle_no`, `visit_place`, `party_id` (FK → `customers`), `opening_km`, `opening_km_image`, `start_lat`, `start_lng`, `status` (`submitted` / `approved` / `flagged`).

**Note:** The existing `customers` table is reused for party selection (`id`, `name`).

### Seeders

**`TourMasterDataSeeder`** — initial master data:

- **Tour purposes:** Party Visit (requires party), Field Visit, Office Visit, Work From Home, Other
- **Tour types:** In Headquarter, Out of Headquarter, Same Day Return, Tour From Home, Work From Home (no vehicle), Other
- **Vehicle types:** Two Wheeler Personal, Four Wheeler Personal, Two Wheeler Company, Four Wheeler Company, Multi Vehicle, Work From Home (no vehicle no), Other

**`DayStartPermissionsSeeder`** — permissions and role assignments:

| Permission | Admin | Manager | Employee |
|------------|:-----:|:-------:|:--------:|
| `master-data.manage` | ✓ | | |
| `day-start.create` | ✓ | ✓ | ✓ |
| `day-start.view-all` | ✓ | | |
| `day-start.view-team` | | ✓ | |
| `day-start.view-own` | | | ✓ |
| `day-start.approve` | ✓ | ✓ | |

Also granted to **Super Admin**. Lowercase role aliases (`admin`, `manager`, `employee`) are supported if present.

### Models

| Model | Relationships |
|-------|---------------|
| `TourPurpose` | hasMany `DayStart` |
| `TourType` | hasMany `DayStart` |
| `VehicleType` | hasMany `DayStart` |
| `DayStart` | belongsTo `User`, `TourPurpose`, `TourType`, `VehicleType`, `Customer` (as `party`) |
| `User` | belongsTo `manager`, hasMany `teamMembers`, hasMany `dayStarts` |

### Trait: `App\Traits\HasRoleBasedScope`

Used on `DayStart` model. Method `scopeForUser($query, $user, $ownColumn = 'user_id')`:

- **Admin / Super Admin** — no filter (sees all)
- **Manager** — `whereIn(user_id, team member IDs + self)`
- **Employee** — `where(user_id, self)`

### Controllers

| Controller | Purpose |
|------------|---------|
| `MasterDataController` | CRUD for tour purposes, tour types, vehicle types (via `{type}` route param) |
| `DayStartController` | index, create, store, show with role scoping and conditional validation |
| `NotificationController` | mark single / all notifications as read |

### Notification

`App\Notifications\TourPlanSubmitted` — database channel. Sent to `$user->manager` on day-start submit. Message format: *"{employee name} submitted a tour plan for {date} — {tour purpose}"*.

### Routes (`routes/web.php`)

All under `admin` prefix with `auth` middleware:

```
GET    admin/master-data/{type}              master-data.index   [permission:master-data.manage]
POST   admin/master-data/{type}              master-data.store
PUT    admin/master-data/{type}/{id}           master-data.update
DELETE admin/master-data/{type}/{id}         master-data.destroy

GET    admin/day-starts                        day-starts.index
GET    admin/day-starts/create                 day-starts.create   [permission:day-start.create]
POST   admin/day-starts                        day-starts.store    [permission:day-start.create]
GET    admin/day-starts/{id}                   day-starts.show

POST   admin/notifications/{id}/read           notifications.read
POST   admin/notifications/read-all            notifications.read-all
```

`{type}` values: `tour-purposes`, `tour-types`, `vehicle-types`

### Views

| View | Description |
|------|-------------|
| `admin/master-data/index.blade.php` | Master data CRUD with type tabs |
| `admin/day-starts/index.blade.php` | Paginated list with date/employee filters |
| `admin/day-starts/create.blade.php` | Tour plan form with conditional fields + geolocation |
| `admin/day-starts/show.blade.php` | Detail view |
| `admin/partials/notifications-bell.blade.php` | Header notification bell |

Sidebar links:
- **Day Start** — visible with `day-start.view-own`, `day-start.view-team`, or `day-start.view-all`
- **Master Data** — visible with `master-data.manage` (admin only)

### Validation rules

- `tour_purpose_id`, `tour_type_id`, `vehicle_type_id` — always required
- `party_id` — required when selected tour purpose has `requires_party = true`
- `vehicle_no` — required when selected vehicle type has `requires_vehicle_no = true` (skipped for Work From Home purpose)
- `opening_km`, `opening_km_image` — required when tour type `requires_vehicle = true` AND vehicle type `requires_vehicle_no = true` (skipped for Work From Home purpose/type)
- One day start per employee per day (unique constraint)
- Photos stored in `storage/app/public/day-start-photos`

### Manager hierarchy

Set `users.reporting_to` to a manager's `users.id` for each employee. Example:

```sql
UPDATE users SET reporting_to = 2 WHERE id IN (3, 4, 5);
```

### Party assignment (user → party dropdown)

Parties (customers) can be assigned to individual employees. Only assigned parties appear in that employee's **Day Start → Party** dropdown.

**Where to assign:** Admin → **Employees** → **Edit Employee** (or Create Employee) → **Assigned Parties** section.

- Check the parties this employee is allowed to visit.
- Save the employee record.

**How it works:**

| Role | Party dropdown shows |
|------|----------------------|
| Employee / Manager / Field Worker | Only parties assigned to that user |
| Admin / Super Admin | All customers |

**Database:** pivot table `customer_user` (`user_id`, `customer_id`).

**Validation:** On Day Start submit, `party_id` must belong to the logged-in user's assigned parties (unless admin).

---

## Troubleshooting

### `Table 'notifications' doesn't exist`

The Day Start module added a `notifications` migration. Run:

```bash
php artisan migrate
```

Or run only pending migrations:

```bash
php artisan migrate:status
php artisan migrate --force
```

If the bell icon shows a warning, migrations have not been applied yet.

### `Table 'day_starts' doesn't exist` / other missing tables

Run the full migration set:

```bash
php artisan migrate
php artisan db:seed --class=TourMasterDataSeeder
php artisan db:seed --class=DayStartPermissionsSeeder
```

### Opening KM photos not displaying

Ensure the storage symlink exists:

```bash
php artisan storage:link
```

### Permission denied on Day Start pages

Re-run the permissions seeder:

```bash
php artisan db:seed --class=DayStartPermissionsSeeder
php artisan permission:cache-reset
```

---

## Existing roles

The project uses Spatie roles (capitalized):

- **Super Admin** — all permissions
- **Admin** — all permissions including master data and day-start view-all
- **Manager** — team day-start view, approve, create
- **Employee** — own day-start view and create
- **Field Worker**, **Finance** — other module permissions (unchanged by Day Start seeder)

---

## License

MIT

# Employee Task Management

A Laravel application for assigning employee tasks, recording daily work, reviewing team progress, exporting reports, and creating performance evaluations. The application uses the existing Laravel authentication flow and Spatie roles and permissions.

## Features

- Role based access for `Admin`, `Manager`, and `Employee`.
- Task assignment, search, status and priority filters, pagination, task details, and status updates.
- Team-scoped manager access and employee access limited to the employee's own assigned tasks and work updates.
- Daily work updates with completed work, pending work, blockers, work date, and an optional related task.
- A data-backed dashboard with task totals, workload, recent updates, and upcoming deadlines.
- Employee-wise task reports with date filters and CSV export.
- Period evaluations with calculated task metrics, a manager rating, and written feedback.
- Admin user access page for assigning one of the fixed roles and linking employees to managers.

## Technology and prerequisites

- PHP 8.3 or later and Composer.
- Laravel 13, Fortify 1, Livewire 4, Flux 2, and Spatie Laravel Permission 8.
- Node.js and npm for the Vite/Tailwind frontend build.
- SQLite is the default database in `.env.example`. Other configured Laravel database connections can be selected in `.env`.

## Install and configure

From the project root after opening or cloning the repository:

```sh
composer install
npm ci
cp .env.example .env
```

Edit `.env` for the local environment. The example is configured for SQLite. For the default SQLite database, create the file if it does not already exist:

```sh
touch database/database.sqlite
```

For another database, set the appropriate existing Laravel `DB_*` values in `.env` and create the database using your database server. Do not commit `.env` or expose its contents.

Generate the application key, apply the additive migrations, and seed roles and development accounts:

```sh
php artisan key:generate
php artisan migrate
php artisan db:seed
```

`DatabaseSeeder` calls seeders in this dependency order: `RolePermissionSeeder`, `AdminSeeder`, then `EmployeeSeeder`. These seeders are repeatable; they do not duplicate the known accounts or reset existing account details or passwords. `RolePermissionSeeder` synchronizes the predefined permissions on the built-in roles.

Build frontend assets:

```sh
npm run build
```

## Development accounts

These credentials are for local development only. Change them before sharing a development database, and never use them in production.

| Role | Email | Default password |
| --- | --- | --- |
| Admin | `admin@example.com` | `Admin@12345` |
| Employee | `employee1@example.com` | `Employee@12345` |
| Employee | `employee2@example.com` | `Employee@12345` |
| Employee | `employee3@example.com` | `Employee@12345` |

Open `/login` and sign in with one of these accounts. The employee password is set in `database/seeders/EmployeeSeeder.php` and is explicitly a development sample credential.

## Roles and workflows

### Admin

The Admin role receives every permission declared in `RolePermissionSeeder`. Admins can access all task, update, report, and evaluation records, and can open **User access** at `/admin/users` to assign a user one of the fixed roles and link employees to a manager. Role names and the permission set are currently defined by the seeder; the application does not provide a screen for creating roles or editing permission definitions.

From the dashboard, admins can review task totals and activity. The **Tasks** page supports task creation and management across employees. **Reports** provides employee statistics and date filters; **Export CSV** downloads the same filtered report. **Evaluations** lists and creates performance evaluations.

### Manager

Manager accounts are not created by the development seeders. An Admin can open **User access**, assign a user the `Manager` role, and select that manager for each employee. Managers can create and assign tasks to employees linked to their account, update or delete authorized team tasks, review team daily updates, filter their dashboard and reports, export team reports, and create evaluations for their employees.

### Employee

Employees can view their assigned tasks, update task status, submit daily work updates, and review their own update history and evaluations. Employees cannot change task assignment, priority, or description. A daily update can be edited by its author for up to seven days after its work date. Updates belonging to other employees are not available to employees.

## Task statuses and metrics

- Statuses: `To Do`, `In Progress`, `Completed`, and `Blocked`.
- Priorities: `Low`, `Medium`, `High`, and `Urgent`.
- A task is overdue when its due date is before today and its status is not `Completed`. Overdue is calculated from the task data; there is no stored overdue flag.
- Moving a task to `Completed` records its completion timestamp. Reopening it clears that timestamp.
- Dashboard date filters and report date ranges use task creation dates. Evaluation task counts use tasks created within the selected evaluation period. Evaluations store a snapshot of assigned, completed, overdue, and completion-rate metrics alongside the manager's separate 1–5 rating and written feedback.

## CSV reports

Go to **Reports**, optionally filter by employee and date range, then choose **Export CSV**. The export applies the same employee access scope and filters as the report page. Text values beginning with spreadsheet formula characters are escaped before export.

## Run the application and tests

Run the Laravel development server:

```sh
php artisan serve
```

In another terminal, run the frontend development server when changing frontend assets:

```sh
npm run dev
```

Run the automated tests:

```sh
php artisan test --compact
```

The suite includes task assignment and team-boundary checks, completion and overdue behavior, daily update privacy/submission, and idempotent role/account seeding.

## Troubleshooting

- **Database connection errors:** confirm `DB_CONNECTION` and the matching `DB_*` settings in `.env`. For SQLite, ensure `database/database.sqlite` exists and is writable.
- **Missing tables:** run `php artisan migrate` against the configured database. This applies pending migrations without deleting existing records; do not use destructive reset commands on databases with data.
- **Missing roles or permissions:** run `php artisan db:seed --class=RolePermissionSeeder`, or run `php artisan db:seed` to execute the complete ordered setup. Clear cached configuration with `php artisan config:clear` if configuration was changed.
- **Cannot sign in:** verify the email/password, confirm the application points to the database where seeders were run, and check that the user exists. The known seeders do not reset passwords for existing accounts.
- **Manager sees no team members:** use the Admin **User access** page to assign the `Manager` role and link each employee to that manager.
- **Frontend changes do not appear:** run `npm run build` for a production asset build or `npm run dev` while developing.

## Security and known limitations

- Change all sample passwords and never use sample credentials outside local development. Keep `.env`, database files, and credentials private.
- Manager and employee access is enforced on the server and scoped using the employee's `manager_id` relationship.
- The project currently supports the three predefined role names. It does not have a general role/permission editor or a task status audit-history log.
- Evaluation metrics are captured when the evaluation is created; they are a snapshot and do not recalculate later if tasks change.
- The setup includes sample employees but does not create a Manager account; configure managers through the Admin user access page.

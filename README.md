# Project Management System (PMS) API

Hey there! This is a robust Laravel-based API for managing projects, tasks, teams, and more. Built with Laravel 12, it includes authentication via Sanctum tokens or Google OAuth, role-based permissions, and all the bells and whistles for a modern PMS. Whether you're building a frontend app or integrating with other tools, this API has you covered.

**Live Demo**: Check out the live version at [pms.kamash.ly](https://pms.kamash.ly) to see it in action!

## Features

- **User Authentication**: Register, login, logout with email/password or Google sign-in.
- **Role-Based Access Control (RBAC)**: Permissions for managing projects, tasks, tags, etc., using Spatie Laravel Permission.
- **Projects & Tasks**: Full CRUD with filtering, soft deletes, and bulk operations.
- **Comments & Attachments**: Polymorphic comments on projects/tasks, file uploads with private storage.
- **Tags**: Assign tags to tasks for better organization.
- **Queues & Caching**: Background jobs for notifications, Redis/database caching for performance.
- **Testing**: Comprehensive tests with Pest/PhpUnit.
- **API-First**: JSON responses, bearer token auth, pagination, and error handling.

## Tech Stack

- **Laravel 12** (PHP 8.2+)
- **Sanctum** for API token authentication
- **Spatie Laravel Permission** for roles/permissions
- **MySQL** (or compatible) for database
- **Redis** for caching/queues (optional)
- **Pest/PhpUnit** for testing
- **Socialite** for Google OAuth

## Installation & Setup

Getting started is straightforward. Make sure you have PHP 8.2+, Composer, and Node.js installed.

1. **Clone the repo**:
   ```bash
   git clone <your-repo-url>
   cd pms
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Update `.env` with your database, mail, and Google OAuth credentials (see below).

4. **Database**:
   ```bash
   php artisan migrate --seed
   ```
   This sets up tables and seeds sample data (users, roles, etc.).

5. **Permissions** (if on Linux/Mac):
   ```bash
   chown -R www-data:www-data storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache
   ```

6. **Run the app**:
   ```bash
   php artisan serve  # For local dev
   php artisan queue:work  # For background jobs
   ```

Your API will be at `http://localhost:8000`. For production, deploy to a server with proper permissions and env vars.

## Authentication

We support two ways to authenticate: traditional email/password and Google OAuth.

### Email/Password Auth

- **Register**: `POST /api/auth/register` with `name`, `email`, `password`.
- **Login**: `POST /api/auth/login` with `email`, `password`.
- **Logout**: `POST /api/auth/logout` (requires token).
- **Get User**: `GET /api/auth/me` (requires token).

Example login response:
```json
{
  "user": { "id": 1, "name": "John Doe", "email": "john@example.com" },
  "token": "your-bearer-token-here"
}
```

Use the token in headers: `Authorization: Bearer <token>`.

### Google Sign-In

For a seamless login experience:

1. Set up Google OAuth in your Google Console (get client ID/secret).
2. Add to `.env`:
   ```
   GOOGLE_CLIENT_ID=your-client-id
   GOOGLE_CLIENT_SECRET=your-secret
   GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
   ```

3. Redirect users to `GET /auth/google` (web route, not API).
4. Google redirects back to `GET /auth/google/callback`, which returns a JSON token like above.

Note: This creates users automatically if they don't exist. No password needed!

## API Endpoints

All API endpoints are under `/api/` and return JSON. Protected routes need `Authorization: Bearer <token>`. We use pagination for lists, and responses include error details.

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user

### Projects
- `GET /api/projects` - List projects (with filters: `?status=active&name=foo`)
- `POST /api/projects` - Create project (perms: manage_projects)
- `GET /api/projects/{id}` - Get project details
- `PUT /api/projects/{id}` - Update project
- `DELETE /api/projects/{id}` - Delete project
- `POST /api/projects/{id}/restore` - Restore deleted project
- `POST /api/projects/{id}/members` - Add member to project
- `DELETE /api/projects/{id}/members` - Remove member

Example project create:
```json
{
  "name": "New Project",
  "description": "Project details",
  "status": "active"
}
```

### Tasks
- `GET /api/tasks` - List tasks (filters: `?project_id=1&status=pending&assigned_to=2`)
- `POST /api/tasks` - Create task (perms: manage_tasks)
- `GET /api/tasks/{id}` - Get task
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task
- `POST /api/tasks/{id}/restore` - Restore task
- `PATCH /api/tasks/{id}/status` - Update status
- `POST /api/tasks/bulk-status` - Bulk update statuses

### Comments
- `POST /api/comments` - Add comment (to project or task)
- `PUT /api/comments/{id}` - Edit comment
- `DELETE /api/comments/{id}` - Delete comment

Example:
```json
{
  "content": "This is a comment",
  "commentable_type": "App\\Models\\Project",
  "commentable_id": 1
}
```

### Tags
- `GET /api/tags` - List tags
- `POST /api/tags` - Create tag (perms: manage_tags)
- `GET /api/tags/{id}` - Get tag
- `PUT /api/tags/{id}` - Update tag
- `DELETE /api/tags/{id}` - Delete tag
- `POST /api/tasks/{task}/tags/{tag}` - Assign tag to task
- `DELETE /api/tasks/{task}/tags/{tag}` - Unassign tag

### Attachments
- `POST /api/attachments` - Upload file (multipart/form-data)
- `DELETE /api/attachments/{id}` - Delete file

Files are stored privately; access via signed URLs if needed.

## Testing

We use Pest for tests. Run them with:

```bash
php artisan test  # Or vendor/bin/pest
```

Key test files:
- `tests/Feature/AuthTest.php` - Auth endpoints
- `tests/Feature/ProjectTest.php` - Project CRUD
- `tests/Feature/TaskTest.php` - Task operations
- `tests/Unit/` - Unit tests

Tests cover happy paths, permissions, validation, and edge cases. Use `--filter` to run specific tests.

For API testing, check `docs/postman_collection.json` for a Postman collection with all endpoints.

## Deployment

- Set `APP_ENV=production` in `.env`.
- Run `php artisan config:cache`, `php artisan route:cache`, etc.
- Use a process manager like Supervisor for queues.
- Ensure storage permissions as above.
- For Google OAuth, set redirect URI to your production domain.

## Common Issues

- **500 Error**: Check logs in `storage/logs/laravel.log`. Often permissions or missing env vars.
- **Auth Fails**: Verify token format and Sanctum config.
- **Google OAuth**: Ensure stateless mode if sessions are flaky.
- **Permissions**: Seed roles/permissions with `php artisan db:seed`.

## Contributing

Feel free to open issues or PRs. Follow Laravel conventions, write tests, and keep it clean!

## License

MIT License. Go build something awesome!

**401 Unauthorized**
```json
{ "message": "Unauthenticated." }
```

**403 Forbidden**
```json
{ "message": "Forbidden" }
```
or
```json
{ "message": "Insufficient permissions" }
```

**422 Validation error**
```json
{
	"message": "The given data was invalid.",
	"errors": {
		"field": ["Validation message..."]
	}
}
```

---

### Auth

**POST `/api/auth/register`**

Request JSON:
```json
{
	"name": "string",
	"email": "string (email)",
	"password": "string (min 8)"
}
```

Response `201`:
```json
{
	"user": {
		"id": 1,
		"name": "string",
		"email": "string"
	},
	"token": "string"
}
```

**POST `/api/auth/login`**

Request JSON:
```json
{
	"email": "string (email)",
	"password": "string"
}
```

Response `200`:
```json
{
	"user": {
		"id": 1,
		"name": "string",
		"email": "string"
	},
	"token": "string"
}
```

Response `401`:
```json
{ "message": "Invalid credentials" }
```

**POST `/api/auth/logout`** (protected)

Request body: none

Response `204`: no content

**GET `/api/auth/me`** (protected)

Request body: none

Response `200` (user object):
```json
{
	"id": 1,
	"name": "string",
	"email": "string"
}
```

---

### Projects (protected)

**GET `/api/projects`**

Query params (all optional):
```json
{
	"name": "string",
	"status": "draft|active|archived",
	"owner_id": 1,
	"starts_at_from": "YYYY-MM-DD",
	"starts_at_to": "YYYY-MM-DD",
	"ends_at_from": "YYYY-MM-DD",
	"ends_at_to": "YYYY-MM-DD",
	"with_trashed": true
}
```

Response `200`:
```json
{
	"data": {
		"data": [
			{
				"id": 1,
				"name": "string",
				"description": "string|null",
				"status": "draft|active|archived|null",
				"starts_at": "datetime|null",
				"ends_at": "datetime|null",
				"owner": { "id": 1, "name": "string|null", "email": "string|null" },
				"members_count": 0,
				"tasks_count": 0,
				"created_at": "datetime",
				"updated_at": "datetime",
				"deleted_at": "datetime|null"
			}
		]
	},
	"meta": { "current_page": 1, "last_page": 1, "total": 1 }
}
```

**POST `/api/projects`** (requires `manage_projects`)

Request JSON:
```json
{
	"name": "string",
	"description": "string|null",
	"status": "draft|active|archived|null",
	"starts_at": "date|null",
	"ends_at": "date|null",
	"owner_id": 1
}
```

Response `201` (ProjectResource):
```json
{
	"id": 1,
	"name": "string",
	"description": "string|null",
	"status": "draft|active|archived|null",
	"starts_at": "datetime|null",
	"ends_at": "datetime|null",
	"owner": { "id": 1, "name": "string|null", "email": "string|null" },
	"members_count": null,
	"tasks_count": null,
	"created_at": "datetime",
	"updated_at": "datetime",
	"deleted_at": null
}
```

**GET `/api/projects/{project}`**

Request body: none

Response `200` (ProjectResource): same shape as above.

Response `403` when not owner/member:
```json
{ "message": "Forbidden" }
```

**PUT `/api/projects/{project}`** (requires `manage_projects`)

Request JSON (all optional):
```json
{
	"name": "string",
	"description": "string|null",
	"status": "draft|active|archived",
	"starts_at": "date|null",
	"ends_at": "date|null",
	"owner_id": 1
}
```

Response `200` (ProjectResource)

**DELETE `/api/projects/{project}`** (requires `manage_projects`)

Request body: none

Response `204`: no content

**POST `/api/projects/{id}/restore`** (requires `manage_projects`)

Request body: none

Response `200` (ProjectResource)

**POST `/api/projects/{project}/members`** (requires `manage_projects`)

Request JSON:
```json
{ "user_id": 1, "role": "project_manager|member" }
```

Response `200`:
```json
{ "message": "Member added" }
```

**DELETE `/api/projects/{project}/members`** (requires `manage_projects`)

Request JSON:
```json
{ "user_id": 1 }
```

Response `200`:
```json
{ "message": "Member removed" }
```

---

### Tasks (protected)

**GET `/api/tasks`**

Query params (all optional):
```json
{
	"status": "todo|in_progress|done|blocked",
	"priority": "low|medium|high",
	"assignee_id": 1,
	"project_id": 1,
	"due_date_from": "YYYY-MM-DD",
	"due_date_to": "YYYY-MM-DD",
	"tag": "string (tag slug)",
	"with_trashed": true
}
```

Response `200`:
```json
{
	"data": {
		"data": [
			{
				"id": 1,
				"project_id": 1,
				"assignee": { "id": 1, "name": "string", "email": "string" },
				"title": "string",
				"description": "string|null",
				"status": "todo|in_progress|done|blocked|null",
				"priority": "low|medium|high|null",
				"due_date": "datetime|null",
				"completed_at": "datetime|null",
				"parent_id": 1,
				"tags": ["string"],
				"created_at": "datetime",
				"updated_at": "datetime",
				"deleted_at": "datetime|null"
			}
		]
	},
	"meta": { "current_page": 1, "last_page": 1, "total": 1 }
}
```

**POST `/api/tasks`** (requires `manage_tasks`)

Request JSON:
```json
{
	"project_id": 1,
	"assignee_id": 1,
	"title": "string",
	"description": "string|null",
	"status": "todo|in_progress|done|blocked|null",
	"priority": "low|medium|high|null",
	"due_date": "date|null",
	"parent_id": 1
}
```

Response `201` (TaskResource)

**GET `/api/tasks/{task}`**

Request body: none

Response `200` (TaskResource)

Response `403` when not in project:
```json
{ "message": "Forbidden" }
```

**PUT `/api/tasks/{task}`** (requires `manage_tasks`)

Request JSON (all optional):
```json
{
	"project_id": 1,
	"assignee_id": 1,
	"title": "string",
	"description": "string|null",
	"status": "todo|in_progress|done|blocked",
	"priority": "low|medium|high",
	"due_date": "date|null",
	"parent_id": 1
}
```

Response `200` (TaskResource)

**DELETE `/api/tasks/{task}`** (requires `manage_tasks`)

Request body: none

Response `204`: no content

**POST `/api/tasks/{id}/restore`** (requires `manage_tasks`)

Request body: none

Response `200` (TaskResource)

**PATCH `/api/tasks/{task}/status`**

Request JSON:
```json
{ "status": "todo|in_progress|done|blocked" }
```

Response `200` (TaskResource)

Response `403` when not permitted:
```json
{ "message": "Forbidden" }
```

**POST `/api/tasks/bulk-status`**

Request JSON:
```json
{
	"updates": [
		{ "id": 1, "status": "todo|in_progress|done|blocked" }
	]
}
```

Response `200`:
```json
{ "message": "Statuses updated" }
```

---

### Comments (protected)

**POST `/api/comments`**

Request JSON:
```json
{
	"on": "project|task",
	"id": 1,
	"body": "string"
}
```

Response `201` (CommentResource):
```json
{
	"id": 1,
	"body": "string",
	"user": { "id": 1, "name": "string", "email": "string" },
	"commentable": { "type": "string", "id": 1 },
	"created_at": "datetime",
	"updated_at": "datetime",
	"deleted_at": null
}
```

Response `403` when not a project member:
```json
{ "message": "Forbidden" }
```

Response `422` when `on` is invalid:
```json
{ "message": "Invalid comment target" }
```

**PUT `/api/comments/{comment}`**

Request JSON:
```json
{ "body": "string" }
```

Response `200` (CommentResource)

**DELETE `/api/comments/{comment}`**

Request body: none

Response `204`: no content

---

### Tags (protected)

**GET `/api/tags`**

Query params (optional):
```json
{ "q": "string" }
```

Response `200`:
```json
{
	"data": {
		"data": [
			{
				"id": 1,
				"name": "string",
				"slug": "string",
				"description": "string|null",
				"created_at": "datetime",
				"updated_at": "datetime"
			}
		]
	},
	"meta": { "current_page": 1, "last_page": 1, "total": 1 }
}
```

**POST `/api/tags`** (requires `manage_tags`)

Request JSON:
```json
{
	"name": "string",
	"slug": "string|null",
	"description": "string|null"
}
```

Response `201` (TagResource)

**GET `/api/tags/{tag}`**

Request body: none

Response `200` (TagResource)

**PUT `/api/tags/{tag}`** (requires `manage_tags`)

Request JSON (all optional):
```json
{
	"name": "string",
	"slug": "string|null",
	"description": "string|null"
}
```

Response `200` (TagResource)

**DELETE `/api/tags/{tag}`** (requires `manage_tags`)

Request body: none

Response `204`: no content

**POST `/api/tasks/{task}/tags/{tag}`** (requires `manage_tasks` or `manage_tags`)

Request body: none

Response `200`:
```json
{ "message": "Tag assigned" }
```

**DELETE `/api/tasks/{task}/tags/{tag}`** (requires `manage_tasks` or `manage_tags`)

Request body: none

Response `200`:
```json
{ "message": "Tag unassigned" }
```

---

### Attachments (protected)

**POST `/api/attachments`**

Request (multipart/form-data fields):
```json
{
	"on": "project|task",
	"id": 1,
	"file": "(binary file)"
}
```

Response `201` (AttachmentResource):
```json
{
	"id": 1,
	"original_name": "string",
	"mime_type": "string",
	"size": 12345,
	"path": "string",
	"user": { "id": 1, "name": "string", "email": "string" },
	"attachable": { "type": "string", "id": 1 },
	"created_at": "datetime",
	"updated_at": "datetime",
	"deleted_at": null
}
```

Response `403` when not a project member:
```json
{ "message": "Forbidden" }
```

Response `422` when `on` is invalid:
```json
{ "message": "Invalid attachment target" }
```

**DELETE `/api/attachments/{attachment}`**

Request body: none

Response `204`: no content

## Roles & Permissions
- Roles: `admin`, `project_manager`, `member`
- Permissions:
	- `manage_projects` (project mutation & membership)
	- `manage_tasks` (task mutation & status bulk)
	- `manage_comments` (moderate comments)
	- `manage_tags` (tag CRUD & assignment)
	- `view_reports` (reserved)
- Seeded via `RolePermissionSeeder` and `SampleDataSeeder`.

## Resources & Filters
- Projects `GET /api/projects`
	- Filters: `name`, `status`, `owner_id`, `starts_at_from`, `starts_at_to`, `ends_at_from`, `ends_at_to`, `with_trashed`
	- Cache: Unfiltered index cached per-user for 60s; bust on writes
- Tasks `GET /api/tasks`
	- Filters: `status`, `priority`, `assignee_id`, `project_id`, `due_date_from`, `due_date_to`, `tag`, `with_trashed`
- Comments (polymorphic): `POST /api/comments`, `PUT /api/comments/{id}`, `DELETE /api/comments/{id}`
- Tags: CRUD `GET/POST/PUT/DELETE /api/tags`; Assign `POST /api/tasks/{task}/tags/{tag}`; Unassign `DELETE /api/tasks/{task}/tags/{tag}`
- Attachments (polymorphic): `POST /api/attachments`, `DELETE /api/attachments/{id}` (stored under private disk)

## Authorization
- All protected routes under `auth:sanctum`.
- Mutations guarded via Spatie `permission` middleware.
- Comments: create/update by project members; delete by owner/manager/admin or `manage_comments`.
- Attachments: upload by project members; delete by owner/manager/admin or `manage_projects`.

## Validation
- FormRequests per domain (`Auth`, `Project`, `Task`, `Comment`, `Attachment`).
- Clear messages example: attachment type/size errors.

## Queues & Caching
- Queue job: `SendTaskAssignedNotification` dispatched on assignment/reassignment.
- Cache: project index cached per-user; invalidated on writes.

## Testing
```powershell
php artisan test
```
- Feature tests:
	- `tests/Feature/AuthTest.php`: register/login/logout/me + failures
	- `tests/Feature/ProjectTest.php`: CRUD + perms, filters, membership, restore
	- `tests/Feature/TaskTest.php`: filters, status updates, bulk, restore
	- `tests/Feature/CommentTest.php`: create/update/delete membership & role rules

## Postman Collection
- See `docs/postman_collection.json` for request examples (auth, projects, tasks, comments, tags, attachments) including failure scenarios.
- Notes include required headers (`Authorization: Bearer <token>`), and queue/cache behavior.

## Design Notes
- Polymorphic relations for `comments` and `attachments` keep schema DRY.
- Sanctum chosen for lightweight token auth.
- Spatie Permission for robust RBAC without reinventing middleware.

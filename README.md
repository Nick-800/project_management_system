<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Project Management System API

A Laravel 12+ JSON REST API for a Project Management System with Sanctum authentication, role/permission RBAC, polymorphic comments & attachments, filtering, caching, and queues.

## Setup

```powershell
# Install deps
composer install

# Environment & key
copy .env.example .env
php artisan key:generate

# Migrate & seed
php artisan migrate --force
php artisan db:seed --force

# Run server (and optional queue)
php artisan serve
php artisan queue:listen --tries=1
```

## Tech Stack
- Laravel 12, PHP 8.2
- Sanctum for token auth
- Spatie Laravel Permission for roles & permissions
- Pest/PhpUnit for tests

## Authentication
- Endpoints: `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/auth/me`
- Use bearer tokens from `login` / `register` response.

## API JSON Structures

### Headers
- Protected endpoints require: `Authorization: Bearer <token>`
- `Content-Type: application/json` for JSON bodies (except attachments upload)

### Common error responses

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

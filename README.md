# Course Management API

A REST API for managing courses, built with Laravel and Laravel Sanctum for
token-based authentication.

## Tech Stack

- PHP / Laravel 11
- Laravel Sanctum (API token authentication)
- MySQL (or any database supported by Laravel)

## Features

- User registration and login
- Token-based authentication (Sanctum)
- Course CRUD (create, read, update, delete)
- Consistent JSON response format for success and error cases

## Getting Started

### 1. Clone the project

```bash
git clone <your-repo-url>
cd course-management
```

### 2. Install dependencies

```bash
composer install
```

### 3. Set up environment

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with your database details.

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Start the server

```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api`.

## API Response Format

All responses follow this shape:

```json
{
    "success": true,
    "message": "Some message",
    "data": {}
}
```

Validation and error responses include an `errors` field:

```json
{
    "success": false,
    "message": "Validation Error",
    "data": null,
    "errors": {
        "field": ["Error message"]
    }
}
```

## Endpoints

### Auth

| Method | Endpoint         | Description             | Auth required |
|--------|------------------|--------------------------|----------------|
| POST   | `/api/register`  | Register a new user      | No             |
| POST   | `/api/login`     | Log in and get a token   | No             |
| POST   | `/api/logout`    | Revoke the current token | Yes            |

### Courses

| Method | Endpoint             | Description                  | Auth required |
|--------|-----------------------|-------------------------------|----------------|
| GET    | `/api/courses`        | List courses (paginated)      | Yes            |
| POST   | `/api/courses`        | Create a course               | Yes            |
| GET    | `/api/courses/{id}`   | Show a single course          | Yes            |
| PUT    | `/api/courses/{id}`   | Update a course (owner only)  | Yes            |
| DELETE | `/api/courses/{id}`   | Delete a course (owner only)  | Yes            |

## Authentication

After logging in, include the token in the `Authorization` header for
protected routes:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

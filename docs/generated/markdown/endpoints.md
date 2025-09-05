# API Endpoints Reference

This document provides detailed information about all available API endpoints.

## Auth Endpoints

### POST /auth/login

User authentication and JWT token generation

**Parameters:**

- `identifier` (string) - Username or email address
- `password` (string) - User password

**Example Request:**

```bash
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/login \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

### POST /auth/refresh

Refresh JWT token

**Example Request:**

```bash
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/refresh \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

### POST /auth/logout

Invalidate JWT token and logout

**Example Request:**

```bash
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/logout \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

## User Endpoints

### GET /users

Get paginated list of users with optional filtering

**Parameters:**

- `page` (integer) - Page number for pagination
- `limit` (integer) - Number of users per page
- `search` (string) - Search term for filtering
- `user_type` (string) - Filter by user type
- `status` (string) - Filter by user status

**Example Request:**

```bash
curl -X GET \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

### POST /users

Create a new user account

**Parameters:**

- `name` (string) - User first name
- `surname` (string) - User surname
- `email` (string) - User email address (required)
- `password` (string) - User password (required)
- `user_type` (string) - User role type

**Example Request:**

```bash
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

### GET /users/{id}

Get a specific user by ID

**Parameters:**

- `id` (integer) - User ID

**Example Request:**

```bash
curl -X GET \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/{id} \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

### PUT /users/{id}

Update an existing user

**Parameters:**

- `id` (integer) - User ID
- `name` (string) - User first name
- `surname` (string) - User surname
- `user_type` (string) - User role type
- `status` (string) - User account status

**Example Request:**

```bash
curl -X PUT \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/{id} \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

### DELETE /users/{id}

Delete a user account

**Parameters:**

- `id` (integer) - User ID

**Example Request:**

```bash
curl -X DELETE \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/{id} \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

### GET /users/{id}/profile

Get detailed user profile information

**Parameters:**

- `id` (integer) - User ID

**Example Request:**

```bash
curl -X GET \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/{id}/profile \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

### PUT /users/{id}/profile

Update user profile information

**Parameters:**

- `id` (integer) - User ID
- `phone` (string) - Phone number
- `address` (string) - Physical address
- `bio` (string) - User biography

**Example Request:**

```bash
curl -X PUT \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/{id}/profile \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---

### POST /users/{id}/change-password

Change user password

**Parameters:**

- `id` (integer) - User ID
- `current_password` (string) - Current password
- `new_password` (string) - New password

**Example Request:**

```bash
curl -X POST \
  http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/{id}/change-password \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json'
```

---


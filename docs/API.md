# API Documentation

**Base URL:** `https://dev.parser2.local/api`
**Version:** v1
**Format:** JSON
**Authentication:** Bearer token (Laravel Sanctum)

## Table of Contents

1. [General Information](#general-information)
2. [Authentication](#authentication)
3. [Error Handling](#error-handling)
4. [Rate Limiting](#rate-limiting)
5. [Endpoints](#endpoints)
   - [Campaigns](#campaigns)
   - [Sources](#sources)
   - [Results](#results)
   - [Parsers](#parsers)
   - [Normalization](#normalization)
   - [Categorization](#categorization)

---

## General Information

### Request Format

All API requests must include:

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### Response Format

#### Success Response

```json
{
  "success": true,
  "data": {},
  "message": "Operation completed successfully"
}
```

#### Paginated Response

```json
{
  "success": true,
  "data": [
    {}
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  },
  "links": {
    "first": "https://dev.parser2.local/api/resources?page=1",
    "last": "https://dev.parser2.local/api/resources?page=7",
    "prev": null,
    "next": "https://dev.parser2.local/api/resources?page=2"
  }
}
```

#### Error Response

```json
{
  "success": false,
  "message": "Human readable error message",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  },
  "code": "ERROR_CODE"
}
```

---

## Authentication

### Register

`POST /api/register`

Register a new user account.

**Request Body:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "securepassword123",
  "password_confirmation": "securepassword123"
}
```

**Response (201 Created):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": null,
      "created_at": "2025-11-08T10:00:00Z"
    },
    "token": "1|abc123..."
  },
  "message": "User registered successfully"
}
```

### Login

`POST /api/login`

Authenticate and receive access token.

**Request Body:**

```json
{
  "email": "john@example.com",
  "password": "securepassword123"
}
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "2|xyz789..."
  },
  "message": "Login successful"
}
```

### Logout

`POST /api/logout`

Revoke current access token.

**Headers:**

```
Authorization: Bearer {token}
```

**Response (200 OK):**

```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | Successful GET, PUT |
| 201 | Created | Successful POST creating resource |
| 204 | No Content | Successful DELETE |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing/invalid authentication |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Input validation failed |
| `UNAUTHENTICATED` | No valid authentication token |
| `UNAUTHORIZED` | User not authorized for resource |
| `NOT_FOUND` | Resource not found |
| `PARSING_FAILED` | Parser execution failed |
| `NORMALIZATION_FAILED` | Data normalization failed |
| `CATEGORIZATION_FAILED` | Categorization failed |

---

## Rate Limiting

**Default Limits:**
- Authenticated requests: 60 requests per minute
- Parsing execution: 10 requests per minute
- Guest requests: 10 requests per minute

**Headers:**

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1636455600
```

**Rate Limit Exceeded (429):**

```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "code": "RATE_LIMIT_EXCEEDED"
}
```

---

## Endpoints

### Campaigns

Parsing campaigns orchestrate parsing operations across multiple sources.

#### List Campaigns

`GET /api/campaigns`

List all campaigns for authenticated user.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| page | integer | Page number (default: 1) |
| per_page | integer | Results per page (default: 15, max: 100) |
| parser_type | string | Filter by parser type |
| status | string | Filter by status (active, inactive) |

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "News Aggregation Campaign",
      "parser_type": "feeds",
      "configuration": {
        "refresh_interval": 3600
      },
      "schedule": "0 * * * *",
      "is_active": true,
      "created_at": "2025-11-08T10:00:00Z",
      "updated_at": "2025-11-08T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 25
  }
}
```

#### Create Campaign

`POST /api/campaigns`

Create a new parsing campaign.

**Request Body:**

```json
{
  "name": "News Aggregation Campaign",
  "parser_type": "feeds",
  "configuration": {
    "refresh_interval": 3600,
    "max_items": 100
  },
  "schedule": "0 * * * *",
  "is_active": true
}
```

**Response (201 Created):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "News Aggregation Campaign",
    "parser_type": "feeds",
    "configuration": {
      "refresh_interval": 3600,
      "max_items": 100
    },
    "schedule": "0 * * * *",
    "is_active": true,
    "created_at": "2025-11-08T10:00:00Z",
    "updated_at": "2025-11-08T10:00:00Z"
  },
  "message": "Campaign created successfully"
}
```

#### Get Campaign

`GET /api/campaigns/{id}`

Get a single campaign by ID.

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "News Aggregation Campaign",
    "parser_type": "feeds",
    "configuration": {},
    "schedule": "0 * * * *",
    "is_active": true,
    "sources_count": 5,
    "results_count": 120,
    "created_at": "2025-11-08T10:00:00Z",
    "updated_at": "2025-11-08T10:00:00Z"
  }
}
```

#### Update Campaign

`PUT /api/campaigns/{id}`

Update an existing campaign.

**Request Body:**

```json
{
  "name": "Updated Campaign Name",
  "is_active": false
}
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Updated Campaign Name",
    "is_active": false,
    "updated_at": "2025-11-08T11:00:00Z"
  },
  "message": "Campaign updated successfully"
}
```

#### Delete Campaign

`DELETE /api/campaigns/{id}`

Delete a campaign and all associated data.

**Response (204 No Content)**

#### Execute Campaign

`POST /api/campaigns/{id}/execute`

Trigger immediate execution of a campaign.

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "campaign_id": 1,
    "execution_id": "exec_abc123",
    "status": "queued",
    "estimated_completion": "2025-11-08T10:05:00Z"
  },
  "message": "Campaign execution queued"
}
```

---

### Sources

Parsing sources define individual data sources for campaigns.

#### List Sources

`GET /api/sources`

List all sources for authenticated user.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| page | integer | Page number |
| campaign_id | integer | Filter by campaign |
| parser_type | string | Filter by parser type |
| is_active | boolean | Filter by active status |

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "campaign_id": 1,
      "name": "TechCrunch RSS",
      "parser_type": "feeds",
      "source_url": "https://techcrunch.com/feed/",
      "configuration": {
        "max_items": 50
      },
      "is_active": true,
      "last_parsed_at": "2025-11-08T09:00:00Z",
      "created_at": "2025-11-08T08:00:00Z"
    }
  ]
}
```

#### Create Source

`POST /api/sources`

Create a new parsing source.

**Request Body:**

```json
{
  "campaign_id": 1,
  "name": "TechCrunch RSS",
  "parser_type": "feeds",
  "source_url": "https://techcrunch.com/feed/",
  "configuration": {
    "max_items": 50
  },
  "is_active": true
}
```

**Response (201 Created):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "campaign_id": 1,
    "name": "TechCrunch RSS",
    "parser_type": "feeds",
    "source_url": "https://techcrunch.com/feed/",
    "configuration": {
      "max_items": 50
    },
    "is_active": true,
    "created_at": "2025-11-08T10:00:00Z"
  },
  "message": "Source created successfully"
}
```

---

### Results

Parsing results contain extracted and processed data.

#### List Results

`GET /api/results`

List parsing results with filtering.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| page | integer | Page number |
| campaign_id | integer | Filter by campaign |
| source_id | integer | Filter by source |
| date_from | date | Filter from date (YYYY-MM-DD) |
| date_to | date | Filter to date (YYYY-MM-DD) |
| category | string | Filter by category |
| search | string | Full-text search |

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "source_id": 1,
      "campaign_id": 1,
      "content": "Article content...",
      "parsed_data": {
        "title": "Article Title",
        "author": "John Doe",
        "published_at": "2025-11-08T09:00:00Z"
      },
      "normalized_data": {
        "title": "Article Title",
        "body": "Normalized content...",
        "metadata": {}
      },
      "categories": [
        {"id": 1, "name": "Technology"},
        {"id": 5, "name": "AI/ML"}
      ],
      "status": "completed",
      "created_at": "2025-11-08T10:00:00Z"
    }
  ]
}
```

#### Get Result

`GET /api/results/{id}`

Get a single result by ID.

#### Export Results

`GET /api/results/export`

Export results to various formats.

**Query Parameters:**

| Parameter | Type | Description | Required |
|-----------|------|-------------|----------|
| format | string | Export format (json, csv, xml) | Yes |
| campaign_id | integer | Filter by campaign | No |
| date_from | date | From date | No |
| date_to | date | To date | No |

**Response (200 OK):**

Headers:
```
Content-Type: application/json (or text/csv, application/xml)
Content-Disposition: attachment; filename="results-2025-11-08.json"
```

---

### Parsers

Information about available parsers.

#### List Parsers

`GET /api/parsers`

List all registered parsers and their capabilities.

**Response (200 OK):**

```json
{
  "success": true,
  "data": [
    {
      "type": "feeds",
      "name": "RSS/Atom Feed Parser",
      "description": "Parses RSS and Atom feeds",
      "configuration_schema": {
        "max_items": "integer",
        "refresh_interval": "integer"
      },
      "supported": true
    },
    {
      "type": "telegram",
      "name": "Telegram Channel Parser",
      "description": "Extracts messages from Telegram channels",
      "configuration_schema": {
        "channel_url": "string",
        "max_messages": "integer"
      },
      "supported": true
    }
  ]
}
```

---

### Normalization

Data normalization endpoints.

#### Normalize Data

`POST /api/normalize`

Normalize raw parsed data to standard schema.

**Request Body:**

```json
{
  "raw_data": {
    "title": "Article Title",
    "content": "Raw content...",
    "metadata": {}
  },
  "parser_type": "feeds"
}
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "title": "Article Title",
    "body": "Normalized content...",
    "author": "John Doe",
    "published_at": "2025-11-08T09:00:00Z",
    "metadata": {
      "source": "feeds",
      "language": "en"
    }
  },
  "message": "Data normalized successfully"
}
```

---

### Categorization

Content categorization endpoints.

#### Categorize Content

`POST /api/categorize`

Categorize normalized content.

**Request Body:**

```json
{
  "content": {
    "title": "AI Breakthrough in Medical Diagnosis",
    "body": "Researchers have developed..."
  },
  "taxonomy": "technology"
}
```

**Response (200 OK):**

```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Technology",
        "confidence": 0.95
      },
      {
        "id": 5,
        "name": "AI/ML",
        "confidence": 0.92
      },
      {
        "id": 8,
        "name": "Healthcare",
        "confidence": 0.78
      }
    ]
  },
  "message": "Content categorized successfully"
}
```

---

## Interactive API Documentation

For interactive API exploration with try-it-out functionality, visit:

**URL:** https://dev.parser2.local/api/documentation

This provides an OpenAPI/Swagger UI with:
- All endpoints documented
- Request/response examples
- Try-it-out functionality
- Authentication support
- Schema definitions

---

## Notes

- All timestamps are in UTC and ISO 8601 format
- Pagination defaults to 15 items per page, max 100
- Rate limits apply per user per minute
- Use Bearer token authentication for all protected endpoints
- See OpenAPI documentation for complete schema definitions

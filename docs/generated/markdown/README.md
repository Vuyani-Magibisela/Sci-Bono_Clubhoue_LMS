# Sci-Bono Clubhouse LMS API Documentation

## Overview

The Sci-Bono Clubhouse LMS API is a comprehensive RESTful API that provides complete functionality for managing the Learning Management System. This API enables developers to integrate with the LMS platform and build custom applications.

## API Information

- **Version**: 1.0.0
- **Base URL**: `http://localhost/Sci-Bono_Clubhoue_LMS/app/API`
- **Protocol**: HTTP/HTTPS
- **Response Format**: JSON
- **Authentication**: JWT (JSON Web Tokens)

## Features

### User Management
- User registration and authentication
- Profile management
- Role-based access control (Admin, Mentor, Member, Student)
- Password management and reset

### Course Management
- Course creation and management
- Lesson organization
- Progress tracking
- Enrollment management

### Holiday Program Management
- Program registration
- Workshop selection
- Capacity management
- Participant tracking

### Attendance System
- Real-time attendance tracking
- Check-in/check-out functionality
- Attendance reports
- Statistical analysis

### Administrative Features
- Comprehensive dashboard analytics
- User management
- System configuration
- Reporting and exports

## API Standards

### HTTP Methods
- `GET` - Retrieve data
- `POST` - Create new resources
- `PUT` - Update existing resources
- `DELETE` - Remove resources

### Response Codes
- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

### Data Format
All API endpoints accept and return JSON data with the following structure:

```json
{
  "success": true,
  "data": {},
  "message": "Operation completed successfully",
  "pagination": {},
  "errors": []
}
```

## Rate Limiting

API requests are limited based on user authentication:

| User Type | Requests per Minute |
|-----------|-------------------|
| Guest     | 20                |
| Student   | 100               |
| Member    | 200               |
| Mentor    | 500               |
| Admin     | 1000              |

Rate limit headers are included in responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests in current window
- `X-RateLimit-Reset`: Time when limit resets

## Getting Started

1. [Authentication Guide](authentication.md)
2. [Quick Start Tutorial](quickstart.md)
3. [API Endpoints Reference](endpoints.md)
4. [Error Handling Guide](errors.md)
5. [Code Examples](examples.md)

## Support

For technical support or questions about the API:
- Email: dev@sci-bono.co.za
- Documentation: [API Documentation](https://docs.sci-bono-lms.com)
- GitHub Issues: [Report Issues](https://github.com/sci-bono/lms-api/issues)

#!/bin/bash

# Sci-Bono LMS API - cURL Examples
# Complete set of API examples using cURL

# Configuration
BASE_URL="http://localhost/Sci-Bono_Clubhoue_LMS/app/API"
JWT_TOKEN=""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_header() {
    echo -e "${BLUE}=== $1 ===${NC}"
}

# Function to extract token from response
extract_token() {
    echo $1 | grep -o '"token":"[^"]*"' | cut -d'"' -f4
}

# 1. AUTHENTICATION EXAMPLES

print_header "Authentication Examples"

# Login
print_status "Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST \
  ${BASE_URL}/auth/login \
  -H 'Content-Type: application/json' \
  -d '{
    "identifier": "admin@sci-bono.co.za",
    "password": "admin123"
  }')

echo "Login Response:"
echo $LOGIN_RESPONSE | jq '.'

# Extract token for subsequent requests
JWT_TOKEN=$(extract_token "$LOGIN_RESPONSE")

if [ -z "$JWT_TOKEN" ]; then
    print_error "Failed to extract JWT token from login response"
    exit 1
fi

print_status "JWT Token extracted: ${JWT_TOKEN:0:50}..."

# Refresh token
print_status "Refreshing token..."
curl -s -X POST \
  ${BASE_URL}/auth/refresh \
  -H "Authorization: Bearer $JWT_TOKEN" | jq '.'

# 2. USER MANAGEMENT EXAMPLES

print_header "User Management Examples"

# Get all users with pagination
print_status "Getting all users (page 1, limit 5)..."
curl -s -X GET \
  "${BASE_URL}/users?page=1&limit=5" \
  -H "Authorization: Bearer $JWT_TOKEN" | jq '.'

# Search users
print_status "Searching users by name..."
curl -s -X GET \
  "${BASE_URL}/users?search=admin&user_type=admin" \
  -H "Authorization: Bearer $JWT_TOKEN" | jq '.'

# Get specific user
print_status "Getting user by ID (user 1)..."
curl -s -X GET \
  ${BASE_URL}/users/1 \
  -H "Authorization: Bearer $JWT_TOKEN" | jq '.'

# Create new user
print_status "Creating new user..."
CREATE_RESPONSE=$(curl -s -X POST \
  ${BASE_URL}/users \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Test",
    "surname": "User",
    "email": "testuser@example.com",
    "password": "TestPassword123!",
    "user_type": "student",
    "phone": "+27123456789"
  }')

echo "Create User Response:"
echo $CREATE_RESPONSE | jq '.'

# Extract user ID for subsequent operations
USER_ID=$(echo $CREATE_RESPONSE | grep -o '"id":[0-9]*' | cut -d':' -f2)

if [ -n "$USER_ID" ]; then
    print_status "Created user with ID: $USER_ID"
    
    # Update user
    print_status "Updating user $USER_ID..."
    curl -s -X PUT \
      ${BASE_URL}/users/$USER_ID \
      -H "Authorization: Bearer $JWT_TOKEN" \
      -H 'Content-Type: application/json' \
      -d '{
        "name": "Test Updated",
        "user_type": "member",
        "status": "active"
      }' | jq '.'
    
    # Get user profile
    print_status "Getting user profile..."
    curl -s -X GET \
      ${BASE_URL}/users/$USER_ID/profile \
      -H "Authorization: Bearer $JWT_TOKEN" | jq '.'
    
    # Update user profile
    print_status "Updating user profile..."
    curl -s -X PUT \
      ${BASE_URL}/users/$USER_ID/profile \
      -H "Authorization: Bearer $JWT_TOKEN" \
      -H 'Content-Type: application/json' \
      -d '{
        "phone": "+27987654321",
        "address": "123 Test Street, Johannesburg",
        "bio": "Test user created via API"
      }' | jq '.'
    
    # Change password
    print_status "Changing user password..."
    curl -s -X POST \
      ${BASE_URL}/users/$USER_ID/change-password \
      -H "Authorization: Bearer $JWT_TOKEN" \
      -H 'Content-Type: application/json' \
      -d '{
        "current_password": "TestPassword123!",
        "new_password": "NewPassword456!"
      }' | jq '.'
    
    # Delete user (cleanup)
    print_status "Deleting test user..."
    curl -s -X DELETE \
      ${BASE_URL}/users/$USER_ID \
      -H "Authorization: Bearer $JWT_TOKEN" | jq '.'
else
    print_warning "Could not extract user ID from create response"
fi

# 3. ERROR HANDLING EXAMPLES

print_header "Error Handling Examples"

# Test 401 - Unauthorized (invalid token)
print_status "Testing unauthorized access..."
curl -s -X GET \
  ${BASE_URL}/users \
  -H "Authorization: Bearer invalid-token" | jq '.'

# Test 403 - Forbidden (insufficient permissions)
print_status "Testing forbidden access (trying to access admin endpoint as student)..."
# This would require a student token, but demonstrates the concept
curl -s -X DELETE \
  ${BASE_URL}/users/1 \
  -H "Authorization: Bearer $JWT_TOKEN" | jq '.'

# Test 404 - Not found
print_status "Testing not found error..."
curl -s -X GET \
  ${BASE_URL}/users/99999 \
  -H "Authorization: Bearer $JWT_TOKEN" | jq '.'

# Test 422 - Validation error
print_status "Testing validation error..."
curl -s -X POST \
  ${BASE_URL}/users \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "",
    "email": "invalid-email",
    "password": "123"
  }' | jq '.'

# Test 429 - Rate limiting (would require many requests)
print_status "Rate limiting test (send many requests quickly)..."
for i in {1..5}; do
    curl -s -X GET \
      ${BASE_URL}/users \
      -H "Authorization: Bearer $JWT_TOKEN" \
      -w "Request $i - Status: %{http_code}\n" \
      -o /dev/null &
done
wait

# 4. ADVANCED EXAMPLES

print_header "Advanced Examples"

# Bulk operations simulation
print_status "Simulating bulk user creation..."
for i in {1..3}; do
    echo "Creating user $i..."
    curl -s -X POST \
      ${BASE_URL}/users \
      -H "Authorization: Bearer $JWT_TOKEN" \
      -H 'Content-Type: application/json' \
      -d "{
        \"name\": \"Bulk\",
        \"surname\": \"User$i\",
        \"email\": \"bulkuser$i@example.com\",
        \"password\": \"BulkPassword$i!\",
        \"user_type\": \"student\"
      }" | jq '.data.id' 2>/dev/null || echo "Failed to create user $i"
done

# Pagination example
print_status "Testing pagination..."
echo "Page 1:"
curl -s -X GET \
  "${BASE_URL}/users?page=1&limit=2" \
  -H "Authorization: Bearer $JWT_TOKEN" | jq '.pagination'

echo "Page 2:"
curl -s -X GET \
  "${BASE_URL}/users?page=2&limit=2" \
  -H "Authorization: Bearer $JWT_TOKEN" | jq '.pagination'

# Performance test
print_status "Performance test (10 concurrent requests)..."
start_time=$(date +%s)
for i in {1..10}; do
    curl -s -X GET \
      ${BASE_URL}/users \
      -H "Authorization: Bearer $JWT_TOKEN" \
      -o /dev/null &
done
wait
end_time=$(date +%s)
duration=$((end_time - start_time))
echo "10 concurrent requests completed in $duration seconds"

# Logout
print_status "Logging out..."
curl -s -X POST \
  ${BASE_URL}/auth/logout \
  -H "Authorization: Bearer $JWT_TOKEN" | jq '.'

print_header "Examples completed!"

echo ""
echo "Additional cURL Examples:"
echo ""

# Formatted examples for documentation
cat << 'EOF'
# Quick Reference - Common API Calls

# 1. Login
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"identifier": "user@example.com", "password": "password"}'

# 2. Get users with filters
curl -X GET 'http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users?page=1&limit=10&user_type=student&status=active' \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

# 3. Create user
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "John",
    "surname": "Doe", 
    "email": "john@example.com",
    "password": "SecurePass123!",
    "user_type": "student"
  }'

# 4. Update user
curl -X PUT http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123 \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"name": "John Updated", "status": "active"}'

# 5. Change password
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123/change-password \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "current_password": "old_password",
    "new_password": "new_password"
  }'

# 6. Delete user
curl -X DELETE http://localhost/Sci-Bono_Clubhoue_LMS/app/API/users/123 \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

# 7. Refresh token
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/refresh \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

# 8. Logout
curl -X POST http://localhost/Sci-Bono_Clubhoue_LMS/app/API/auth/logout \
  -H 'Authorization: Bearer YOUR_JWT_TOKEN'

EOF

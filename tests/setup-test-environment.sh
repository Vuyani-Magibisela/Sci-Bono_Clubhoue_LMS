#!/bin/bash

# Sci-Bono LMS Test Environment Setup Script
# Phase 7: API Development & Testing

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
TEST_DB_NAME="${TEST_DB_NAME:-sci_bono_lms_test}"
TEST_DB_HOST="${TEST_DB_HOST:-localhost}"
TEST_DB_USER="${TEST_DB_USER:-root}"
TEST_DB_PASS="${TEST_DB_PASS:-}"

echo -e "${BLUE}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    Sci-Bono LMS Test Environment Setup                       ║${NC}"
echo -e "${BLUE}║                       Phase 7: API Development & Testing                    ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Function to print status messages
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check PHP version
check_php_version() {
    print_status "Checking PHP version..."
    
    if ! command_exists php; then
        print_error "PHP is not installed or not in PATH"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_status "PHP version: $PHP_VERSION"
    
    # Check minimum PHP version (7.4)
    php -r "
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        echo 'ERROR: PHP 7.4 or higher is required. Current version: ' . PHP_VERSION . PHP_EOL;
        exit(1);
    }
    echo 'PHP version check passed.' . PHP_EOL;
    "
}

# Function to check PHP extensions
check_php_extensions() {
    print_status "Checking required PHP extensions..."
    
    REQUIRED_EXTENSIONS=("mysqli" "json" "mbstring" "openssl")
    OPTIONAL_EXTENSIONS=("xdebug" "curl")
    
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_status "✓ $ext extension is loaded"
        else
            print_error "✗ $ext extension is not loaded (required)"
            exit 1
        fi
    done
    
    for ext in "${OPTIONAL_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_status "✓ $ext extension is loaded"
        else
            print_warning "✗ $ext extension is not loaded (optional)"
        fi
    done
}

# Function to check MySQL/MariaDB
check_database() {
    print_status "Checking database connectivity..."
    
    if ! command_exists mysql; then
        print_error "MySQL client is not installed or not in PATH"
        exit 1
    fi
    
    # Test database connection
    if mysql -h "$TEST_DB_HOST" -u "$TEST_DB_USER" ${TEST_DB_PASS:+-p"$TEST_DB_PASS"} -e "SELECT 1;" >/dev/null 2>&1; then
        print_status "✓ Database connection successful"
    else
        print_error "✗ Cannot connect to database"
        echo "  Host: $TEST_DB_HOST"
        echo "  User: $TEST_DB_USER"
        echo "  Please check your database configuration"
        exit 1
    fi
}

# Function to create test database
create_test_database() {
    print_status "Creating test database: $TEST_DB_NAME"
    
    # Create database if it doesn't exist
    mysql -h "$TEST_DB_HOST" -u "$TEST_DB_USER" ${TEST_DB_PASS:+-p"$TEST_DB_PASS"} \
        -e "CREATE DATABASE IF NOT EXISTS \`$TEST_DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        print_status "✓ Test database created/verified"
    else
        print_error "✗ Failed to create test database"
        exit 1
    fi
}

# Function to create test directories
create_test_directories() {
    print_status "Creating test directories..."
    
    TEST_DIRS=(
        "$SCRIPT_DIR/reports"
        "$SCRIPT_DIR/coverage"
        "$SCRIPT_DIR/logs"
        "$SCRIPT_DIR/temp"
        "$SCRIPT_DIR/fixtures"
    )
    
    for dir in "${TEST_DIRS[@]}"; do
        if [ ! -d "$dir" ]; then
            mkdir -p "$dir"
            print_status "✓ Created directory: $(basename "$dir")"
        else
            print_status "✓ Directory exists: $(basename "$dir")"
        fi
    done
    
    # Set proper permissions
    chmod 755 "${TEST_DIRS[@]}" 2>/dev/null || true
}

# Function to install Composer dependencies
install_dependencies() {
    print_status "Checking Composer dependencies..."
    
    cd "$PROJECT_ROOT"
    
    if [ -f "composer.json" ]; then
        if command_exists composer; then
            print_status "Installing/updating Composer dependencies..."
            composer install --prefer-dist --no-interaction
        else
            print_warning "Composer is not installed. Skipping dependency installation."
        fi
    else
        print_warning "No composer.json found. Skipping dependency installation."
    fi
}

# Function to run test database setup
setup_test_database() {
    print_status "Setting up test database schema..."
    
    cd "$PROJECT_ROOT"
    
    # Look for database schema files
    if [ -d "Database" ]; then
        for sql_file in Database/*.sql; do
            if [ -f "$sql_file" ]; then
                print_status "Executing schema file: $(basename "$sql_file")"
                mysql -h "$TEST_DB_HOST" -u "$TEST_DB_USER" ${TEST_DB_PASS:+-p"$TEST_DB_PASS"} "$TEST_DB_NAME" < "$sql_file"
            fi
        done
    fi
    
    # Run any test-specific setup
    if [ -f "$SCRIPT_DIR/fixtures/test-schema.sql" ]; then
        print_status "Executing test schema..."
        mysql -h "$TEST_DB_HOST" -u "$TEST_DB_USER" ${TEST_DB_PASS:+-p"$TEST_DB_PASS"} "$TEST_DB_NAME" < "$SCRIPT_DIR/fixtures/test-schema.sql"
    fi
}

# Function to create environment file
create_env_file() {
    print_status "Creating test environment configuration..."
    
    ENV_FILE="$SCRIPT_DIR/.env"
    cat > "$ENV_FILE" << EOF
# Test Environment Configuration
# Generated by setup-test-environment.sh

TEST_DB_HOST=$TEST_DB_HOST
TEST_DB_USERNAME=$TEST_DB_USER
TEST_DB_PASSWORD=$TEST_DB_PASS
TEST_DB_NAME=$TEST_DB_NAME

APP_ENV=testing
APP_DEBUG=true

# Test-specific settings
TEST_TIMEOUT=30
TEST_MEMORY_LIMIT=256M
EOF
    
    print_status "✓ Environment file created: $ENV_FILE"
}

# Function to validate test setup
validate_setup() {
    print_status "Validating test setup..."
    
    # Check if test runner exists
    if [ -f "$SCRIPT_DIR/run-tests.php" ]; then
        print_status "✓ Test runner found"
    else
        print_error "✗ Test runner not found"
        exit 1
    fi
    
    # Check if BaseTestCase exists
    if [ -f "$SCRIPT_DIR/BaseTestCase.php" ]; then
        print_status "✓ Base test case found"
    else
        print_error "✗ Base test case not found"
        exit 1
    fi
    
    # Try to run a simple test check
    cd "$SCRIPT_DIR"
    if php -l run-tests.php >/dev/null 2>&1; then
        print_status "✓ Test runner syntax is valid"
    else
        print_error "✗ Test runner has syntax errors"
        exit 1
    fi
}

# Function to run a quick test
run_quick_test() {
    print_status "Running quick validation test..."
    
    cd "$SCRIPT_DIR"
    
    # Create a simple test to verify everything works
    cat > temp_test.php << 'EOF'
<?php
require_once 'BaseTestCase.php';

class QuickSetupTest extends Tests\BaseTestCase
{
    public function testDatabaseConnection()
    {
        $this->assertNotNull($this->db, 'Database connection should not be null');
        
        // Test a simple query
        $result = $this->db->query("SELECT 1 as test");
        $this->assertNotNull($result, 'Query should execute successfully');
        
        $row = $result->fetch_assoc();
        $this->assertEquals(1, $row['test'], 'Query should return expected result');
    }
    
    public function testTableCreation()
    {
        // Test creating a simple table
        $sql = "CREATE TEMPORARY TABLE test_setup_validation (id INT PRIMARY KEY, name VARCHAR(50))";
        $result = $this->db->query($sql);
        $this->assertTrue($result, 'Temporary table creation should succeed');
        
        // Test inserting data
        $sql = "INSERT INTO test_setup_validation (id, name) VALUES (1, 'test')";
        $result = $this->db->query($sql);
        $this->assertTrue($result, 'Data insertion should succeed');
    }
}
EOF

    # Run the quick test
    if php -c <(echo "error_reporting = E_ALL & ~E_DEPRECATED") run-tests.php --filter=QuickSetupTest --verbose; then
        print_status "✓ Quick validation test passed"
    else
        print_warning "Quick validation test failed - check configuration"
    fi
    
    # Clean up
    rm -f temp_test.php
}

# Function to show completion message
show_completion_message() {
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                          Setup Complete!                                    ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${BLUE}Test Environment Summary:${NC}"
    echo "  Database Host: $TEST_DB_HOST"
    echo "  Database Name: $TEST_DB_NAME"
    echo "  Test Directory: $SCRIPT_DIR"
    echo "  PHP Version: $(php -r 'echo PHP_VERSION;')"
    echo ""
    echo -e "${BLUE}Next Steps:${NC}"
    echo "  1. Run all tests: ${YELLOW}php tests/run-tests.php${NC}"
    echo "  2. Run specific suite: ${YELLOW}php tests/run-tests.php --suite=models${NC}"
    echo "  3. Run with coverage: ${YELLOW}php tests/run-tests.php --coverage${NC}"
    echo "  4. Get help: ${YELLOW}php tests/run-tests.php --help${NC}"
    echo ""
}

# Main execution
main() {
    print_status "Starting test environment setup..."
    
    check_php_version
    check_php_extensions
    check_database
    create_test_database
    create_test_directories
    install_dependencies
    setup_test_database
    create_env_file
    validate_setup
    
    if [ "${1:-}" != "--skip-quick-test" ]; then
        run_quick_test
    fi
    
    show_completion_message
}

# Handle command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --help|-h)
            echo "Sci-Bono LMS Test Environment Setup"
            echo ""
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --help, -h              Show this help message"
            echo "  --skip-quick-test       Skip the quick validation test"
            echo "  --db-host HOST          Set database host (default: localhost)"
            echo "  --db-user USER          Set database user (default: root)"
            echo "  --db-pass PASS          Set database password (default: empty)"
            echo "  --db-name NAME          Set test database name (default: sci_bono_lms_test)"
            echo ""
            echo "Environment Variables:"
            echo "  TEST_DB_HOST            Database host"
            echo "  TEST_DB_USER            Database user"
            echo "  TEST_DB_PASS            Database password"
            echo "  TEST_DB_NAME            Test database name"
            exit 0
            ;;
        --skip-quick-test)
            SKIP_QUICK_TEST=1
            shift
            ;;
        --db-host)
            TEST_DB_HOST="$2"
            shift 2
            ;;
        --db-user)
            TEST_DB_USER="$2"
            shift 2
            ;;
        --db-pass)
            TEST_DB_PASS="$2"
            shift 2
            ;;
        --db-name)
            TEST_DB_NAME="$2"
            shift 2
            ;;
        *)
            print_error "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Run main function
main "$@"
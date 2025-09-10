#!/bin/bash

# Laravel OpenTelemetry Package Setup Script
# This script automates the complete setup of OpenTelemetry in your Laravel project

set -e

echo "🚀 Laravel OpenTelemetry Package Setup"
echo "======================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    print_error "This script must be run from the root of a Laravel project"
    exit 1
fi

print_info "Detected Laravel project"

# Step 1: Install the package
echo ""
echo "📦 Step 1: Installing OpenTelemetry package..."
if composer require mumzworld/laravel-opentelemetry; then
    print_status "Package installed successfully"
else
    print_error "Failed to install package"
    exit 1
fi

# Step 2: Publish configuration files
echo ""
echo "📋 Step 2: Publishing configuration files..."
php artisan vendor:publish --provider="Mumzworld\LaravelOpenTelemetry\LaravelOpenTelemetryServiceProvider" --force
print_status "Configuration files published"

# Step 3: Setup environment variables
echo ""
echo "🔧 Step 3: Setting up environment variables..."
if [ ! -f ".env" ]; then
    print_warning ".env file not found, creating from .env.example"
    cp .env.example .env
fi

# Add OpenTelemetry configuration to .env if not exists
if ! grep -q "OTEL_ENABLED" .env; then
    echo "" >> .env
    echo "# OpenTelemetry Configuration" >> .env
    echo "OTEL_ENABLED=true" >> .env
    echo "OTEL_SERVICE_NAME=\${APP_NAME}" >> .env
    echo "OTEL_SERVICE_VERSION=1.0.0" >> .env
    echo "OTEL_ENVIRONMENT=\${APP_ENV}" >> .env
    echo "OTEL_TRACES_EXPORTER=otlp" >> .env
    echo "OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318" >> .env
    echo "OTEL_PROPAGATORS=baggage,tracecontext" >> .env
    echo "OTEL_PHP_AUTOLOAD_ENABLED=true" >> .env
    echo "OTEL_INSTRUMENTATION_ENABLED=true" >> .env
    print_status "OpenTelemetry environment variables added to .env"
else
    print_warning "OpenTelemetry environment variables already exist in .env"
fi

# Step 4: Update bootstrap/app.php
echo ""
echo "🔄 Step 4: Updating bootstrap/app.php..."
if ! grep -q "otel.php" bootstrap/app.php; then
    # Create backup
    cp bootstrap/app.php bootstrap/app.php.backup
    
    # Add OpenTelemetry initialization
    sed -i.tmp '1a\
\
// Initialize OpenTelemetry early for automatic tracing\
if (extension_loaded('\''opentelemetry'\'') && ($_ENV['\''OTEL_ENABLED'\''] ?? '\''true'\'') === '\''true'\'') {\
    require_once __DIR__ . '\''/otel.php'\'';\
}' bootstrap/app.php
    
    rm bootstrap/app.php.tmp
    print_status "Bootstrap file updated with OpenTelemetry initialization"
else
    print_warning "OpenTelemetry initialization already exists in bootstrap/app.php"
fi

# Step 5: Setup Docker Compose
echo ""
echo "🐳 Step 5: Setting up Docker Compose..."
if [ -f "docker-compose.yml" ]; then
    if ! grep -q "docker/opentelemetry/docker-compose.opentelemetry.yml" docker-compose.yml; then
        echo "" >> docker-compose.yml
        echo "# Include OpenTelemetry observability stack" >> docker-compose.yml
        echo "include:" >> docker-compose.yml
        echo "  - docker/opentelemetry/docker-compose.opentelemetry.yml" >> docker-compose.yml
        print_status "OpenTelemetry services added to docker-compose.yml"
    else
        print_warning "OpenTelemetry services already included in docker-compose.yml"
    fi
else
    print_warning "docker-compose.yml not found, skipping Docker setup"
fi

# Step 6: Add test routes (optional)
echo ""
read -p "🧪 Do you want to add test routes for OpenTelemetry? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if [ ! -f "routes/opentelemetry-test.php" ]; then
        php artisan vendor:publish --tag=opentelemetry-test-routes
        
        # Add route include to web.php or api.php
        if [ -f "routes/api.php" ]; then
            if ! grep -q "opentelemetry-test.php" routes/api.php; then
                echo "" >> routes/api.php
                echo "// OpenTelemetry test routes (remove in production)" >> routes/api.php
                echo "if (app()->environment(['local', 'testing'])) {" >> routes/api.php
                echo "    require __DIR__ . '/opentelemetry-test.php';" >> routes/api.php
                echo "}" >> routes/api.php
                print_status "Test routes added and included in routes/api.php"
            fi
        fi
    else
        print_warning "Test routes already exist"
    fi
fi

# Step 7: Check PHP OpenTelemetry extension
echo ""
echo "🔍 Step 7: Checking PHP OpenTelemetry extension..."
if php -m | grep -q opentelemetry; then
    print_status "OpenTelemetry PHP extension is installed"
else
    print_warning "OpenTelemetry PHP extension is not installed"
    echo ""
    echo "To install the OpenTelemetry PHP extension:"
    echo "1. Using PECL: pecl install opentelemetry"
    echo "2. Or download from: https://github.com/open-telemetry/opentelemetry-php-instrumentation/releases"
    echo "3. Add 'extension=opentelemetry' to your php.ini"
    echo "4. Restart your web server"
fi

# Step 8: Start services (if Docker is available)
echo ""
read -p "🚀 Do you want to start the OpenTelemetry observability stack now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if command -v docker-compose &> /dev/null; then
        echo "Starting OpenTelemetry services..."
        docker-compose up -d otel-collector tempo grafana
        print_status "OpenTelemetry services started"
        echo ""
        echo "🌐 Access URLs:"
        echo "   Grafana: http://your-host:3000 (admin/admin)"
        echo "   Tempo API: http://your-host:3200"
        echo "   Collector Health: http://your-host:13133"
        if [ -f "routes/opentelemetry-test.php" ]; then
            echo "   Test Routes: http://your-host/api/opentelemetry/test"
        fi
    else
        print_error "Docker Compose not found"
    fi
fi

echo ""
echo "🎉 Setup Complete!"
echo "=================="
echo ""
echo "Next steps:"
echo "1. Install OpenTelemetry PHP extension if not already installed"
echo "2. Configure your application service in docker-compose.yml with OpenTelemetry environment variables"
echo "3. Start your application and test the integration"
echo "4. View traces in Grafana at http://your-host:3000"
echo ""
echo "For detailed documentation, see the README.md file"
echo ""
print_status "Laravel OpenTelemetry setup completed successfully!"
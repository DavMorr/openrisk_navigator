#!/bin/bash

# OPENRISK NAVIGATOR - PRE-INSTALLATION DEPENDENCIES
# Install required contrib modules via Composer
# Version: 1.0

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}▶${NC} $1"
}

print_success() {
    echo -e "${GREEN}✅${NC} $1"
}

print_error() {
    echo -e "${RED}❌${NC} $1"
}

echo ""
echo "OPENRISK NAVIGATOR - PRE-INSTALLATION DEPENDENCIES"
echo "================================================="
echo ""
print_status "Installing required contrib modules via Composer..."
echo ""

# Detect environment
if [ -f "composer.json" ]; then
    COMPOSER_CMD="composer"
elif command -v ddev &> /dev/null && ddev describe &> /dev/null 2>&1; then
    COMPOSER_CMD="ddev composer"
else
    print_error "Cannot detect Composer environment. Please run from Drupal root or DDEV project."
    exit 1
fi

print_status "Using: $COMPOSER_CMD"
echo ""

# Remove manually copied contrib modules if they exist
if [ -d "web/modules/contrib" ] && [ "$(ls -A web/modules/contrib 2>/dev/null)" ]; then
    print_status "Removing any manually copied contrib modules..."
    rm -rf web/modules/contrib/*
    print_success "Cleaned contrib directory"
fi

# Install contrib modules in dependency order
print_status "Installing core dependencies..."
$COMPOSER_CMD require drupal/key
$COMPOSER_CMD require drupal/consumers
print_success "Core dependencies installed"

print_status "Installing AI modules..."
$COMPOSER_CMD require drupal/ai
$COMPOSER_CMD require drupal/ai_agents  
$COMPOSER_CMD require drupal/ai_provider_openai
print_success "AI modules installed"

print_status "Installing OAuth and API modules..."
$COMPOSER_CMD require drupal/simple_oauth
print_success "OAuth modules installed"

print_status "Installing additional modules..."
$COMPOSER_CMD require drupal/markdown_easy
$COMPOSER_CMD require drupal/migrate_tools
print_success "Additional modules installed"

print_status "Installing unstructured module (RC version)..."
$COMPOSER_CMD require 'drupal/unstructured:^2.0@RC'
print_success "Unstructured module installed"

print_status "Running composer install to ensure all dependencies..."
$COMPOSER_CMD install
print_success "All PHP dependencies installed"

echo ""
print_success "PRE-INSTALLATION DEPENDENCIES COMPLETE!"
echo "========================================="
print_status "All required contrib modules and PHP libraries have been installed."
print_status "You can now enable the OpenRisk Navigator module:"
print_status "  drush en openrisk_navigator -y"
print_status ""
print_status "For optional AI and API configuration, run:"
print_status "  ./post-install-setup.sh"
echo ""

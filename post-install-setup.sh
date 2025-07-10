#!/bin/bash

# OPENRISK NAVIGATOR - POST-INSTALLATION SETUP
# Optional configuration for AI integration and API access
# Version: 1.0

set -e

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}▶${NC} $1"
}

print_success() {
    echo -e "${GREEN}✅${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠️${NC} $1"
}

print_error() {
    echo -e "${RED}❌${NC} $1"
}

print_info() {
    echo -e "${CYAN}ℹ️${NC} $1"
}

echo ""
echo "OPENRISK NAVIGATOR - POST-INSTALLATION SETUP"
echo "=============================================="
echo ""
print_info "This script provides optional configuration for:"
print_info "• AI integration with OpenAI for automated risk analysis"  
print_info "• OAuth2 API access for external applications"
print_info "• Basic user and permission setup"
echo ""
print_info "All configuration is optional. If skipped, you can configure manually"
print_info "using the Drupal admin interface. See README.md for manual instructions."
echo ""

# Detect Drupal environment (DDEV vs other)
if command -v ddev &> /dev/null && ddev describe &> /dev/null 2>&1; then
    print_status "DDEV environment detected"
    DRUSH_CMD="ddev drush"
    ENVIRONMENT="ddev"
else
    print_status "Standard Drupal environment detected"
    DRUSH_CMD="drush"
    ENVIRONMENT="standard"
    
    # Check if drush is available
    if ! command -v drush &> /dev/null; then
        print_error "Drush not found. Please ensure drush is in your PATH or use DDEV."
        exit 1
    fi
fi

# Check if we can access Drupal
print_status "Verifying Drupal bootstrap..."
if ! $DRUSH_CMD status --field=bootstrap 2>/dev/null | grep -q "Successful"; then
    print_error "Cannot bootstrap Drupal. Please check your installation."
    exit 1
fi
print_success "Drupal bootstrap successful"

# Check if OpenRisk Navigator module is enabled
print_status "Verifying OpenRisk Navigator module..."
if ! $DRUSH_CMD pm:list --status=enabled --format=string | grep -q "openrisk_navigator"; then
    print_error "OpenRisk Navigator module is not enabled. Please enable it first:"
    print_error "  $DRUSH_CMD en openrisk_navigator -y"
    exit 1
fi
print_success "OpenRisk Navigator module is enabled"

echo ""

# Helper functions to check existing configuration
check_api_user_exists() {
    if $DRUSH_CMD user:information api_user >/dev/null 2>&1; then
        return 0  # User exists
    else
        return 1  # User doesn't exist
    fi
}

check_openai_key_exists() {
    if $DRUSH_CMD config:get key.key.openai_api_key >/dev/null 2>&1; then
        return 0  # Key exists
    else
        return 1  # Key doesn't exist
    fi
}

check_oauth2_consumer_exists() {
    local result=$($DRUSH_CMD php:eval '
        try {
            $query = \Drupal::entityQuery("consumer");
            $query->condition("client_id", "openrisk_navigator");
            $query->accessCheck(FALSE);
            print $query->count()->execute();
        } catch (Exception $e) {
            print "0";
        }
    ' 2>/dev/null)
    
    if [ "$result" = "1" ]; then
        return 0  # Consumer exists
    else
        return 1  # Consumer doesn't exist
    fi
}

check_ai_provider_configured() {
    if $DRUSH_CMD config-get ai_provider_openai.settings >/dev/null 2>&1; then
        return 0  # Config exists
    else
        return 1  # Config doesn't exist
    fi
}

# SECTION 1: API User Setup
print_status "SECTION 1: API User Setup"
print_info "An API user is needed for OAuth2 authentication and external API access."
echo ""

if check_api_user_exists; then
    print_success "API user 'api_user' already exists"
    SETUP_API_USER="exists"
else
    echo -n -e "${BLUE}▶${NC} Create API user for OAuth2 authentication? (y/n): "
    read -r API_USER_RESPONSE
    
    if [[ $API_USER_RESPONSE =~ ^[Yy]$ ]]; then
        print_status "Creating API user 'api_user'..."
        
        # Generate site-appropriate email
        SITE_URL=$($DRUSH_CMD config:get system.site uuid 2>/dev/null | cut -d: -f2 | tr -d ' ' || echo "drupal-site")
        
        $DRUSH_CMD user:create api_user \
            --mail="api_user@${SITE_URL}.local" \
            --password="api_password_$(date +%s)"
        
        print_success "API user created"
        SETUP_API_USER="created"
    else
        print_info "Skipping API user creation"
        SETUP_API_USER="skipped"
    fi
fi

echo ""

# SECTION 2: AI Integration Setup
print_status "SECTION 2: AI Integration Setup"
print_info "OpenRisk Navigator can use OpenAI for automated loan risk analysis."
print_info "This requires an OpenAI API key and internet connectivity."
echo ""

# Check if OpenAI is already configured
if check_openai_key_exists && check_ai_provider_configured; then
    print_success "OpenAI integration already configured"
    SETUP_AI="configured"
else
    echo -n -e "${BLUE}▶${NC} Configure OpenAI for AI-powered risk analysis? (y/n): "
    read -r AI_RESPONSE
    
    if [[ $AI_RESPONSE =~ ^[Yy]$ ]]; then
        SETUP_AI="yes"
        
        # Prompt for OpenAI API key
        echo ""
        print_info "You'll need an OpenAI API key from: https://platform.openai.com/api-keys"
        print_info "The key should start with 'sk-' and have read access to GPT models."
        echo ""
        echo -n -e "${BLUE}▶${NC} Enter your OpenAI API key: "
        read -r OPENAI_API_KEY
        
        if [ -z "$OPENAI_API_KEY" ]; then
            print_warning "No API key provided - skipping AI configuration"
            SETUP_AI="no"
        else
            # Validate API key format (basic check)
            if [[ ! "$OPENAI_API_KEY" =~ ^sk-[A-Za-z0-9] ]]; then
                print_warning "API key format looks incorrect (should start with 'sk-')"
                print_warning "Continuing anyway - you can reconfigure later if needed"
            fi
        fi
    else
        SETUP_AI="no"
        print_info "Skipping AI configuration"
        print_info "You can configure this later via: /admin/config/ai/providers"
    fi
fi

# Configure OpenAI if requested and not already configured
if [ "$SETUP_AI" = "yes" ]; then
    print_status "Configuring OpenAI integration..."
    
    # Create/update OpenAI key
    if check_openai_key_exists; then
        print_status "Updating existing OpenAI key..."
        $DRUSH_CMD php:eval "
            \$key = \Drupal::entityTypeManager()->getStorage('key')->load('openai_api_key');
            if (\$key) {
                \$key->delete();
            }
            \$new_key = \Drupal\key\Entity\Key::create([
                'id' => 'openai_api_key',
                'label' => 'OpenAI API Key for Risk Assessment',
                'description' => 'OpenAI API Key for automated loan risk analysis',
                'key_type' => 'authentication',
                'key_provider' => 'config',
                'key_input' => 'text_field',
                'key_provider_settings' => [
                    'key_value' => '$OPENAI_API_KEY'
                ]
            ]);
            \$new_key->save();
        "
    else
        print_status "Creating OpenAI key configuration..."
        $DRUSH_CMD php:eval "
            \$key = \Drupal\key\Entity\Key::create([
                'id' => 'openai_api_key',
                'label' => 'OpenAI API Key for Risk Assessment',
                'description' => 'OpenAI API Key for automated loan risk analysis',
                'key_type' => 'authentication',
                'key_provider' => 'config',
                'key_input' => 'text_field',
                'key_provider_settings' => [
                    'key_value' => '$OPENAI_API_KEY'
                ]
            ]);
            \$key->save();
        "
    fi
    print_success "OpenAI key configured"
    
    # Configure AI provider
    print_status "Configuring OpenAI provider..."
    $DRUSH_CMD config:set ai_provider_openai.settings api_key openai_api_key -y
    print_success "AI provider configured"
    
    # Clear cache to ensure configuration is loaded
    $DRUSH_CMD cr
    print_success "Configuration cache cleared"
fi

echo ""

# SECTION 3: OAuth2 Consumer Setup
print_status "SECTION 3: OAuth2 Consumer Setup"
print_info "OAuth2 consumers allow external applications to access the API securely."
echo ""

if check_oauth2_consumer_exists; then
    print_success "OAuth2 consumer 'openrisk_navigator' already exists"
    SETUP_OAUTH2="exists"
else
    if [ "$SETUP_API_USER" = "skipped" ]; then
        print_warning "API user not configured - OAuth2 consumer requires an API user"
        print_info "Skipping OAuth2 consumer setup"
        SETUP_OAUTH2="skipped"
    else
        echo -n -e "${BLUE}▶${NC} Create OAuth2 consumer for API access? (y/n): "
        read -r OAUTH2_RESPONSE
        
        if [[ $OAUTH2_RESPONSE =~ ^[Yy]$ ]]; then
            print_status "Creating OAuth2 consumer 'openrisk_navigator'..."
            
            # Generate a secure consumer secret
            CONSUMER_SECRET="openrisk_secret_$(openssl rand -hex 16 2>/dev/null || echo "openrisk_secret_$(date +%s)")"
            
            $DRUSH_CMD php:eval "
                \$query = \Drupal::entityQuery('user');
                \$query->condition('name', 'api_user');
                \$query->accessCheck(FALSE);
                \$uids = \$query->execute();
                \$user_id = !empty(\$uids) ? reset(\$uids) : NULL;
                
                \$consumer = \Drupal\consumers\Entity\Consumer::create([
                    'client_id' => 'openrisk_navigator',
                    'label' => 'OpenRisk Navigator API Client',
                    'secret' => '$CONSUMER_SECRET',
                    'grant_types' => ['client_credentials'],
                    'scopes' => ['loan_record:view'],
                    'user_id' => \$user_id,
                    'is_confidential' => TRUE,
                    'access_token_expiration' => 3600,
                ]);
                \$consumer->save();
                print 'OAuth2 consumer created successfully';
            "
            print_success "OAuth2 consumer created"
            SETUP_OAUTH2="created"
        else
            print_info "Skipping OAuth2 consumer setup"
            SETUP_OAUTH2="skipped"
        fi
    fi
fi

echo ""

# SECTION 4: Permissions and Final Setup
print_status "SECTION 4: Permissions and Final Setup"

# Grant necessary permissions to anonymous users for API access
print_status "Configuring API permissions..."
$DRUSH_CMD php:eval "
    \$anonymous_role = \Drupal\user\Entity\Role::load('anonymous');
    if (\$anonymous_role && !\$anonymous_role->hasPermission('view loan_record entities')) {
        \$anonymous_role->grantPermission('view loan_record entities');
        \$anonymous_role->save();
        print 'Anonymous API access configured';
    } else {
        print 'Anonymous API access already configured';
    }
"
print_success "API permissions configured"

# Clear all caches
print_status "Clearing all caches..."
$DRUSH_CMD cr
print_success "Cache cleared"

echo ""

# FINAL SUMMARY
print_success "POST-INSTALLATION SETUP COMPLETE!"
echo "============================================="
echo ""

# Get site URL for reference
SITE_URL=$($DRUSH_CMD config:get system.site base_url 2>/dev/null || $DRUSH_CMD php:eval 'print \Drupal::request()->getSchemeAndHttpHost();' 2>/dev/null || echo "your-site-url")

print_status "CONFIGURATION SUMMARY:"
echo ""

# API User Status
if [ "$SETUP_API_USER" = "exists" ] || [ "$SETUP_API_USER" = "created" ]; then
    print_success "✓ API User: api_user (configured)"
else
    print_warning "✗ API User: Not configured"
fi

# AI Integration Status
if [ "$SETUP_AI" = "yes" ] || [ "$SETUP_AI" = "configured" ]; then
    print_success "✓ AI Integration: OpenAI configured"
    print_info "  Test AI by creating a loan record - risk analysis will be generated automatically"
    print_info "  Admin: $SITE_URL/admin/config/ai/providers"
else
    print_warning "✗ AI Integration: Not configured"
    print_info "  Configure manually: $SITE_URL/admin/config/ai/providers"
    print_info "  Or run this script again and choose 'y' for AI setup"
fi

# OAuth2 Status
if [ "$SETUP_OAUTH2" = "exists" ] || [ "$SETUP_OAUTH2" = "created" ]; then
    print_success "✓ OAuth2 Consumer: openrisk_navigator (configured)"
    print_info "  Consumer management: $SITE_URL/admin/config/services/consumer"
else
    print_warning "✗ OAuth2 Consumer: Not configured"
    print_info "  Configure manually: $SITE_URL/admin/config/services/consumer"
fi

echo ""
print_status "USEFUL URLS:"
print_status "  Admin Dashboard: $SITE_URL/admin"
print_status "  Loan Records: $SITE_URL/admin/content/loan-records"
print_status "  Field Management: $SITE_URL/admin/structure/loan-record-settings"
print_status "  JSON API: $SITE_URL/jsonapi/loan_record/loan_record"
echo ""

print_status "NEXT STEPS:"
if [ "$SETUP_AI" = "yes" ] || [ "$SETUP_AI" = "configured" ]; then
    print_status "  1. Create a test loan record to verify AI integration"
    print_status "  2. Check the 'Risk Summary' field for AI-generated analysis"
else
    print_status "  1. Configure AI providers if desired (see URLs above)"
    print_status "  2. Create test loan records"
fi
print_status "  3. Review module documentation for advanced configuration"
echo ""

print_success "Setup complete! Happy analyzing!"

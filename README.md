# OpenRisk Navigator Module

A Drupal 11-based open-source platform for analyzing mortgage loan performance data, enriched by AI features including semantic search, summarization, and risk tagging—optimized for public sector, academic, and nonprofit use.

## Features

- **AI-Powered Risk Analysis**: Automated loan risk assessment using configurable AI providers
- **Zero-Configuration Installation**: Automatic setup of permissions, OAuth2, entity displays, and sample data
- **Professional Admin Interface**: Enhanced navigation with real-time statistics and comprehensive management tools
- **JSON:API Ready**: Full REST API support for headless integrations and external applications
- **Entity Management**: Complete CRUD operations for loan records with advanced field configurations
- **OAuth2 Security**: Secure API access with client credentials flow for machine-to-machine authentication

## Requirements

### System Requirements
- **Drupal**: 10.2+ or 11.x
- **PHP**: 8.1+
- **Database**: MySQL 8.0+ or PostgreSQL 13+

### Module Dependencies

All dependencies are automatically installed when using the provided installation scripts:

#### Core Dependencies
- **[AI Core](https://www.drupal.org/project/ai)** - Abstraction layer for AI services
- **[AI Agents](https://www.drupal.org/project/ai_agents)** - Makes Drupal taskable by AI agents
- **[AI Provider OpenAI](https://www.drupal.org/project/ai_provider_openai)** - OpenAI integration for AI module
- **[Key](https://www.drupal.org/project/key)** - Secure API key management
- **[Consumers](https://www.drupal.org/project/consumers)** - OAuth2 consumer management

#### Authentication & API
- **[Simple OAuth](https://www.drupal.org/project/simple_oauth)** - OAuth2 server implementation
- **[Simple OAuth Static Scope](https://www.drupal.org/project/simple_oauth)** - Static scope definitions

#### Additional Modules
- **[Markdown Easy](https://www.drupal.org/project/markdown_easy)** - Markdown text filtering
- **[Migrate Tools](https://www.drupal.org/project/migrate_tools)** - Migration development tools
- **[Unstructured](https://www.drupal.org/project/unstructured)** - Document processing (requires RC version)

#### Core Modules (Auto-enabled)
- **Field UI** - Field and display management
- **Migrate** - Data migration framework
- **JSON:API** - RESTful API endpoints
- **Serialization** - Data serialization support

### Special Requirements
- **Unstructured Module**: Requires RC version installation: `composer require 'drupal/unstructured:^2.0@RC'`
- **Internet Access**: Required for AI integration functionality
- **OpenAI API Key**: Optional but recommended for AI-powered risk analysis

## Installation

### Automated Installation (Recommended)

The module includes automated installation scripts to handle all dependencies and configuration:

#### 1. Install Dependencies
```bash
# Navigate to the module directory
cd web/modules/custom/openrisk_navigator/

# Make scripts executable (if needed)
chmod +x pre-install-dependencies.sh
chmod +x post-install-setup.sh

# Install all required contrib modules and PHP libraries
./pre-install-dependencies.sh
```

#### 2. Enable Module
```bash
drush en openrisk_navigator -y
```

#### 3. Optional Configuration (Recommended)
```bash
# Configure AI integration and API access
./post-install-setup.sh
```

⚠️ **Important**: The post-install script is optional but highly recommended. If skipped, you'll need to perform manual configuration (see Manual Configuration section below).

### Manual Installation

If you prefer to install dependencies manually:

```bash
# Install contrib modules via Composer
composer require drupal/ai
composer require drupal/ai_agents
composer require drupal/ai_provider_openai
composer require drupal/consumers
composer require drupal/key
composer require drupal/markdown_easy
composer require drupal/migrate_tools
composer require drupal/simple_oauth
composer require 'drupal/unstructured:^2.0@RC'

# Enable the module
drush en openrisk_navigator -y
```

⚠️ **Critical**: Never copy contrib modules manually - always use Composer to ensure PHP dependencies are properly installed.

## Manual Configuration

If you skip the post-install script, you'll need to configure the following components manually **in this specific order** (due to procedural interdependencies):

### 1. User Accounts
**Location**: `/admin/people`

Create an API user for OAuth2 authentication:
- **Username**: `api_user`
- **Email**: `api_user@your-domain.com`
- **Password**: Secure password for API access
- **Roles**: Authenticated user (default permissions sufficient)

### 2. Key Management
**Location**: `/admin/config/system/keys`

Create OpenAI API key storage:
- **Key ID**: `openai_api_key`
- **Label**: "OpenAI API Key for Risk Assessment"
- **Key Type**: Authentication
- **Key Provider**: Configuration
- **Key Value**: Your OpenAI API key (starts with `sk-`)

### 3. OAuth2 Scopes
**Location**: `/admin/config/people/simple_oauth/oauth2_scope/dynamic`

Configure dynamic scopes for API access:
- **Scope Name**: `loan_record:view`
- **Description**: "Access to view loan record entities"
- **Grant Types**: Client Credentials
- **Permissions**: `view loan_record entities`

### 4. OAuth2 Consumer
**Location**: `/admin/config/services/consumer`

Create API consumer:
- **Client ID**: `openrisk_navigator`
- **Label**: "OpenRisk Navigator API Client"
- **Secret**: Generate secure secret
- **Grant Types**: Client Credentials
- **Scopes**: `loan_record:view`
- **User**: Select the `api_user` created in step 1
- **Confidential**: Yes
- **Token Expiration**: 3600 seconds (1 hour)

### 5. AI Provider Configuration
**Location**: `/admin/config/ai/providers` → **OpenAI Authentication**: `/admin/config/ai/providers/openai`

Configure OpenAI provider:
- **API Key**: Select the key created in step 2 (`openai_api_key`)
- **Organization ID**: Optional (leave blank unless specified by OpenAI)
- **Default Model**: `gpt-4o` (recommended for risk analysis)

### 6. AI Settings
**Location**: `/admin/config/ai/settings`

Configure AI behavior:
- **Default Provider**: OpenAI
- **Enable Fallback**: Yes (recommended)
- **Rate Limiting**: Configure based on your OpenAI plan
- **Request Timeout**: 30 seconds (recommended)

## AI Provider Options

**OpenRisk Navigator is pre-configured for OpenAI integration** but supports alternative AI providers through the Drupal AI module ecosystem:

### Supported AI Providers
- **OpenAI** (Default) - GPT-4, GPT-3.5-turbo models
- **Anthropic Claude** - Install `ai_provider_anthropic` module
- **Google AI** - Install `ai_provider_google` module
- **Local/Self-Hosted** - Install `ai_provider_ollama` or similar modules

### Using Alternative Providers
1. Install the appropriate AI provider module via Composer
2. Enable the module: `drush en [provider_module] -y`
3. Configure the provider at `/admin/config/ai/providers`
4. Update OpenRisk Navigator settings to use the new provider

**Note**: Alternative providers may require different API keys, authentication methods, or model configurations.

## Usage

### Admin Interface

**Main Dashboard**: `/admin/openrisk/dashboard`
- View loan portfolio statistics and analytics
- Quick access to all module features
- Real-time performance metrics

**Loan Record Management**: `/admin/content/loan-records`
- Create, edit, and delete loan records
- AI risk summaries automatically generated on save
- Bulk operations and filtering

**Field Management**: `/admin/structure/loan-record-settings`
- Configure loan record fields and display
- Customize AI integration settings
- Entity form and view mode configuration

**Module Configuration**: `/admin/config/openrisk/settings`
- Global module settings
- AI integration preferences
- Risk assessment parameters

### API Access

**JSON:API Endpoint**: `/jsonapi/loan_record/loan_record`
- RESTful access to all loan records
- Full CRUD operations via HTTP
- OAuth2 authentication required for write operations

**Example API Usage**:
```bash
# Get OAuth2 token
curl -X POST /oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials&client_id=openrisk_navigator&client_secret=YOUR_SECRET"

# Access loan records
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://your-site.com/jsonapi/loan_record/loan_record
```

### AI-Powered Features

**Automatic Risk Analysis**: When creating or editing loan records, the AI system automatically:
- Analyzes borrower creditworthiness
- Evaluates loan-to-value ratios
- Assesses debt-to-income ratios
- Generates comprehensive risk summaries
- Provides risk mitigation recommendations

**Risk Summary Field**: AI-generated analysis appears in the `risk_summary` field, including:
- Risk level assessment (Low, Moderate, High)
- Key risk factors identified
- Recommendations for risk mitigation
- Confidence scores and analysis methodology

## Troubleshooting

### Common Issues

**Module Installation Fails**
- Ensure all dependencies are installed via Composer
- Check PHP version compatibility (8.1+ required)
- Verify database permissions

**AI Integration Not Working**
- Verify OpenAI API key is valid and has credits
- Check internet connectivity from server
- Review AI provider configuration
- Check Drupal logs at `/admin/reports/dblog`

**API Access Denied**
- Verify OAuth2 consumer configuration
- Check user permissions for `api_user`
- Ensure scopes are properly configured
- Test with fresh OAuth2 token

**Dependencies Missing**
- Never copy modules manually - use Composer only
- Run `composer install` to ensure PHP libraries
- Check for version conflicts with existing modules

### Script Permissions

If installation scripts fail to execute:
```bash
chmod +x pre-install-dependencies.sh
chmod +x post-install-setup.sh
```

### Reset Installation

To completely reset and reinstall:
```bash
# Uninstall module and clean data
drush pmu openrisk_navigator -y

# Re-run installation process
./pre-install-dependencies.sh
drush en openrisk_navigator -y
./post-install-setup.sh
```

## Uninstall

To completely remove the module and all its data:

```bash
drush pmu openrisk_navigator -y
```

This automatically removes:
- All loan records and entity data
- User permissions and OAuth2 configurations
- Entity definitions and database tables
- AI provider configurations (module-specific)

## Development

### Testing Environment

The module includes comprehensive development tools:
- Sample data generation via Drush commands
- API endpoint testing utilities
- AI integration validation tools

### Extending the Module

**Custom Risk Strategies**: Implement `LoanRiskStrategyInterface` to create custom risk assessment algorithms.

**Additional AI Providers**: Install compatible AI provider modules and configure through the AI settings interface.

**Custom Field Types**: Use Drupal's Field API to add custom loan data fields with automatic AI integration.

## Support & Contributing

- **Issue Tracker**: Report bugs and request features through the project's issue tracker
- **Documentation**: Additional documentation available in the `/docs` directory
- **Community**: Join discussions on Drupal.org project page

## Security

- All API access secured via OAuth2 authentication
- AI API keys stored using Drupal's secure Key module
- Input validation and sanitization on all user inputs
- Regular security updates following Drupal security practices

## License

GPL-2.0-or-later

---

**Version**: 1.0.0  
**Drupal**: 10.2+ | 11.x  
**Last Updated**: January 2025

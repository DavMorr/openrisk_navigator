# OpenRisk Navigator Module

A Drupal 11 module for AI-powered mortgage loan risk analysis and performance evaluation.

## Features

- **AI-Powered Risk Analysis**: Automated loan risk assessment using configurable AI providers
- **Zero-Configuration Installation**: Automatic setup of permissions, OAuth2, entity displays, and sample data
- **Professional Admin Interface**: Enhanced navigation with real-time statistics
- **JSON:API Ready**: Full REST API support for headless integrations
- **Entity Management**: Complete CRUD operations for loan records

## Requirements

- Drupal 10 or 11
- PHP 8.1+
- Required modules: `ai`, `ai_agents`, `field_ui`, `migrate`, `migrate_tools`
- Recommended: `simple_oauth` for API access, `jsonapi` for REST endpoints

## Installation

1. **Place module in custom modules directory**:
   ```
   web/modules/custom/openrisk_navigator/
   ```

2. **Enable the module**:
   ```bash
   drush en openrisk_navigator -y
   ```

That's it! The module includes zero-configuration installation that automatically sets up:
- ✅ Entity definitions and database tables
- ✅ User permissions for API access
- ✅ OAuth2 scopes for machine-to-machine access
- ✅ AI field formatters and display configurations
- ✅ Sample loan records for demonstration

## Usage

### Admin Interface

**Main Dashboard**: `/admin/openrisk/dashboard`
- View loan statistics and analytics
- Quick access to all module features

**Loan Record Management**: `/admin/content/loan-records`
- Create, edit, and delete loan records
- AI risk summaries automatically generated

**Module Settings**: `/admin/structure/loan-record-settings`
- Configure AI providers and models
- Adjust risk assessment parameters

### API Access

**JSON:API Endpoint**: `/jsonapi/loan_record/loan_record`
- RESTful access to all loan records
- Full CRUD operations via HTTP

### AI Configuration

Configure your AI provider through the Drupal AI module settings at `/admin/config/ai/providers`.

## Uninstall

To completely remove the module and all its data:

```bash
drush pmu openrisk_navigator -y
```

This will automatically:
- Remove all loan records
- Clean up permissions and OAuth2 scopes
- Remove entity definitions and database tables

## Support

For issues and feature requests, please use the project's issue tracker.

## License

GPL-2.0-or-later

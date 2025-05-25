# Changelog for Basic SEO

## Version 1.8.2 - December 4, 2024
**Tested up to**:
- WordPress: 6.7
- WooCommerce: 9.4.1

### Improvements
- Enhanced sitemap generation with better error handling
- Added post type and taxonomy validation in sitemap
- Improved 404 error responses for invalid sitemap requests

### Technical
- Added post_type_exists() and taxonomy_exists() validation
- Improved empty data handling in sitemap generation
- Added proper HTTP status codes for error responses

## Version 1.8.1 - November 17, 2024
**Tested up to**:
- WordPress: 6.7
- WooCommerce: 9.4.1
### Improvements
- Added Title Tag and Meta Description columns to WooCommerce categories list view
- Enhanced visibility of SEO fields in product categories management
### Technical
- Improved column display structure for product categories

## Version 1.8 - November 13, 2024

**Tested up to**:
- WordPress: 6.7
- WooCommerce: 9.4.1

### Security & Architecture
- Introduced unique prefix 'basicseotorvald_v1_' for all function names to prevent conflicts
- Added constants with unique prefixes for meta keys:
  - BSTV1_POST_TITLE
  - BSTV1_POST_DESC
  - BSTV1_TERM_TITLE
  - BSTV1_TERM_DESC

### Improvements
- Reorganized code structure with clear section headers:
  - Configuration Section
  - Posts & Pages SEO Fields
  - Admin Columns and Quick Edit
  - WooCommerce Category SEO Fields
  - Meta Tags Output
  - Sitemap Section
  - Breadcrumbs Section
  - Attachments Redirect Section
- Updated shortcode name to [basicseo-breadcrumb] for better branding consistency

### Bug Fixes
- Fixed meta data duplication issue in post/page editing
- Corrected column display in admin list view
- Resolved data saving and retrieval logic to prevent duplicate entries

### Technical
- Added proper nonce verification for form submissions
- Improved sanitization of input data
- Added support for debugging via WordPress error logs

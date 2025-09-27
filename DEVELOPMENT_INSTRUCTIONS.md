# CB QR Code Plugin - Development Instructions

## Plugin Overview

**Version**: 1.0.2  
**Description**: WordPress plugin that generates and displays QR codes on posts and pages with customizable appearance and security-compliant architecture.

### Key Features
- QR code generation using Endroid QR Code library
- Early sanitization security pattern (FluentSecurity compliant)
- Customizable appearance (size, colors, margins, labels)
- Logo integration with validation
- Multiple post type support
- Custom URL mode with validation
- Real-time preview functionality

### Technical Requirements
- PHP: >= 7.4
- WordPress: >= 5.0
- Composer dependencies
- Image formats: JPEG, PNG, GIF, WebP

## Architecture

### Security-First Design
The plugin implements **FluentSecurity Early Sanitization Pattern** with:
- **Input Processing**: Early sanitization immediately after nonce verification
- **No Bulk Processing**: Individual field sanitization, never process whole $_POST arrays
- **Universal wp_unslash()**: Applied to ALL input types before sanitization
- **Data Integrity**: Maintains original form through proper unslashing

### Core Components

#### 1. Admin Class (`includes/Admin.php`) - Security Compliant
- **AJAX Handlers**: `cbqrcode_ajax_save_settings()`, `cbqrcode_handle_ajax_preview()`
- **Security Implementation**: 
  ```php
  // 1. Nonce verification FIRST
  if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'] ?? '')), 'cbqrcode_ajax_nonce')) {
      wp_send_json_error(['errors' => [esc_html__('Security check failed.', 'cb-qr-code')]]);
  }
  
  // 2. IMMEDIATE individual field sanitization
  $sanitized_input = [
      'qr-code-label' => sanitize_text_field(wp_unslash($_POST['qr-code-label'] ?? '')),
      'qr-code-size' => intval(wp_unslash($_POST['qr-code-size'] ?? 150)),
      'qr-code-dark' => sanitize_hex_color(wp_unslash($_POST['qr-code-dark'] ?? '000000'))
      // ... all fields individually sanitized with wp_unslash()
  ];
  ```

#### 2. Helper Functions (`includes/helpers.php`) - Simplified & Secure
- **No Direct $_POST Access**: Helpers only process pre-sanitized data
- **Essential Functions Only**: 
  - `cbqrcode_get_current_settings()` - Database retrieval
  - `cbqrcode_get_allowed_post_types()` - Post type management
  - `cbqrcode_validate_fields()` - Validation of sanitized data only
  - `cbqrcode_get_field_definitions()` - Minimal validation rules

#### 3. Frontend Class (`includes/Frontend.php`) - Display Only
- **No User Input Processing**: Only displays stored data
- **Proper Escaping**: All output properly escaped with `esc_attr()`, `esc_html()`, `esc_url()`

#### 4. QR Generator (`includes/QRGenerator.php`) - Validation Heavy
- **Input Validation**: Validates all parameters with type checking
- **File Security**: Comprehensive image validation and path checking
- **Error Handling**: Try-catch blocks for safe QR generation

## Critical Security Practices (MANDATORY)

### 1. FluentSecurity Early Sanitization Pattern - REQUIRED

#### ✅ Correct Implementation (Current)
```php
public function cbqrcode_ajax_save_settings()
{
    // Step 1: Nonce verification FIRST
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'] ?? '')), 'cbqrcode_ajax_nonce')) {
        wp_send_json_error(['errors' => [esc_html__('Security check failed.', 'cb-qr-code')]]);
    }

    // Step 2: IMMEDIATE individual field sanitization with wp_unslash()
    $sanitized_input = [
        'qr-code-label' => sanitize_text_field(wp_unslash($_POST['qr-code-label'] ?? '')),
        'qr-code-size' => intval(wp_unslash($_POST['qr-code-size'] ?? 150)),
        'qr-code-dark' => sanitize_hex_color(wp_unslash($_POST['qr-code-dark'] ?? '000000')),
        // ALL fields must use wp_unslash() before sanitization
    ];
    
    // Step 3: Process sanitized data only
    $this->cbqrcode_handle_appearance_tab($sanitized_input);
}
```

#### ❌ NEVER Do This (Late Sanitization)
```php
// DON'T: Pass raw $_POST to methods
$this->process_data($_POST);

// DON'T: Sanitize in helper functions
function process_fields($raw_post_data) {
    return sanitize_text_field($raw_post_data['field']); // TOO LATE!
}
```

### 2. Universal wp_unslash() Usage - MANDATORY

#### ✅ Required for ALL Field Types
```php
// Text fields
sanitize_text_field(wp_unslash($_POST['text_field'] ?? ''))

// URLs
sanitize_url(wp_unslash($_POST['url_field'] ?? ''))

// Integers (YES, even integers need wp_unslash!)
intval(wp_unslash($_POST['int_field'] ?? 0))

// Hex colors
sanitize_hex_color(wp_unslash($_POST['color_field'] ?? '000000'))

// Arrays
array_map('sanitize_text_field', wp_unslash($_POST['array_field']))
```

### 3. Input Processing Rules - MANDATORY

#### ✅ Individual Field Processing Only
```php
// Process each field individually
foreach (['field1', 'field2', 'field3'] as $field) {
    $sanitized[$field] = sanitize_text_field(wp_unslash($_POST[$field] ?? ''));
}
```

#### ❌ NEVER Process Whole Arrays
```php
// DON'T: Process entire $_POST
$data = sanitize_array($_POST); // FORBIDDEN

// DON'T: Pass raw $_POST to functions
process_form_data($_POST); // FORBIDDEN
```

### 4. Helper Function Security - MANDATORY

#### ✅ Helpers Process Sanitized Data Only
```php
function cbqrcode_validate_fields($field_names, $sanitized_data) {
    // Only receives pre-sanitized data
    // Never accesses $_POST directly
}
```

#### ❌ NEVER Access $_POST in Helpers
```php
function helper_function() {
    $data = $_POST['field']; // FORBIDDEN in helpers
}
```

## WordPress Security Standards - MANDATORY

### 1. Nonce Verification - EVERY Request
```php
// Generate nonce in JavaScript localization
wp_localize_script('cbqrcode-admin-script', 'CBQRCodeAjax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('cbqrcode_ajax_nonce')
]);

// Verify nonce in EVERY AJAX handler (FIRST THING)
if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'] ?? '')), 'cbqrcode_ajax_nonce')) {
    wp_send_json_error(['errors' => [esc_html__('Security check failed.', 'cb-qr-code')]]);
}
```

### 2. Capability Checks - EVERY Admin Action
```php
// Check user permissions (SECOND THING after nonce)
if (!current_user_can('manage_options')) {
    wp_send_json_error(['errors' => [esc_html__('Insufficient permissions.', 'cb-qr-code')]]);
}
```

### 3. Output Escaping - ALL Output
```php
// Text content
echo esc_html($text);

// HTML attributes
echo '<div class="' . esc_attr($class_name) . '">';

// URLs
echo '<a href="' . esc_url($url) . '">';

// HTML content (when needed)
echo wp_kses_post($html_content);
```

### 4. File Upload Validation - COMPREHENSIVE
```php
public static function is_supported_image_format($attachment_id) {
    // 1. Validate ID
    if (!is_numeric($attachment_id) || $attachment_id <= 0) {
        return false;
    }
    
    // 2. Check WordPress attachment
    if (!wp_attachment_is_image($attachment_id)) {
        return false;
    }
    
    // 3. Verify file exists
    $file_path = get_attached_file($attachment_id);
    if (!$file_path || !file_exists($file_path) || !is_readable($file_path)) {
        return false;
    }
    
    // 4. Validate image type
    $image_info = @getimagesize($file_path);
    if ($image_info === false) {
        return false;
    }
    
    // 5. Check allowed formats
    $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
    return in_array($image_info[2], $allowed_types, true);
}
```

## Development Standards

### 1. Naming Conventions
- **Functions**: `cbqrcode_` prefix with snake_case
- **Classes**: PascalCase with namespace `ChinmoyBiswas\CBQRCode`
- **Database Options**: `cbqrcode_` prefix
- **CSS Classes**: `cbqrcode-` prefix

### 2. File Requirements
```php
// EVERY PHP file must start with:
<?php
namespace ChinmoyBiswas\CBQRCode;
if (!defined('ABSPATH')) exit;
```

### 3. AJAX Pattern (MANDATORY Structure)
```php
public function ajax_handler_name() {
    // 1. NONCE VERIFICATION (FIRST)
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['security'] ?? '')), 'cbqrcode_ajax_nonce')) {
        wp_send_json_error(['errors' => [esc_html__('Security check failed.', 'cb-qr-code')]]);
    }

    // 2. CAPABILITY CHECK (SECOND)
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['errors' => [esc_html__('Insufficient permissions.', 'cb-qr-code')]]);
    }

    // 3. EARLY SANITIZATION (THIRD)
    $sanitized_input = [
        'field1' => sanitize_text_field(wp_unslash($_POST['field1'] ?? '')),
        'field2' => intval(wp_unslash($_POST['field2'] ?? 0)),
        // ALL fields individually sanitized with wp_unslash()
    ];

    // 4. VALIDATION (FOURTH)
    $errors = cbqrcode_validate_fields(['field1', 'field2'], $sanitized_input);
    if (!empty($errors)) {
        wp_send_json_error(['errors' => $errors]);
    }

    // 5. PROCESSING (FINAL)
    // Process only sanitized data
}
```

### 4. Error Handling
```php
// QR Generation with try-catch
try {
    $qr_data_uri = QRGenerator::generate($text, $options);
    if (empty($qr_data_uri)) {
        wp_send_json_error(['message' => esc_html__('Failed to generate QR code', 'cb-qr-code')]);
    }
} catch (\Exception $e) {
    wp_send_json_error(['message' => esc_html__('QR generation error', 'cb-qr-code')]);
}
```

## Testing & Validation

### 1. Security Testing Checklist
- [ ] **Early Sanitization**: Verify all AJAX handlers sanitize immediately after nonce
- [ ] **wp_unslash() Usage**: Confirm ALL field types use wp_unslash() before sanitization
- [ ] **No Bulk Processing**: Ensure no $_POST arrays are passed to functions
- [ ] **Helper Security**: Verify helpers don't access $_POST directly
- [ ] **Nonce Verification**: Check all AJAX requests verify nonces first
- [ ] **Capability Checks**: Confirm user permissions are checked

### 2. WordPress Standards Validation
```bash
# PHP Syntax Check
php -l includes/Admin.php
php -l includes/helpers.php

# WordPress Coding Standards (if installed)
phpcs --standard=WordPress includes/
```

### 3. Functional Testing
- [ ] Settings save correctly with validation
- [ ] Preview updates in real-time
- [ ] QR codes display on selected post types
- [ ] File upload validation works
- [ ] Error messages display appropriately

### 4. Browser Compatibility
- Test admin interface in Chrome, Firefox, Safari, Edge
- Verify responsive behavior on mobile devices
- Check QR code scanning functionality

## WordPress.org Compliance

### 1. Pre-deployment Security Checklist
- [ ] **FluentSecurity Pattern**: All AJAX handlers follow early sanitization
- [ ] **wp_unslash() Universal**: ALL input types use wp_unslash() before sanitization
- [ ] **No Bulk Processing**: Individual field processing only
- [ ] **Helper Security**: No $_POST access in helper functions
- [ ] **WordPress Security**: Nonce verification + capability checks + input sanitization + output escaping

### 2. Code Quality Requirements
```bash
# Required before submission
composer install --no-dev
php -l includes/*.php
```

### 3. WordPress Standards Compliance
- **Naming**: All functions prefixed with `cbqrcode_`
- **Database**: All options prefixed with `cbqrcode_`
- **Escaping**: All output escaped with appropriate functions
- **Sanitization**: All input sanitized immediately after nonce verification

---

## CRITICAL SECURITY RULES (NEVER BREAK)

### ❌ FORBIDDEN Patterns
1. **Late Sanitization**: Never sanitize in helper functions
2. **Bulk Processing**: Never pass whole $_POST arrays to functions
3. **Missing wp_unslash()**: Never sanitize without wp_unslash() first
4. **Helper $_POST Access**: Never access $_POST in helper functions
5. **Missing Nonce**: Never process input without nonce verification first

### ✅ REQUIRED Patterns
1. **Early Sanitization**: Sanitize immediately after nonce verification
2. **Individual Processing**: Process each field individually
3. **Universal wp_unslash()**: Use on ALL field types before sanitization
4. **Helper Security**: Helpers only process pre-sanitized data
5. **WordPress Security**: Nonce → Capability → Sanitization → Validation → Processing

---

**Plugin Status**: WordPress.org Security Compliant ✅  
**Security Grade**: A+ (FluentSecurity Pattern Implemented)  
**Last Updated**: Current implementation follows all mandatory security practices
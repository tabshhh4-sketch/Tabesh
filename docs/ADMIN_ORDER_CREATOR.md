# Admin Order Creator - Implementation Documentation

## Overview

The Admin Order Creator feature allows administrators to create orders on behalf of customers without requiring the customer's presence or action. This is useful for phone orders, in-person orders, or situations where the administrator needs to manually enter order details.

## Architecture

### Independent Design
The feature is implemented as a completely independent, self-contained module that:
- Does not modify existing order creation flow
- Reuses existing tested methods for price calculation and order creation
- Integrates seamlessly with all existing shortcodes and features
- Maintains full compatibility with the notification system

### Components

#### 1. Handler Class
**File:** `includes/handlers/class-tabesh-admin-order-creator.php`

The main handler class that manages:
- Asset enqueueing (CSS and JavaScript)
- REST API endpoint callbacks
- User search functionality
- User creation with validation
- Order creation on behalf of customers

**Key Methods:**
- `rest_search_users_live()` - Live search for existing users
- `rest_create_user()` - Create new user with mobile number validation
- `rest_create_order()` - Create order for selected user
- `render_new_order_button()` - Render the modal trigger button
- `render_order_modal()` - Include the modal template

#### 2. Modal Template
**File:** `templates/admin/admin-order-creator-modal.php`

A comprehensive order form template that includes:
- User selection/creation interface
- All order parameters from the customer order form
- Price calculation display
- Price override option (for super admins)
- Responsive layout with RTL support

**Form Sections:**
1. User Management (selection or creation)
2. Order Details (all book printing parameters)
3. Price Display (calculated and final price)

#### 3. JavaScript
**File:** `assets/js/admin-order-creator.js`

Handles all client-side interactions:
- Modal open/close with animations
- Live user search with debouncing
- New user creation
- Dynamic form field updates
- Price calculation
- Form validation
- Order submission

**Key Features:**
- Debounced search (300ms delay)
- Automatic paper weight population based on paper type
- Dynamic page count fields based on print type
- Price override handling
- Form reset on modal close

#### 4. CSS
**File:** `assets/css/admin-order-creator.css`

Modern, responsive modal styling with:
- RTL support
- Responsive design (desktop, tablet, mobile)
- Smooth animations
- Accessibility considerations
- Isolation from theme/plugin conflicts

## REST API Endpoints

### 1. Search Users (Live)
```
GET /wp-json/tabesh/v1/admin/search-users-live?search={query}
```

**Permission:** `can_manage_admin`

**Parameters:**
- `search` (string, required) - Search query (min 2 characters)

**Response:**
```json
{
  "success": true,
  "users": [
    {
      "id": 123,
      "user_login": "09123456789",
      "display_name": "نام کاربر",
      "first_name": "نام",
      "last_name": "نام خانوادگی"
    }
  ]
}
```

### 2. Create User
```
POST /wp-json/tabesh/v1/admin/create-user
```

**Permission:** `can_manage_admin`

**Body:**
```json
{
  "mobile": "09123456789",
  "first_name": "نام",
  "last_name": "نام خانوادگی"
}
```

**Response:**
```json
{
  "success": true,
  "user_id": 124,
  "user": {
    "id": 124,
    "user_login": "09123456789",
    "display_name": "نام نام خانوادگی",
    "first_name": "نام",
    "last_name": "نام خانوادگی"
  },
  "message": "کاربر با موفقیت ایجاد شد"
}
```

**Validation:**
- Mobile format: `^09[0-9]{9}$` (Iranian mobile number)
- All fields required
- Checks for existing user with same mobile
- Auto-generates secure password

### 3. Create Order
```
POST /wp-json/tabesh/v1/admin/create-order
```

**Permission:** `can_manage_admin`

**Body:**
```json
{
  "user_id": 124,
  "book_title": "عنوان کتاب",
  "book_size": "A5",
  "paper_type": "تحریر",
  "paper_weight": "80",
  "print_type": "سیاه و سفید",
  "page_count_color": 0,
  "page_count_bw": 100,
  "quantity": 100,
  "binding_type": "شومیز",
  "license_type": "دارم",
  "cover_paper_weight": "250",
  "lamination_type": "براق",
  "extras": ["لب گرد", "شیرینک"],
  "notes": "یادداشت اختیاری",
  "override_price": 1500000
}
```

**Response:**
```json
{
  "success": true,
  "order_id": 456,
  "order_number": "TB-20231203-1234",
  "message": "سفارش با موفقیت ثبت شد"
}
```

**Processing:**
1. Validates user exists
2. Calculates price using `Tabesh_Order::calculate_price()`
3. Uses override price if provided
4. Creates order using `Tabesh_Order::create_order()`
5. Adds metadata: `_created_by_admin` with admin user ID
6. Appends admin note to order notes
7. Fires `tabesh_order_submitted` action hook
8. Logs action in `wp_tabesh_logs`

## Security

### Permission Checks
All endpoints require the `can_manage_admin` capability, which checks:
1. `manage_woocommerce` capability (WooCommerce administrators)
2. User in `admin_dashboard_allowed_users` list

### Nonce Verification
All REST requests include and verify WordPress REST nonce (`X-WP-Nonce` header).

### Input Sanitization
All inputs are sanitized using WordPress functions:
- `sanitize_text_field()` for text inputs
- `sanitize_textarea_field()` for textarea
- `intval()` for numeric values
- Array sanitization for extras

### Validation
- Mobile number format validation (Iranian format)
- Required field validation
- User existence checks
- Price calculation validation

### Output Escaping
All template outputs use appropriate escaping:
- `esc_html()` for text
- `esc_attr()` for attributes
- `esc_url()` for URLs

## Integration with Existing System

### Order Creation Flow
The feature reuses the existing `Tabesh_Order::create_order()` method, ensuring:
- Consistent order structure
- Same database schema
- Compatibility with all features

### Price Calculation
Uses `Tabesh_Order::calculate_price()` method, ensuring:
- Accurate pricing
- Same pricing rules as customer orders
- Support for all pricing configurations

### Notifications
Fires `tabesh_order_submitted` action hook, triggering:
- SMS notifications (if configured)
- Email notifications
- Any custom integrations

### Metadata
Stores admin order metadata:
- `_created_by_admin` post meta with admin user ID
- Admin note in order notes field
- Action log in `wp_tabesh_logs` table

### Visibility
Orders created by admin appear in:
- `[tabesh_admin_dashboard]` - Admin dashboard
- `[tabesh_user_orders]` - Customer orders panel
- `[tabesh_staff_panel]` - Staff management panel
- `[tabesh_upload_manager]` - File upload interface
- All order listing and management interfaces

## User Interface

### Button Placement
The "ثبت سفارش جدید" (New Order) button appears:
- At the top of the admin dashboard
- Next to the dashboard title
- Only for users with admin access

### Modal Design
- Modern, clean interface
- Organized into logical sections
- Clear visual hierarchy
- Responsive layout
- RTL support for Persian language

### User Experience
- Live search with instant results
- Auto-population of dependent fields
- Real-time price calculation
- Clear error messages
- Loading states during operations
- Success feedback
- Page reload to show new order

## Testing

### Manual Testing
See `TESTING.md` for comprehensive manual testing guide covering:
- Modal operations
- User selection/creation
- Order form functionality
- Price calculation
- Order submission
- Integration verification
- Security testing
- Responsive design

### Automated Testing
- PHP syntax validation: ✅ Pass
- JavaScript syntax validation: ✅ Pass
- CodeQL security scan: ✅ 0 vulnerabilities
- WordPress coding standards: Minor formatting issues (spaces vs tabs)

## Troubleshooting

### Modal Not Opening
- Check browser console for JavaScript errors
- Verify `tabeshAdminOrderCreator` object is defined
- Check if assets are loading (CSS and JS files)
- Verify user has admin access

### User Search Not Working
- Check REST API is accessible
- Verify nonce is being sent
- Check user has `can_manage_admin` permission
- Look for AJAX errors in browser console

### Order Not Creating
- Verify all required fields are filled
- Check calculated price is showing
- Verify user is selected
- Check browser console and PHP error logs
- Ensure database table `wp_tabesh_orders` exists

### Price Not Calculating
- Verify settings are configured (paper types, etc.)
- Check `tabeshAdminOrderCreator.settings.paperTypes` is populated
- Verify REST endpoint `/calculate-price` is accessible
- Check for JavaScript errors

## Future Enhancements

Possible improvements for future versions:
1. Bulk order creation
2. Order templates/presets
3. Customer contact information import
4. Order duplication feature
5. CSV import for bulk orders
6. Enhanced validation with field-specific messages
7. Order preview before submission
8. Draft order saving

## Maintenance

### Updating Settings
When plugin settings are updated, the localized JavaScript data is automatically updated on page load.

### Database Schema Changes
If order table schema changes, the `create_order()` method handles it with fallback to WordPress posts.

### WordPress Updates
The feature uses standard WordPress APIs and should remain compatible with WordPress updates.

### WooCommerce Updates
The feature checks `manage_woocommerce` capability, which is standard and should remain stable.

## Support

For issues, questions, or feature requests:
1. Check this documentation
2. Review the testing guide
3. Check browser console for errors
4. Check WordPress debug log
5. Review REST API responses
6. Contact plugin maintainers

## License

This feature is part of the Tabesh plugin and is licensed under GPL v2 or later.

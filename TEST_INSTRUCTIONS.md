# Test Instructions for Sub-status Tracking and Firewall Features

## Prerequisites
- WordPress installation with Tabesh plugin activated
- Admin and staff user accounts
- At least one test order

## Part 1: Sub-status Tracking Tests

### Test 1: Substep Change Logging
1. Create or find an order in "confirmed" or "processing" status
2. Open the order in the admin dashboard
3. Check/uncheck substep checkboxes (e.g., "چاپ جلد", "چاپ متن کتاب")
4. Verify that each change appears in "تاریخچه تغییرات مراحل چاپ" section
5. Check that the log shows:
   - Your username (staff/admin name)
   - Correct timestamp
   - Correct description (e.g., "مرحله 'چاپ جلد' تکمیل شد")

### Test 2: Staff Panel Substep Tracking
1. Login as a staff member (user with `edit_shop_orders` capability)
2. Go to staff panel (shortcode `[tabesh_staff_panel]`)
3. Search for a confirmed/processing order
4. Toggle substep checkboxes
5. Verify changes appear in history section with staff member's name

### Test 3: Admin Panel Substep Tracking
1. Login as admin
2. Go to admin dashboard (shortcode `[tabesh_admin_dashboard]`)
3. Open order details
4. Toggle substep checkboxes
5. Verify changes appear in history section with admin's name

## Part 2: Doomsday Firewall Tests

### Test 4: Firewall Configuration
1. Go to WordPress Admin → Tabesh Settings → Security
2. Enable Doomsday Firewall
3. Set a secret key (minimum 32 characters)
4. Save settings

### Test 5: Create WAR Order
1. Create a test order
2. In order notes, add the text: `@WAR#` (case insensitive)
3. Save the order
4. Verify the order appears in admin/staff panels (firewall not in lockdown)

### Test 6: Activate Lockdown Mode (Manual URL)
1. Get your secret key from Tabesh Settings
2. Visit URL: `https://yoursite.com/?tabesh_firewall_action=lockdown&key=YOUR_SECRET_KEY`
3. You should see "Firewall action completed successfully"

### Test 7: Verify Lockdown Hides Orders
1. After activating lockdown, refresh admin dashboard
2. Verify that WAR orders (with @WAR# tag) are NOT visible
3. Verify non-WAR orders are still visible
4. Check staff panel - WAR orders should also be hidden there

### Test 8: Deactivate Lockdown Mode
1. Visit URL: `https://yoursite.com/?tabesh_firewall_action=unlock&key=YOUR_SECRET_KEY`
2. You should see "Firewall action completed successfully"
3. Refresh admin dashboard
4. Verify WAR orders are now visible again

### Test 9: Firewall REST API
Test lockdown activation via REST API:
```bash
curl -X POST "https://yoursite.com/wp-json/tabesh/v1/firewall/lockdown/activate" \
  -H "X-Firewall-Secret: YOUR_SECRET_KEY"
```

Test lockdown deactivation via REST API:
```bash
curl -X POST "https://yoursite.com/wp-json/tabesh/v1/firewall/lockdown/deactivate" \
  -H "X-Firewall-Secret: YOUR_SECRET_KEY"
```

Check status:
```bash
curl "https://yoursite.com/wp-json/tabesh/v1/firewall/status?key=YOUR_SECRET_KEY"
```

### Test 10: Cron Job Simulation
For automated testing (e.g., via cron):
1. Set up cron to call lockdown at specific time:
   ```
   0 22 * * * curl "https://yoursite.com/?tabesh_firewall_action=lockdown&key=YOUR_SECRET_KEY"
   ```
2. Set up cron to unlock at specific time:
   ```
   0 8 * * * curl "https://yoursite.com/?tabesh_firewall_action=unlock&key=YOUR_SECRET_KEY"
   ```

## Expected Results

### Sub-status Tracking
✅ All substep changes should be logged with:
- Staff/admin username who made the change
- Precise timestamp (Y/m/d H:i format)
- Clear description of what changed
- History visible in both admin and staff panels

### Doomsday Firewall
✅ When lockdown is activated:
- Orders with @WAR# tag in notes should be hidden from admin dashboard
- Orders with @WAR# tag should be hidden from staff panel
- Non-WAR orders should remain visible
- Customers should never see WAR orders (always hidden)

✅ When lockdown is deactivated:
- All orders should be visible in admin/staff panels
- WAR orders should reappear

## Database Verification

### Verify Substep History Logs
```sql
SELECT * FROM wp_tabesh_logs 
WHERE action = 'substep_change' 
ORDER BY created_at DESC 
LIMIT 10;
```

### Verify Firewall Settings
```sql
SELECT * FROM wp_options 
WHERE option_name IN ('tabesh_firewall_enabled', 'tabesh_firewall_lockdown_mode', 'tabesh_firewall_secret_key');
```

## Troubleshooting

### Substep history not showing
- Check that `staff_user_id` column exists in `wp_tabesh_logs` table
- Verify `old_status` and `new_status` columns exist in `wp_tabesh_logs` table
- Check browser console for JavaScript errors

### Firewall not hiding orders
- Ensure firewall is enabled in settings
- Verify secret key is set and matches
- Check that @WAR# tag is in order notes
- Confirm lockdown mode is active: Check `wp_options` table for `tabesh_firewall_lockdown_mode = 1`

### CSS not applying
- Clear browser cache
- Check that CSS files are loaded (inspect in browser dev tools)
- Verify file timestamps match (cache busting)

# Testing Guide: New Order Button and Modal

## Overview
This guide helps you test the "New Order" button and modal functionality added to the admin dashboard.

## Prerequisites
- WordPress installation with Tabesh plugin active
- Admin user with `manage_woocommerce` capability
- Page or post with `[tabesh_admin_dashboard]` shortcode

## Test Scenarios

### 1. Button Visibility Test
**Steps:**
1. Log in as admin user
2. Navigate to page with `[tabesh_admin_dashboard]` shortcode
3. Look for "ثبت سفارش جدید" button in header actions area (top right)

**Expected Result:**
- Button is visible with purple gradient background
- Button has a "+" icon
- Button appears between profile section and settings/logout buttons

**Pass Criteria:**
- [ ] Button is visible
- [ ] Button has proper styling (gradient, icon, text)
- [ ] Button has hover effect (shadow, slight move up)

### 2. Modal Opening Test
**Steps:**
1. Click "ثبت سفارش جدید" button
2. Observe modal behavior

**Expected Result:**
- Modal appears with smooth fade-in animation
- Modal overlays the entire screen
- Modal has dark semi-transparent background
- Modal content is centered
- Body scroll is disabled

**Pass Criteria:**
- [ ] Modal opens smoothly
- [ ] Background overlay appears
- [ ] Content is properly centered
- [ ] Can't scroll page behind modal

### 3. Modal Closing Test
**Steps:**
1. Open the modal
2. Try all closing methods:
   - Click the X button in top-right
   - Click outside modal (on overlay)
   - Press ESC key
   - Click "انصراف" button

**Expected Result:**
- Modal closes with fade-out animation
- Body scroll is re-enabled
- Form is reset to initial state

**Pass Criteria:**
- [ ] X button closes modal
- [ ] Clicking overlay closes modal
- [ ] ESC key closes modal
- [ ] Cancel button closes modal
- [ ] Form resets after closing

### 4. User Selection Test (Existing User)
**Steps:**
1. Open modal
2. Ensure "انتخاب کاربر موجود" is selected
3. Type at least 2 characters in user search field
4. Wait for search results
5. Click a user from results

**Expected Result:**
- Live search shows results after 2+ characters
- Results appear in dropdown below search field
- Clicking a user selects them
- Selected user appears with name and remove button
- User ID is stored in hidden field

**Pass Criteria:**
- [ ] Search works with 2+ characters
- [ ] Results display correctly
- [ ] Can select a user
- [ ] Selected user displays properly
- [ ] Can remove selected user

### 5. User Creation Test
**Steps:**
1. Open modal
2. Select "ایجاد کاربر جدید" radio button
3. Fill in mobile (09xxxxxxxxx), first name, last name
4. Click "ایجاد کاربر" button

**Expected Result:**
- Form switches to new user fields
- Mobile validation enforces 09xxxxxxxxx format
- New user is created successfully
- User is auto-selected after creation
- View switches back to existing user with new user selected

**Pass Criteria:**
- [ ] Form switches correctly
- [ ] Mobile validation works
- [ ] User creation succeeds
- [ ] New user is auto-selected
- [ ] Success message appears

### 6. Print Type Field Behavior
**Steps:**
1. Open modal
2. Select "نوع چاپ" dropdown
3. Test each option:
   - سیاه و سفید: Should show only B&W page count field
   - رنگی: Should show only color page count field
   - ترکیبی: Should show both color and B&W page count fields

**Expected Result:**
- Fields show/hide based on selection
- Appropriate fields are marked required
- Non-visible fields have value set to 0

**Pass Criteria:**
- [ ] B&W print: Shows correct field
- [ ] Color print: Shows correct field
- [ ] Combined print: Shows both fields
- [ ] Validation works for each type

### 7. Paper Weight Dynamic Update
**Steps:**
1. Select a "نوع کاغذ" (paper type)
2. Observe "گرماژ کاغذ" (paper weight) dropdown

**Expected Result:**
- Paper weight options update based on paper type
- Available weights match the paper type selected

**Pass Criteria:**
- [ ] Weights update on paper type change
- [ ] Correct weights shown for each type

### 8. Price Calculation Test
**Steps:**
1. Fill all required fields:
   - Book title
   - Book size
   - Paper type & weight
   - Print type & page counts
   - Quantity
   - Binding type
   - License type
2. Click "محاسبه قیمت" button

**Expected Result:**
- Button shows loading state
- Price is calculated via AJAX
- Calculated price appears in "قیمت محاسبه شده" field
- Final price appears in "قیمت نهایی" field

**Pass Criteria:**
- [ ] Calculation succeeds
- [ ] Price displays correctly
- [ ] Both calculated and final prices shown

### 9. Price Override Test (Super Admin Only)
**Steps:**
1. Calculate price normally
2. Check "قیمت دلخواه" checkbox
3. Enter a custom price
4. Observe final price

**Expected Result:**
- Override checkbox enables price field
- Final price updates to override value
- Original calculated price remains visible

**Pass Criteria:**
- [ ] Checkbox enables override field
- [ ] Final price updates to override
- [ ] Can toggle back to calculated price

### 10. Order Submission Test
**Steps:**
1. Select a user (existing or create new)
2. Fill all order fields
3. Calculate price
4. Click "ثبت سفارش" button

**Expected Result:**
- Button shows loading state
- Order is submitted via AJAX
- Success message appears
- Page reloads to show new order
- Modal closes automatically

**Pass Criteria:**
- [ ] Submission succeeds
- [ ] Success message shown
- [ ] New order appears in list
- [ ] Modal closes after success

### 11. Color/Combined Print Order Test
**Steps:**
1. Create order with "رنگی" print type
   - Set color pages > 0
2. Create order with "ترکیبی" print type
   - Set both color and B&W pages > 0

**Expected Result:**
- Color order submits successfully with color_pages field
- Combined order submits with both page count fields
- No validation errors occur

**Pass Criteria:**
- [ ] Color order creates successfully
- [ ] Combined order creates successfully
- [ ] Page counts stored correctly in database

### 12. Responsive Design Test
**Steps:**
Test on different screen sizes:
- Desktop (1920px+): 3-column layout
- Tablet (768px-1024px): 2-column layout
- Mobile (<768px): 1-column layout

**Expected Result:**
- Layout adjusts to screen size
- All fields remain accessible
- No horizontal scroll
- Touch targets are adequate on mobile

**Pass Criteria:**
- [ ] Desktop: 3-column works
- [ ] Tablet: 2-column works
- [ ] Mobile: 1-column works
- [ ] No layout issues on any size

### 13. RTL Support Test
**Steps:**
1. View modal in Persian/RTL mode
2. Check text alignment
3. Check field layout
4. Check button positions

**Expected Result:**
- All text is right-aligned
- Fields flow right-to-left
- Icons are on correct side
- No LTR bleeding

**Pass Criteria:**
- [ ] Text aligns correctly
- [ ] Layout is properly mirrored
- [ ] Icons positioned correctly
- [ ] No direction conflicts

### 14. Form Validation Test
**Steps:**
1. Try submitting with empty required fields
2. Try invalid mobile number
3. Try negative quantities

**Expected Result:**
- Browser validation prevents submission
- Appropriate error messages show
- Focus moves to first invalid field

**Pass Criteria:**
- [ ] Required fields validated
- [ ] Mobile format validated
- [ ] Numeric fields validated
- [ ] User-friendly error messages

### 15. CSS Isolation Test
**Steps:**
1. Check that modal styles don't affect page
2. Check that page styles don't affect modal

**Expected Result:**
- Modal has independent styling via `.tabesh-admin-order-modal` namespace
- No visual conflicts with dashboard or other elements

**Pass Criteria:**
- [ ] Modal styling is isolated
- [ ] No conflicts with dashboard
- [ ] Custom properties work correctly

## Known Issues to Verify Are Fixed
- [ ] PR #86 issue: Color/combined print validation - Should be working now
- [ ] Asset enqueuing on shortcode pages - Fixed by inline enqueue
- [ ] Modal not appearing - Fixed by adding to template

## Browser Compatibility
Test on:
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

## Performance Checks
- [ ] Modal opens without lag (<100ms)
- [ ] AJAX requests complete quickly (<2s)
- [ ] No console errors
- [ ] No memory leaks on repeated open/close

## Security Checks
- [ ] Nonce verification works
- [ ] Only admins can access
- [ ] Input sanitization works
- [ ] XSS prevention works

## Success Criteria
✅ All test scenarios pass
✅ No console errors
✅ Smooth user experience
✅ Responsive on all devices
✅ RTL works correctly
✅ Color/combined orders work
✅ No styling conflicts

## Debugging Tips
If issues occur:
1. Check browser console for errors
2. Verify `tabeshAdminOrderCreator` object exists in console
3. Check Network tab for failed AJAX requests
4. Verify assets are loaded (admin-order-creator.css and .js)
5. Check WordPress debug.log for PHP errors

## Reporting Issues
When reporting issues, include:
- Browser and version
- Screen size
- Steps to reproduce
- Console errors (if any)
- Screenshots
- Expected vs actual behavior

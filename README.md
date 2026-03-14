# Captain J Ordering System (PHP + MySQL)
Minimal tea ordering system with inventory, admin/staff roles, and printable receipts.
Files included:
- public/ (webroot)
  - index.php (dashboard redirect)
  - login.php, logout.php
  - staff/order.php, staff/process_order.php, staff/receipt.php
  - admin/dashboard.php, admin/inventory.php, admin/users.php
  - assets/ (css/js)
- includes/
  - config.php, auth.php
- sql/schema.sql
- LICENSE

## Quick setup
1. Place the `public/` folder into your web server's document root.
   Place `includes/` one level above the public folder if possible, or keep as-is for quick test.
2. Create a MySQL database and user, then import `sql/schema.sql`:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
   The SQL includes a default admin user:
   - username: admin
   - password: admin123
3. Edit `includes/config.php` to set your DB credentials.
4. Ensure PHP 8+ and PDO MySQL extension are enabled.
5. Open `public/login.php` in your browser and login with the default admin.
6. Admin > Inventory to add items. Staff > Order to create orders and print receipts.

## Notes
- This package uses Bootstrap 5 via CDN for UI.
- Receipt printing uses browser `window.print()` (simple thermal/printer-friendly HTML).
- For PDF receipts server-side, integrate dompdf or TCPDF (instructions in README).
- This code is intentionally minimal for learning and customization.
"# captain-j-ordiring-POS-system" 

# ElectroHub - Database Setup Guide

This guide will help you integrate MySQL database into your ElectroHub project.

## Prerequisites

1. **XAMPP** (or any LAMP/WAMP stack) installed on your computer
2. **PHP 7.4 or higher**
3. **MySQL 5.7 or higher** (included with XAMPP)
4. **phpMyAdmin** (included with XAMPP)

---

## Step 1: Start MySQL Server

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Both modules should show **green** status

---

## Step 2: Create the Database

### Option A: Using phpMyAdmin (Recommended for beginners)

1. Open your web browser and go to: `http://localhost/phpmyadmin`
2. You should see the phpMyAdmin interface
3. Click on the **"New"** button in the left sidebar (or click **"Databases"** tab)
4. In the **"Create database"** field, enter: `electrohub_db`
5. Select **utf8mb4_unicode_ci** from the "Collation" dropdown
6. Click **"Create"**

### Option B: Using MySQL CLI

1. Open Command Prompt (Windows) or Terminal (Mac/Linux)
2. Navigate to your MySQL bin directory, usually:
   ```
   cd C:\xampp\mysql\bin
   ```
3. Run MySQL client:
   ```
   mysql -u root -p
   ```
4. Press Enter (default XAMPP has no password for root)
5. Create database:
   ```sql
   CREATE DATABASE electrohub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

---

## Step 3: Import Database Schema

### Using phpMyAdmin:

1. Go to `http://localhost/phpmyadmin`
2. Click on **electrohub_db** in the left sidebar
3. Click on the **"Import"** tab at the top
4. Click **"Choose File"** and select the `schema.sql` file (from your project directory where you saved it)
5. Scroll down and click **"Go"**
6. You should see a success message: "Import has been successfully finished"

### Using MySQL CLI:

```bash
cd C:\xampp\mysql\bin
mysql -u root -p electrohub_db < C:\path\to\your\schema.sql
```

Press Enter when prompted for password (if no password set).

---

## Step 4: Import Seed Data

This will populate your database with demo vendors, products, and other initial data.

### Using phpMyAdmin:

1. With **electrohub_db** selected in left sidebar
2. Click **"Import"** tab again
3. Choose the `seed.sql` file
4. Click **"Go"**
5. Success message should appear

### Using MySQL CLI:

```bash
cd C:\xampp\mysql\bin
mysql -u root -p electrohub_db < C:\path\to\your\seed.sql
```

---

## Step 5: Verify Database Tables

### Using phpMyAdmin:

1. With **electrohub_db** selected
2. You should see these tables in the left sidebar:
   - customers
   - vendors
   - products
   - orders
   - order_items
   - carts
   - wishlists
   - reviews
   - coupons
   - flash_sales
   - notifications
   - support_tickets
   - admin_logs
   - visited_products

3. Click on **"products"** table
4. Click **"Browse"** tab – you should see 10 demo products

---

## Step 6: Configure PHP Project Files

### 6.1 Copy Files to Your Project

Copy these files to your ElectroHub project directory (where your other PHP files are):

- `config.php` - Database configuration
- `db.php` - Database connection helper
- `data.php` - Replace your existing data.php with this new one
- `cart.php` - Replace existing
- `checkout.php` - Replace existing
- `orders.php` - Replace existing
- `order_details.php` - Replace existing
- `admin.php` - Replace existing
- `support.php` - Replace existing
- `buy_now.php` - Replace existing

### 6.2 Update Database Credentials

Open **config.php** and verify/update these settings:

```php
'db' => [
    'host' => '127.0.0.1',      // Usually localhost or 127.0.0.1
    'port' => 3306,             // Default MySQL port
    'database' => 'electrohub_db',
    'username' => 'root',        // Change if you have different username
    'password' => '',            // Add password if you set one
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

**IMPORTANT**: If your MySQL has a password, replace the empty `''` with your password in quotes.

---

## Step 7: Test the Integration

### 7.1 Start Your Local Server

If using XAMPP's built-in Apache:
- Your project should be in `C:\xampp\htdocs\electrohub\` folder
- Access via: `http://localhost/electrohub/index.php`

If using PHP built-in server:
```bash
cd C:\path\to\your\project
php -S localhost:8000
```
Then visit: `http://localhost:8000/index.php`

### 7.2 Test Customer Registration

1. Visit your site homepage
2. Click "Sign Up"
3. Create a new account with:
   - Name: Test User
   - Email: test@example.com
   - Password: test123
4. If successful, you're redirected to homepage

### 7.3 Verify Database Entry

In phpMyAdmin:
1. Select **electrohub_db**
2. Click **customers** table
3. Click **Browse** tab
4. You should see your new "Test User" entry

### 7.4 Test Demo Login

Try logging in with the pre-seeded demo account:
- Email: `demo@customer.com`
- Password: `demo123`

### 7.5 Test Product Browsing

1. Go to **Catalog** page
2. You should see 10 demo products
3. Click on any product to view details

### 7.6 Test Cart & Checkout

1. While logged in, add a product to cart
2. View cart page
3. Proceed to checkout
4. Fill in delivery details
5. Complete "payment"
6. Order should be created in database

Verify in phpMyAdmin:
- Check **orders** table for new entry
- Check **order_items** table for line items

### 7.7 Test Admin Console

1. Go to: `http://localhost/electrohub/admin.php` (or your URL)
2. Login with:
   - Admin ID: `RGMN`
   - Password: `Dont look at the password`
3. You should see dashboard with:
   - Customer count
   - Vendor count
   - Orders
   - Revenue charts

---

## Common Issues & Solutions

### Issue: "Connection refused" or "Unable to connect to database"

**Solutions:**
1. Check MySQL is running in XAMPP Control Panel
2. Verify credentials in `config.php`
3. Try connecting manually:
   ```bash
   cd C:\xampp\mysql\bin
   mysql -u root -p
   ```
4. If you can't connect, reset MySQL password in XAMPP

### Issue: "Table 'electrohub_db.products' doesn't exist"

**Solutions:**
1. Re-import `schema.sql` in phpMyAdmin
2. Check database name is exactly `electrohub_db` (case-sensitive on some systems)
3. Verify you're connected to the correct database in `config.php`

### Issue: "Call to undefined function password_hash()"

**Solutions:**
1. Upgrade to PHP 7.4 or higher
2. Check PHP version: `php -v` in command line

### Issue: "Access denied for user 'root'@'localhost'"

**Solutions:**
1. If you set a MySQL password, update it in `config.php`
2. Reset MySQL root password in XAMPP:
   - Stop MySQL in XAMPP
   - Click **Config** → **my.ini**
   - Find `[mysqld]` section
   - Add line: `skip-grant-tables`
   - Save, restart MySQL
   - Connect and reset password
   - Remove `skip-grant-tables` line
   - Restart MySQL again

### Issue: Products page is blank

**Solutions:**
1. Check you ran **both** `schema.sql` AND `seed.sql`
2. In phpMyAdmin, verify `products` table has 10 rows
3. Check PHP error logs in XAMPP Control Panel

### Issue: "Cannot modify header information - headers already sent"

**Solutions:**
1. Make sure there's no whitespace or `<?php` tags before the opening `<?php` in your PHP files
2. Save all PHP files with UTF-8 **without BOM** encoding

---

## Database Maintenance

### Backup Database

Using phpMyAdmin:
1. Select **electrohub_db**
2. Click **"Export"** tab
3. Select **"Quick"** export method
4. Click **"Go"**
5. Save the `.sql` file

### Reset Database to Fresh State

1. In phpMyAdmin, select **electrohub_db**
2. Click **"Operations"** tab
3. Scroll to **"Remove database"**
4. Confirm deletion
5. Re-create database following **Step 2**
6. Re-import `schema.sql` and `seed.sql`

### View Live Database

While testing your application:
1. Keep phpMyAdmin open in another browser tab
2. After each action (register, order, etc.), refresh the relevant table in phpMyAdmin
3. You'll see real-time data changes

---

## Next Steps

1. **Test all features**: Sign up, add to cart, checkout, admin functions
2. **Check error logs**: In `C:\xampp\php\logs\php_error_log`
3. **Add more vendors/products**: Use vendor signup and dashboard
4. **Customize**: Modify table structures in schema.sql as needed

---

## Security Notes for Production

⚠️ **These settings are for local development only**:

1. Change default MySQL root password
2. Create a dedicated MySQL user for your app (not root)
3. Update `config.php` with new credentials
4. Never commit `config.php` to version control (add to `.gitignore`)
5. Use environment variables for passwords in production
6. Enable HTTPS
7. Implement CSRF tokens for forms
8. Add input validation and sanitization

---

## Support

If you encounter issues not covered here:

1. Check PHP error logs
2. Check MySQL error logs in XAMPP
3. Verify all files are in the correct directory
4. Ensure file permissions allow PHP to read config files
5. Test database connection separately using a simple test script

---

## Test Database Connection Script

Create a file `test_db.php` in your project root:

```php
<?php
require_once 'db.php';

try {
    $pdo = Database::getInstance()->pdo();
    echo "✅ Database connection successful!<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "✅ Found {$result['count']} products in database<br>";
    
    echo "✅ Your database is working correctly!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
```

Visit `http://localhost/electrohub/test_db.php` to verify connection.

---

## Congratulations!

Your ElectroHub marketplace is now connected to a MySQL database and fully functional. All user registrations, orders, products, and other data will persist across sessions.

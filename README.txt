Sun and Space Online Store — PHP + MySQL
=========================================

Storefront with guest cart, customer login/register, checkout, and admin area.
Requires PHP and MySQL (e.g. XAMPP).

Directory layout
----------------
  sunandspace/           Deployable app (maps to Hostinger public_html)
  sunandspace_data/      Persistent data outside the web root (sibling folder)
    config/              database.php, payment.php (not in public_html)
    images/              Bundled product, branding, and QR images
    uploads/             User uploads (products, site branding, receipts)
    sql/                 Schema and migrations

Setup (XAMPP)
-------------
1. Start Apache and MySQL in XAMPP Control Panel.
2. Ensure sunandspace_data/ exists beside sunandspace/ (created on first setup).
3. Open phpMyAdmin: http://localhost/phpmyadmin
4. Import sunandspace_data/sql/schema.sql (creates database sunandspace, tables, seed data).
5. Edit sunandspace_data/config/database.php if your MySQL user/password differ from root / empty.
6. Visit http://localhost/sunandspace/

Default Local admin account
---------------------
  Email: admin@sunandspace.local
  Password: changeme
  

The same email has a linked customer row (role=customer) so admins can shop on
the storefront while signed into admin. Fresh installs get both rows from
sunandspace_data/sql/schema.sql.

Existing databases created before dual accounts: run once in phpMyAdmin:
  sunandspace_data/sql/migrate_admin_customer_account.sql

Existing databases before bank transfer receipts: run once in phpMyAdmin:
  sunandspace_data/sql/migrate_bank_transfer_receipt.sql

Existing databases with separate city fields: run once in phpMyAdmin:
  sunandspace_data/sql/migrate_remove_city.sql

Existing databases before order tracking: run once in phpMyAdmin:
  sunandspace_data/sql/migrate_order_tracking.sql

Existing databases before J&T shipping at checkout: run once in phpMyAdmin:
  sunandspace_data/sql/migrate_jt_shipping.sql
  Then copy sunandspace_data/config/jt-shipping.example.php to jt-shipping.php if needed,
  and set product weights in Admin → Products.

Existing databases before Google sign-in: run once in phpMyAdmin:
  sunandspace_data/sql/migrate_google_oauth.sql
  Then copy sunandspace_data/config/google-oauth.example.php to google-oauth.php,
  add OAuth credentials, and register the callback URL in Google Cloud Console.

Google sign-in (Continue with Google)
-------------------------------------
1. Google Cloud Console → APIs & Services → Credentials → Create OAuth client (Web application).
2. Authorized redirect URIs (add each environment you use):
     http://localhost/sunandspace/auth/google-callback.php
     http://localhost:8000/auth/google-callback.php
     https://yourdomain.com/auth/google-callback.php
3. Copy sunandspace_data/config/google-oauth.example.php to google-oauth.php.
4. Set enabled=true, client_id, and client_secret in google-oauth.php.
5. Leave redirect_uri empty to auto-detect from the current host, or set it explicitly in production.
6. Existing DB: import sunandspace_data/sql/migrate_google_oauth.sql once (adds users.google_id).

Customers can use Continue with Google on login/register. Existing email/password accounts
with the same verified Google email are linked automatically on first Google sign-in.

After admin sign-in, visiting the storefront logs you in as that customer
automatically. Storefront "Log out" ends the customer session only; admin
sign-in remains until you use Admin → Log out.

Change passwords after first login (phpMyAdmin or future admin tools).

Hostinger deployment
--------------------
1. Deploy only sunandspace/ files into public_html (index.php at public_html root).
2. Create /home/<user>/sunandspace_data/ beside public_html (one-time).
3. Copy config/, images/, uploads/, and sql/ into sunandspace_data/ on the server.
4. Edit sunandspace_data/config/database.php with production credentials.
5. Ensure sunandspace_data/uploads/ is writable by PHP.
6. On each deploy, sync public_html only — never overwrite sunandspace_data.

Customer flow
-------------
- Browse products and add to cart without signing in.
- Open cart, then Proceed to checkout.
- If not signed in, sign in or register, then fill shipping details and place order.
- Cash on delivery / bank transfer options are stored with the order (no payment gateway in v1).
- Bank transfer shows the QR Ph BPI receiver at checkout (sunandspace_data/config/payment.php). Customers must upload a payment receipt before placing the order. Receipts are stored in sunandspace_data/uploads/receipts/ and viewable from Admin → Orders.

Order status workflow
---------------------
Admin can set order status to Pending, Approved, In progress, or Delivered.
When marking an order In progress, admin is prompted for an optional tracking number.
Customers see status labels and tracking numbers on their orders list and order details.

Bank transfer configuration
---------------------------
- QR image: sunandspace_data/images/qr-ph-bpi.png
- Settings: sunandspace_data/config/payment.php (receiver label, instructions, QR path)
- Replace the QR image file or update payment.php to change payment details.

Files overview
--------------
  index.php          Storefront
  cart.php           Guest cart
  checkout.php       Auth gate + order form
  login.php          Customer sign in
  register.php       Customer sign up
  order-confirmation.php
  orders.php         Customer order history
  receipt.php        Customer receipt viewer
  media.php          Serves images from sunandspace_data
  admin/receipt.php    Admin receipt viewer
  admin/login.php    Admin sign in
  admin/index.php    Admin dashboard
  api/cart.php       Add-to-cart JSON API
  includes/          Bootstrap, auth, cart, layout

Editing products
----------------
Use phpMyAdmin on the products table, or use Admin → Products.
Product and branding images are served via media.php from sunandspace_data.

Colors and layout
-----------------
CSS custom properties at the top of assets/css/style.css.
Mobile menu and carousel: assets/js/main.js
Add to cart: assets/js/cart.js
Admin order status updates: assets/js/admin-orders.js

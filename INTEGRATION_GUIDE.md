# Akpa ison Integration Guide

**Complete step-by-step setup for MetaMonetize email system**

---

## 📌 Prerequisites

- ✅ MetaMonetize WordPress installation
- ✅ WPCode plugin installed & activated
- ✅ Elementor page builder (optional, for UI)
- ✅ SendGrid account with API key
- ✅ PHP 7.4+ and MySQL 5.7+
- ✅ Admin access to WordPress

---

## 🔧 Installation Steps

### Step 1: Set Up SendGrid Account (5 minutes)

**If you don't have SendGrid:**

1. Go to [sendgrid.com](https://sendgrid.com)
2. Sign up (free tier available)
3. Verify your email
4. In Dashboard → Settings → API Keys:
   - Click "Create API Key"
   - Name it "MetaMonetize Akpa ison"
   - Select "Full Access"
   - Copy the key (save securely)

**Verify Sender Email:**
1. Dashboard → Sender Authentication
2. Verify your sending domain or email
3. Follow SendGrid's verification process

---

### Step 2: Install Backend (PHP API)

**Via WPCode:**

1. **WordPress Admin Panel** → **Code Snippets** → **WPCode**
2. Click **+ Add Snippet**
3. Choose **PHP Snippet**
4. Title: `Akpa ison - Email API`
5. Copy entire content of `akpa-ison-backend.php`
6. In the right sidebar:
   - Set **Insertion Method**: Auto Insert
   - Set **Run Everywhere**: ON
   - Click **Save Snippet**
7. Click **Activate**

**Verify Installation:**
```bash
# Test via cURL or Postman
curl -X GET https://yoursite.com/wp-json/akpa-ison/v1/templates \
  -H "X-WP-Nonce: $(wp eval 'echo wp_create_nonce("wp_rest");')"
```

Should return JSON with template list.

---

### Step 3: Set Up Database Tables

**Option A: Via phpMyAdmin (Recommended)**

1. Go to **cPanel/Hosting Panel** → **phpMyAdmin**
2. Select your **WordPress database**
3. Click **SQL** tab
4. Copy entire `akpa-ison-database.sql` content
5. Paste and click **Go/Execute**

**Tables created:**
- `wp_akpa_ison_logs` (email history)
- `wp_akpa_ison_templates` (email templates)

**Option B: Via WPCode PHP Snippet**

Create new PHP snippet in WPCode:

```php
<?php
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

// Create logs table
$logs_table = $wpdb->prefix . 'akpa_ison_logs';
$sql_logs = "CREATE TABLE IF NOT EXISTS $logs_table (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  to_email varchar(255) NOT NULL,
  template varchar(100) NOT NULL,
  subject text NOT NULL,
  html longtext NOT NULL,
  status varchar(50) NOT NULL DEFAULT 'pending',
  user_id bigint(20) NOT NULL DEFAULT 0,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY to_email (to_email),
  KEY user_id (user_id),
  KEY created_at (created_at)
) $charset_collate;";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
dbDelta($sql_logs);

// Set default options
update_option('akpa_ison_from_name', 'MetaMonetize');

echo "✓ Akpa ison database tables created successfully!";
?>
```

**Verify Database:**
```sql
-- In phpMyAdmin, run:
SHOW TABLES LIKE '%akpa_ison%';
```

Should show:
- `wp_akpa_ison_logs`
- `wp_akpa_ison_templates`

---

### Step 4: Configure SendGrid API Key

**In WordPress Admin:**

1. Navigate to **Akpa ison Settings** (you may need to add this page first)
2. Add your SendGrid API Key
3. Set From Email: `noreply@yoursite.com` (verified in SendGrid)
4. Set From Name: `MetaMonetize`
5. Click **Save**

**Or via PHP (one-time setup):**

Create WPCode PHP snippet:

```php
<?php
update_option('akpa_ison_sendgrid_key', 'SG.your_api_key_here');
update_option('akpa_ison_from_email', 'noreply@metamonetize.com');
update_option('akpa_ison_from_name', 'MetaMonetize');

echo "✓ Settings saved!";
?>
```

---

### Step 5: Add Frontend Dashboard

**Option A: As Elementor Widget (Easiest)**

1. **Create New Elementor Page:**
   - WordPress Admin → Pages → Add New
   - Title: "Email Manager"
   - Open with Elementor

2. **Add React App:**
   - Add HTML Widget
   - Paste:
   ```html
   <div id="root"></div>
   ```

3. **Add Scripts (Page Settings):**
   - Elementor → Customize → Page Settings → Custom CSS
   - Add this code:
   ```html
   <script>
   window.wpAkpaIson = {
     nonce: '<?php echo wp_create_nonce('wp_rest'); ?>'
   };
   </script>
   
   <link rel="stylesheet" href="/wp-content/plugins/akpa-ison/AkpaIsonDashboard.css">
   <script src="/wp-content/plugins/akpa-ison/AkpaIsonDashboard.jsx"></script>
   ```

**Option B: As Standalone Page**

1. Create `akpa-ison-dashboard.php` in theme:

```php
<?php
/**
 * Template Name: Akpa ison Email Manager
 */

get_header();
?>

<div id="akpa-ison-root"></div>

<script>
window.wpAkpaIson = {
  nonce: '<?php echo wp_create_nonce('wp_rest'); ?>'
};
</script>

<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/AkpaIsonDashboard.css">
<script src="<?php echo get_template_directory_uri(); ?>/AkpaIsonDashboard.jsx"></script>

<?php get_footer(); ?>
```

2. Create page and select template

---

### Step 6: Test Email Sending

**Test via API (cURL):**

```bash
curl -X POST https://yoursite.com/wp-json/akpa-ison/v1/send-email \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE" \
  -d '{
    "to": "test@example.com",
    "template": "deposit_confirmed",
    "data": {
      "amount": "100",
      "coin": "USDT",
      "tx_id": "0x123abc",
      "new_balance": "1500"
    }
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Email sent successfully",
  "to": "test@example.com"
}
```

**Test via Dashboard:**
1. Go to your Akpa ison page
2. Fill in test email
3. Select template
4. Fill variables
5. Click "Send Email"
6. Check inbox (may take 1-2 seconds)

---

## 🔗 MetaMonetize Integration Examples

### Send Email on Deposit

Add this to your **deposit completion handler**:

```php
// In your MetaMonetize deposit function
do_action('metamonetize_deposit_complete', [
    'user_id' => $user_id,
    'amount' => $deposit_amount,
    'coin' => $coin_symbol,
    'tx_id' => $transaction_id,
    'new_balance' => $updated_balance
]);

// Then in your functions.php or WPCode:
add_action('metamonetize_deposit_complete', function($data) {
    $user = get_user_by('ID', $data['user_id']);
    
    wp_remote_post(
        home_url('/wp-json/akpa-ison/v1/send-email'),
        [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-WP-Nonce' => wp_create_nonce('wp_rest')
            ],
            'body' => json_encode([
                'to' => $user->user_email,
                'template' => 'deposit_confirmed',
                'data' => [
                    'amount' => $data['amount'],
                    'coin' => $data['coin'],
                    'tx_id' => $data['tx_id'],
                    'new_balance' => $data['new_balance']
                ]
            ])
        ]
    );
});
```

### Send Email on Withdrawal

```php
add_action('metamonetize_withdrawal_initiated', function($user_id, $amount, $coin, $address, $tx_id) {
    $user = get_user_by('ID', $user_id);
    
    wp_remote_post(
        home_url('/wp-json/akpa-ison/v1/send-email'),
        [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-WP-Nonce' => wp_create_nonce('wp_rest')
            ],
            'body' => json_encode([
                'to' => $user->user_email,
                'template' => 'withdrawal_initiated',
                'data' => [
                    'amount' => $amount,
                    'coin' => $coin,
                    'address' => $address,
                    'tx_id' => $tx_id,
                    'status' => 'Processing'
                ]
            ])
        ]
    );
}, 10, 5);
```

### Send Email on Swap

```php
add_action('metamonetize_swap_completed', function($user_id, $from_amount, $from_coin, $to_amount, $to_coin, $fee, $tx_id) {
    $user = get_user_by('ID', $user_id);
    $rate = $to_amount / $from_amount;
    
    wp_remote_post(
        home_url('/wp-json/akpa-ison/v1/send-email'),
        [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-WP-Nonce' => wp_create_nonce('wp_rest')
            ],
            'body' => json_encode([
                'to' => $user->user_email,
                'template' => 'swap_completed',
                'data' => [
                    'from_amount' => $from_amount,
                    'from_coin' => $from_coin,
                    'to_amount' => $to_amount,
                    'to_coin' => $to_coin,
                    'rate' => number_format($rate, 4),
                    'fee' => $fee,
                    'fee_coin' => $from_coin,
                    'tx_id' => $tx_id
                ]
            ])
        ]
    );
}, 10, 7);
```

---

## 📊 Testing Checklist

- [ ] SendGrid API key verified
- [ ] Database tables created
- [ ] Backend API responding
- [ ] Frontend dashboard loads
- [ ] Test email sends successfully
- [ ] Email appears in SendGrid logs
- [ ] Email received in test inbox
- [ ] Templates display correctly
- [ ] History shows sent emails
- [ ] Settings save properly

---

## 🔍 Debugging

**Check PHP Error Log:**
```bash
# Via SSH
tail -f wp-content/debug.log | grep "akpa"
```

**Test API via WordPress CLI:**
```bash
wp eval 'echo wp_create_nonce("wp_rest");'
```

**Check SendGrid Status:**
1. SendGrid Dashboard → Activity
2. Look for your test emails
3. Check delivery status and bounces

**Enable Debug Mode:**
In `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

---

## 🚀 Production Checklist

Before going live:

- [ ] SSL certificate installed (HTTPS only)
- [ ] SendGrid domain verified
- [ ] Backup database
- [ ] Test all email templates
- [ ] Monitor email delivery rate
- [ ] Set up email alerts for failures
- [ ] Restrict dashboard access (admin only)
- [ ] Log security policies
- [ ] Review email compliance (CAN-SPAM, GDPR)

---

## 📞 Support & Resources

- **WordPress**: [wp.org](https://wordpress.org)
- **SendGrid**: [sendgrid.com/docs](https://sendgrid.com/docs)
- **WPCode**: [wpcode.com](https://wpcode.com)
- **Elementor**: [elementor.com/docs](https://elementor.com/docs)

---

## 🎉 Next Steps

1. ✅ Complete integration
2. 🧪 Test all workflows
3. 📧 Monitor email delivery
4. 🎨 Customize templates (optional)
5. 📈 Track metrics in history

**You're ready to send emails like a pro!** 🚀

---

**For MetaMonetize Integration Support:**
[GitHub Issues](https://github.com/minnnntho/akpa-ison/issues)

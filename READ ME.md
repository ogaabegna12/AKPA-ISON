# Akpa ison 📧

**Transactional Email Manager for MetaMonetize**

A powerful, WordPress-integrated mailing system for sending and managing cryptocurrency transaction notifications, deposit confirmations, withdrawal alerts, and swap receipts on the MetaMonetize platform.

![Akpa ison Logo](./akpa-ison-logo.svg)

---

## 🎯 Features

✅ **Transactional Email Sending** - Send crypto transaction notifications instantly  
✅ **Pre-built Templates** - Deposit, withdrawal, swap, and alert templates  
✅ **Email History** - Track all sent emails with status and timestamps  
✅ **SendGrid Integration** - Enterprise-grade email delivery  
✅ **REST API** - Complete PHP REST endpoints for automation  
✅ **Glassmorphism UI** - Beautiful React dashboard matching MetaMonetize design  
✅ **Variable Templating** - Dynamic content injection ({amount}, {coin}, etc.)  
✅ **WordPress Native** - Seamless WPCode integration  

---

## 📋 Tech Stack

| Component | Technology |
|-----------|-----------|
| **Backend** | PHP (WPCode) |
| **Frontend** | React.js |
| **Database** | MySQL (WordPress) |
| **API** | WordPress REST API |
| **Email Provider** | SendGrid |
| **Styling** | CSS with Glassmorphism |
| **Authentication** | WordPress Nonce + User Roles |

---

## 🚀 Installation

### Step 1: Copy Backend PHP to WPCode

1. Go to **WordPress Admin → Code Snippets → WPCode**
2. Create a new snippet
3. Copy the entire content from `akpa-ison-backend.php`
4. Set to:
   - **Type**: PHP Snippet
   - **Auto Insert**: ON
   - **Run Everywhere**: ON
5. **Save & Activate**

### Step 2: Import Database Schema

1. Go to **phpMyAdmin** or your hosting panel
2. Select your WordPress database
3. Go to **SQL** tab
4. Paste content from `akpa-ison-database.sql`
5. Click **Execute**

This creates:
- `wp_akpa_ison_logs` table for email history
- `wp_akpa_ison_templates` table for email templates
- WordPress options for API keys

### Step 3: Integrate React Dashboard

**Option A: As an Elementor Widget**

1. Create a new Elementor page
2. Add **HTML Widget**
3. Add this wrapper:

```html
<div id="akpa-ison-root"></div>
```

4. In page footer (Elementor → Advanced → Custom CSS), add:

```html
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<link rel="stylesheet" href="/path/to/AkpaIsonDashboard.css">
<script src="/path/to/AkpaIsonDashboard.jsx"></script>
```

**Option B: As a Standalone Page**

1. Create a custom page template
2. Include the React component and CSS files
3. Make sure `wpAkpaIson.nonce` is set in JS for authentication

### Step 4: Configure SendGrid

1. Sign up at [sendgrid.com](https://sendgrid.com)
2. Create an API key
3. Go to Akpa ison Settings → Add SendGrid Key
4. Set "From Email" and "From Name"
5. Save & Test

---

## 📚 API Endpoints

### Send Email
```http
POST /wp-json/akpa-ison/v1/send-email
X-WP-Nonce: [nonce]
Content-Type: application/json

{
  "to": "user@example.com",
  "template": "deposit_confirmed",
  "data": {
    "amount": "100",
    "coin": "USDT",
    "tx_id": "0x123abc",
    "new_balance": "1500"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Email sent successfully",
  "to": "user@example.com"
}
```

### Get Templates
```http
GET /wp-json/akpa-ison/v1/templates
X-WP-Nonce: [nonce]
```

**Response:**
```json
{
  "deposit_confirmed": {
    "name": "Deposit Confirmed",
    "subject": "Your deposit of {amount} {coin} has been received",
    "description": "Sent when a deposit transaction is confirmed"
  },
  ...
}
```

### Get Email History
```http
GET /wp-json/akpa-ison/v1/history?limit=50&user_id=123
X-WP-Nonce: [nonce]
```

### Get Settings
```http
GET /wp-json/akpa-ison/v1/settings
X-WP-Nonce: [nonce]
Authorization: Bearer [admin_token]
```

### Update Settings
```http
POST /wp-json/akpa-ison/v1/settings
X-WP-Nonce: [nonce]
Authorization: Bearer [admin_token]
Content-Type: application/json

{
  "sendgrid_key": "SG.xxx",
  "from_email": "noreply@metamonetize.com",
  "from_name": "MetaMonetize"
}
```

---

## 🔌 Integration Examples

### Send Email on Deposit Confirmation

```php
// In your MetaMonetize deposit handler
add_action('metamonetize_deposit_confirmed', function($user_id, $amount, $coin, $tx_id) {
    $user = get_user_by('ID', $user_id);
    $new_balance = get_user_meta($user_id, 'crypto_balance', true);
    
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
                    'amount' => $amount,
                    'coin' => $coin,
                    'tx_id' => $tx_id,
                    'new_balance' => $new_balance
                ]
            ])
        ]
    );
}, 10, 4);
```

### Send Email on Swap

```php
// Trigger after swap completion
do_action('akpa_ison_send', [
    'to' => $user_email,
    'template' => 'swap_completed',
    'data' => [
        'from_amount' => '100',
        'from_coin' => 'USDT',
        'to_amount' => '98.5',
        'to_coin' => 'USDC',
        'rate' => '0.985',
        'fee' => '2',
        'fee_coin' => 'USDT',
        'tx_id' => $swap_tx_id
    ]
]);
```

---

## 📧 Email Templates

### Available Templates

1. **deposit_confirmed**
   - Variables: `{amount}`, `{coin}`, `{tx_id}`, `{new_balance}`
   - Use: Deposit completed

2. **withdrawal_initiated**
   - Variables: `{amount}`, `{coin}`, `{address}`, `{tx_id}`, `{status}`
   - Use: Withdrawal requested

3. **swap_completed**
   - Variables: `{from_amount}`, `{from_coin}`, `{to_amount}`, `{to_coin}`, `{rate}`, `{fee}`, `{fee_coin}`, `{tx_id}`
   - Use: Crypto swap completed

4. **transaction_alert**
   - Variables: `{type}`, `{amount}`, `{coin}`, `{timestamp}`
   - Use: Suspicious or large transactions

### Custom Template

To add a custom template, insert into `wp_akpa_ison_templates`:

```sql
INSERT INTO wp_akpa_ison_templates 
(template_key, name, subject, html, description) 
VALUES 
(
  'custom_template',
  'My Template',
  'Subject with {variables}',
  '<html>Body with {variables}</html>',
  'Description'
);
```

---

## 🎨 Design

Akpa ison uses MetaMonetize's design system:

- **Primary Dark**: `#020c2b`
- **Secondary Dark**: `#0f1429`
- **Accent Teal**: `#1dd1a1`
- **Accent Light**: `#00d4aa`
- **Effect**: Glassmorphism with backdrop blur

All emails use responsive HTML with inline CSS matching this palette.

---

## 🔐 Security

✅ **WordPress Nonce Protection** - All API calls require valid nonce  
✅ **User Capability Check** - Admin functions check `manage_options`  
✅ **Email Validation** - All emails validated before sending  
✅ **Input Sanitization** - All user inputs sanitized and escaped  
✅ **HTTPS Required** - SendGrid uses TLS encryption  
✅ **API Key Security** - Keys stored in WordPress options, never logged  

---

## 📊 Database Schema

### wp_akpa_ison_logs
```sql
id              | mediumint    | Primary Key
to_email        | varchar(255) | Recipient email
template        | varchar(100) | Template used
subject         | text         | Email subject
html            | longtext     | Email body
status          | varchar(50)  | sent | failed | pending
user_id         | bigint       | WordPress user ID
created_at      | datetime     | Timestamp
```

### wp_akpa_ison_templates
```sql
id              | mediumint    | Primary Key
template_key    | varchar(100) | Unique identifier
name            | varchar(255) | Display name
subject         | text         | Email subject template
html            | longtext     | Email body template
description     | text         | Template description
active          | tinyint      | 1 or 0
created_at      | datetime     | Created timestamp
updated_at      | datetime     | Updated timestamp
```

---

## 🐛 Troubleshooting

### Emails not sending?

1. **Check SendGrid key** - Dashboard → Settings → Verify API key
2. **Check from_email** - Must be verified SendGrid sender
3. **Check logs** - History tab shows failed emails
4. **Check PHP errors** - Look in WordPress error log (`/wp-content/debug.log`)

### Database table not created?

Run this in WPCode PHP snippet:
```php
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
global $wpdb;
$charset_collate = $wpdb->get_charset_collate();
$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}akpa_ison_logs (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  to_email varchar(255) NOT NULL,
  template varchar(100) NOT NULL,
  subject text NOT NULL,
  html longtext NOT NULL,
  status varchar(50) NOT NULL,
  user_id bigint(20) NOT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) $charset_collate;";
dbDelta($sql);
```

### React component not loading?

1. Verify React/React-DOM CDN links are loaded
2. Check console for errors: `F12 → Console tab`
3. Ensure `wpAkpaIson.nonce` is set globally
4. Check file paths for `.css` and `.jsx`

---

## 📝 License

MIT License - Feel free to modify and integrate into your projects.

---

## 🤝 Support

Need help? Check:
- MetaMonetize documentation
- WordPress plugin docs at [wp.org](https://wordpress.org)
- SendGrid docs at [sendgrid.com](https://sendgrid.com)

---

## 🎉 Version History

**v1.0.0** (2026-07-30)
- Initial release
- 4 pre-built templates
- SendGrid integration
- React dashboard
- Complete API

---

**Built with ❤️ for MetaMonetize**

[GitHub](https://github.com/minnnntho/akpa-ison) • [MetaMonetize](https://metamonetize.com)

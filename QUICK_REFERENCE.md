# Akpa ison Quick Reference

**Common use cases, API calls, and code snippets**

---

## 📧 Send Email (Quickest Way)

### Method 1: Via PHP/WPCode

```php
<?php
$response = wp_remote_post(
    home_url('/wp-json/akpa-ison/v1/send-email'),
    [
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'X-WP-Nonce' => wp_create_nonce('wp_rest')
        ],
        'body' => json_encode([
            'to' => 'user@example.com',
            'template' => 'deposit_confirmed',
            'data' => [
                'amount' => '100',
                'coin' => 'USDT',
                'tx_id' => '0x123abc...',
                'new_balance' => '1500'
            ]
        ])
    ]
);

$body = json_decode(wp_remote_retrieve_body($response), true);
if ($body['success']) {
    echo "✓ Email sent!";
} else {
    echo "✗ Error: " . $body['error'];
}
?>
```

### Method 2: Via cURL (CLI)

```bash
curl -X POST https://yoursite.com/wp-json/akpa-ison/v1/send-email \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: $(wp eval 'echo wp_create_nonce("wp_rest");')" \
  -d '{
    "to": "user@example.com",
    "template": "deposit_confirmed",
    "data": {
      "amount": "100",
      "coin": "USDT",
      "tx_id": "0x123abc",
      "new_balance": "1500"
    }
  }'
```

### Method 3: Via JavaScript

```javascript
const sendEmail = async (to, template, data) => {
  const response = await fetch('/wp-json/akpa-ison/v1/send-email', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': window.wpAkpaIson?.nonce || ''
    },
    body: JSON.stringify({ to, template, data })
  });
  
  const result = await response.json();
  return result;
};

// Usage:
sendEmail('user@example.com', 'deposit_confirmed', {
  amount: '100',
  coin: 'USDT',
  tx_id: '0x123abc',
  new_balance: '1500'
});
```

---

## 🔄 Common Scenarios

### Deposit Received Email

```php
do_action('metamonetize_deposit', [
    'user_id' => $user_id,
    'amount' => '100',
    'coin' => 'USDT',
    'tx_id' => '0x123...',
    'new_balance' => get_user_meta($user_id, 'usdt_balance', true)
]);

add_action('metamonetize_deposit', 'akpa_send_deposit_email');
function akpa_send_deposit_email($data) {
    $user = get_user_by('ID', $data['user_id']);
    
    wp_remote_post(home_url('/wp-json/akpa-ison/v1/send-email'), [
        'body' => json_encode([
            'to' => $user->user_email,
            'template' => 'deposit_confirmed',
            'data' => $data
        ]),
        'headers' => ['Content-Type' => 'application/json']
    ]);
}
```

### Withdrawal Initiated Email

```php
do_action('metamonetize_withdrawal', [
    'user_id' => $user_id,
    'amount' => '50',
    'coin' => 'USDT',
    'address' => '0x742d35Cc6634C0532925a3b844Bc...',
    'tx_id' => '0x456...',
    'status' => 'Pending'
]);

add_action('metamonetize_withdrawal', 'akpa_send_withdrawal_email');
function akpa_send_withdrawal_email($data) {
    $user = get_user_by('ID', $data['user_id']);
    
    wp_remote_post(home_url('/wp-json/akpa-ison/v1/send-email'), [
        'body' => json_encode([
            'to' => $user->user_email,
            'template' => 'withdrawal_initiated',
            'data' => [
                'amount' => $data['amount'],
                'coin' => $data['coin'],
                'address' => substr($data['address'], 0, 10) . '...',
                'tx_id' => $data['tx_id'],
                'status' => $data['status']
            ]
        ]),
        'headers' => ['Content-Type' => 'application/json']
    ]);
}
```

### Swap Completed Email

```php
do_action('metamonetize_swap', [
    'user_id' => $user_id,
    'from_amount' => '100',
    'from_coin' => 'USDT',
    'to_amount' => '98.5',
    'to_coin' => 'USDC',
    'rate' => '0.985',
    'fee' => '2',
    'tx_id' => '0x789...'
]);

add_action('metamonetize_swap', 'akpa_send_swap_email');
function akpa_send_swap_email($data) {
    $user = get_user_by('ID', $data['user_id']);
    
    wp_remote_post(home_url('/wp-json/akpa-ison/v1/send-email'), [
        'body' => json_encode([
            'to' => $user->user_email,
            'template' => 'swap_completed',
            'data' => $data
        ]),
        'headers' => ['Content-Type' => 'application/json']
    ]);
}
```

### Transaction Alert (Large/Suspicious)

```php
function check_suspicious_transaction($user_id, $amount, $coin) {
    $large_threshold = 1000; // USDT
    $unusual_time = date('H') >= 23 || date('H') <= 5; // Night time
    
    if ($amount > $large_threshold || $unusual_time) {
        $user = get_user_by('ID', $user_id);
        
        wp_remote_post(home_url('/wp-json/akpa-ison/v1/send-email'), [
            'body' => json_encode([
                'to' => $user->user_email,
                'template' => 'transaction_alert',
                'data' => [
                    'type' => 'Large Transaction',
                    'amount' => $amount,
                    'coin' => $coin,
                    'timestamp' => current_time('Y-m-d H:i:s')
                ]
            ]),
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }
}
```

---

## 📋 Get Email History

### Get All Emails

```php
$response = wp_remote_get(
    home_url('/wp-json/akpa-ison/v1/history?limit=100'),
    ['headers' => ['X-WP-Nonce' => wp_create_nonce('wp_rest')]]
);
$emails = json_decode(wp_remote_retrieve_body($response));
```

### Get User's Emails Only

```php
$user_id = get_current_user_id();
$response = wp_remote_get(
    home_url('/wp-json/akpa-ison/v1/history?limit=50&user_id=' . $user_id),
    ['headers' => ['X-WP-Nonce' => wp_create_nonce('wp_rest')]]
);
$user_emails = json_decode(wp_remote_retrieve_body($response));
```

### Filter Failed Emails

```php
global $wpdb;
$failed = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}akpa_ison_logs 
     WHERE status = 'failed' 
     ORDER BY created_at DESC 
     LIMIT 50"
);

foreach ($failed as $email) {
    echo $email->to_email . " - " . $email->subject . "\n";
}
```

---

## 🔧 Manage Templates

### Get All Templates

```php
$response = wp_remote_get(
    home_url('/wp-json/akpa-ison/v1/templates'),
    ['headers' => ['X-WP-Nonce' => wp_create_nonce('wp_rest')]]
);
$templates = json_decode(wp_remote_retrieve_body($response), true);

foreach ($templates as $key => $template) {
    echo $template['name'] . " (" . $key . ")\n";
}
```

### Create Custom Template

```sql
INSERT INTO wp_akpa_ison_templates 
(template_key, name, subject, html, description) 
VALUES 
(
  'welcome_bonus',
  'Welcome Bonus Awarded',
  'You received {amount} {coin} as a welcome bonus!',
  '<div style="background: linear-gradient(135deg, #020c2b 0%, #0f1429 100%); padding: 40px; border-radius: 12px; color: #fff;">
    <h2 style="color: #1dd1a1;">🎉 Welcome to MetaMonetize!</h2>
    <p>You received <strong>{amount} {coin}</strong> as a welcome bonus.</p>
    <p>Your current balance: <strong>{new_balance}</strong></p>
  </div>',
  'Sent when new user receives welcome bonus'
);
```

---

## 🔐 Admin Functions

### Get Settings

```php
$sendgrid_key = get_option('akpa_ison_sendgrid_key');
$from_email = get_option('akpa_ison_from_email');
$from_name = get_option('akpa_ison_from_name');
```

### Update Settings

```php
update_option('akpa_ison_sendgrid_key', 'SG.your_key');
update_option('akpa_ison_from_email', 'noreply@metamonetize.com');
update_option('akpa_ison_from_name', 'MetaMonetize');
```

### Clear Old Logs (Maintenance)

```php
global $wpdb;

// Delete emails older than 90 days
$wpdb->query(
    "DELETE FROM {$wpdb->prefix}akpa_ison_logs 
     WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
);

echo "✓ Old logs cleared";
```

---

## 🎨 Template Variables Reference

### deposit_confirmed
```
{amount}      - Deposit amount (e.g., "100")
{coin}        - Coin symbol (e.g., "USDT")
{tx_id}       - Transaction ID
{new_balance} - Updated account balance
```

### withdrawal_initiated
```
{amount}  - Withdrawal amount
{coin}    - Coin symbol
{address} - Wallet address (can truncate)
{tx_id}   - Transaction ID
{status}  - Status (Pending, Processing, Confirmed)
```

### swap_completed
```
{from_amount} - Amount sent (e.g., "100")
{from_coin}   - Coin sent (e.g., "USDT")
{to_amount}   - Amount received (e.g., "98.5")
{to_coin}     - Coin received (e.g., "USDC")
{rate}        - Exchange rate (e.g., "0.985")
{fee}         - Fee amount (e.g., "2")
{fee_coin}    - Fee coin (e.g., "USDT")
{tx_id}       - Transaction ID
```

### transaction_alert
```
{type}      - Alert type (e.g., "Large Transaction")
{amount}    - Transaction amount
{coin}      - Coin symbol
{timestamp} - When it occurred
```

---

## 🐛 Debugging Tips

### Log All Email Attempts

```php
add_action('akpa_ison_send_email', function($to, $template, $data) {
    error_log("📧 Akpa ison - Sending $template to $to");
    error_log("Data: " . json_encode($data));
}, 10, 3);
```

### Check Last 10 Emails Sent

```php
global $wpdb;
$emails = $wpdb->get_results(
    "SELECT to_email, template, status, created_at 
     FROM {$wpdb->prefix}akpa_ison_logs 
     ORDER BY created_at DESC 
     LIMIT 10"
);

foreach ($emails as $email) {
    echo sprintf(
        "%s | %s | %s | %s\n",
        $email->created_at,
        $email->to_email,
        $email->template,
        $email->status
    );
}
```

### Test SendGrid Connection

```php
$api_key = get_option('akpa_ison_sendgrid_key');

if (!$api_key) {
    echo "❌ No SendGrid API key configured";
    return;
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api.sendgrid.com/v3/mail/send',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        'personalizations' => [['to' => [['email' => 'test@sendgrid.com']], 'subject' => 'Test']],
        'from' => ['email' => get_option('akpa_ison_from_email')],
        'content' => [['type' => 'text/html', 'value' => 'Test']]
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo $status >= 200 && $status < 300 ? "✓ Connection OK" : "❌ Connection Failed";
```

---

## 📊 Email Statistics

### Count Emails by Status

```php
global $wpdb;

$stats = $wpdb->get_results(
    "SELECT status, COUNT(*) as count 
     FROM {$wpdb->prefix}akpa_ison_logs 
     GROUP BY status"
);

foreach ($stats as $stat) {
    echo $stat->status . ": " . $stat->count . "\n";
}
```

### Success Rate

```php
global $wpdb;

$total = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}akpa_ison_logs"
);

$sent = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}akpa_ison_logs 
     WHERE status = 'sent'"
);

$rate = $total > 0 ? round(($sent / $total) * 100, 2) : 0;
echo "✓ Success Rate: " . $rate . "%\n";
```

---

## 🚀 Performance Tips

1. **Batch Emails** - Queue multiple sends instead of one-by-one
2. **Cache Templates** - Use transients for template data
3. **Archive Logs** - Move old logs to archive table monthly
4. **Monitor Queue** - Check pending emails and retry failures
5. **Use Webhooks** - Subscribe to SendGrid webhooks for delivery tracking

---

## 🎯 Real-World Example: Complete Deposit Flow

```php
// When deposit webhook received
function metamonetize_handle_deposit_webhook($data) {
    $user_id = $data['user_id'];
    $amount = $data['amount'];
    $coin = $data['coin'];
    $tx_id = $data['transaction_id'];
    
    // Update user balance
    $balance = get_user_meta($user_id, "{$coin}_balance", true) ?: 0;
    $new_balance = $balance + $amount;
    update_user_meta($user_id, "{$coin}_balance", $new_balance);
    
    // Log deposit
    add_user_activity($user_id, "Deposit: +$amount $coin (TX: $tx_id)");
    
    // Send confirmation email
    wp_remote_post(home_url('/wp-json/akpa-ison/v1/send-email'), [
        'method' => 'POST',
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode([
            'to' => get_user_by('ID', $user_id)->user_email,
            'template' => 'deposit_confirmed',
            'data' => [
                'amount' => $amount,
                'coin' => $coin,
                'tx_id' => $tx_id,
                'new_balance' => number_format($new_balance, 8)
            ]
        ])
    ]);
    
    // Check if large transaction
    if ($amount > 1000) {
        wp_remote_post(home_url('/wp-json/akpa-ison/v1/send-email'), [
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'to' => get_user_by('ID', $user_id)->user_email,
                'template' => 'transaction_alert',
                'data' => [
                    'type' => 'Large Deposit',
                    'amount' => $amount,
                    'coin' => $coin,
                    'timestamp' => current_time('Y-m-d H:i:s')
                ]
            ])
        ]);
    }
    
    return ['status' => 'success'];
}
```

---

**Happy emailing! 🚀**

For more help, check the main README.md and INTEGRATION_GUIDE.md

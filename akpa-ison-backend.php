<?php
/**
 * Akpa ison - Mailing API for MetaMonetize
 * REST API endpoints for sending and managing transactional emails
 * 
 * Integration: WPCode PHP Snippet (Auto Insert, Run Everywhere)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define API base
define('AKPA_ISON_API_BASE', '/wp-json/akpa-ison/v1');

// Register REST routes
add_action('rest_api_init', function () {
    // Send email endpoint
    register_rest_route('akpa-ison/v1', '/send-email', [
        'methods' => 'POST',
        'callback' => 'akpa_ison_send_email',
        'permission_callback' => 'akpa_ison_verify_nonce',
        'args' => [
            'to' => ['required' => true],
            'template' => ['required' => true],
            'data' => ['required' => false],
        ],
    ]);

    // Get email templates
    register_rest_route('akpa-ison/v1', '/templates', [
        'methods' => 'GET',
        'callback' => 'akpa_ison_get_templates',
        'permission_callback' => 'is_user_logged_in',
    ]);

    // Get email history
    register_rest_route('akpa-ison/v1', '/history', [
        'methods' => 'GET',
        'callback' => 'akpa_ison_get_history',
        'permission_callback' => 'is_user_logged_in',
        'args' => [
            'limit' => ['default' => 50],
            'user_id' => ['default' => 0],
        ],
    ]);

    // Get settings
    register_rest_route('akpa-ison/v1', '/settings', [
        'methods' => 'GET',
        'callback' => 'akpa_ison_get_settings',
        'permission_callback' => 'akpa_ison_verify_admin',
    ]);

    // Update settings
    register_rest_route('akpa-ison/v1', '/settings', [
        'methods' => 'POST',
        'callback' => 'akpa_ison_update_settings',
        'permission_callback' => 'akpa_ison_verify_admin',
        'args' => [
            'sendgrid_key' => ['required' => false],
            'from_email' => ['required' => false],
            'from_name' => ['required' => false],
        ],
    ]);
});

/**
 * Verify nonce for API requests
 */
function akpa_ison_verify_nonce($request) {
    $nonce = $request->get_header('X-WP-Nonce');
    if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
        return is_user_logged_in();
    }
    return true;
}

/**
 * Verify admin capability
 */
function akpa_ison_verify_admin() {
    return current_user_can('manage_options');
}

/**
 * Send email callback
 */
function akpa_ison_send_email($request) {
    $to = sanitize_email($request->get_param('to'));
    $template = sanitize_text_field($request->get_param('template'));
    $data = $request->get_param('data') ?? [];

    // Validate
    if (!is_email($to)) {
        return new WP_REST_Response(['error' => 'Invalid email'], 400);
    }

    // Get template
    $email_template = akpa_ison_get_email_template($template);
    if (!$email_template) {
        return new WP_REST_Response(['error' => 'Template not found'], 404);
    }

    // Replace variables in template
    $subject = akpa_ison_replace_variables($email_template['subject'], $data);
    $html = akpa_ison_replace_variables($email_template['html'], $data);

    // Send email via SendGrid
    $result = akpa_ison_send_via_sendgrid($to, $subject, $html);

    if ($result) {
        // Log email
        akpa_ison_log_email($to, $template, $subject, $html, 'sent');

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Email sent successfully',
            'to' => $to,
        ], 200);
    } else {
        akpa_ison_log_email($to, $template, $subject, $html, 'failed');
        return new WP_REST_Response([
            'success' => false,
            'error' => 'Failed to send email',
        ], 500);
    }
}

/**
 * Get email templates
 */
function akpa_ison_get_templates($request) {
    $templates = [
        'deposit_confirmed' => [
            'name' => 'Deposit Confirmed',
            'subject' => 'Your deposit of {amount} {coin} has been received',
            'description' => 'Sent when a deposit transaction is confirmed',
        ],
        'withdrawal_initiated' => [
            'name' => 'Withdrawal Initiated',
            'subject' => 'Your withdrawal of {amount} {coin} has been processed',
            'description' => 'Sent when a withdrawal is initiated',
        ],
        'swap_completed' => [
            'name' => 'Swap Completed',
            'subject' => 'You swapped {from_amount} {from_coin} to {to_amount} {to_coin}',
            'description' => 'Sent when a crypto swap is completed',
        ],
        'transaction_alert' => [
            'name' => 'Transaction Alert',
            'subject' => 'New transaction: {type}',
            'description' => 'Sent for suspicious or large transactions',
        ],
    ];

    return new WP_REST_Response($templates, 200);
}

/**
 * Get email history
 */
function akpa_ison_get_history($request) {
    global $wpdb;

    $limit = intval($request->get_param('limit'));
    $user_id = intval($request->get_param('user_id'));

    $query = "SELECT * FROM {$wpdb->prefix}akpa_ison_logs";
    $params = [];

    if ($user_id > 0) {
        $query .= " WHERE user_id = %d";
        $params[] = $user_id;
    }

    $query .= " ORDER BY created_at DESC LIMIT %d";
    $params[] = $limit;

    if ($params) {
        $results = $wpdb->get_results($wpdb->prepare($query, $params));
    } else {
        $results = $wpdb->get_results($query);
    }

    return new WP_REST_Response($results, 200);
}

/**
 * Get Akpa ison settings
 */
function akpa_ison_get_settings($request) {
    $settings = [
        'sendgrid_key' => get_option('akpa_ison_sendgrid_key', ''),
        'from_email' => get_option('akpa_ison_from_email', get_option('admin_email')),
        'from_name' => get_option('akpa_ison_from_name', get_bloginfo('name')),
    ];

    return new WP_REST_Response($settings, 200);
}

/**
 * Update Akpa ison settings
 */
function akpa_ison_update_settings($request) {
    $sendgrid_key = sanitize_text_field($request->get_param('sendgrid_key'));
    $from_email = sanitize_email($request->get_param('from_email'));
    $from_name = sanitize_text_field($request->get_param('from_name'));

    if ($sendgrid_key) {
        update_option('akpa_ison_sendgrid_key', $sendgrid_key);
    }
    if ($from_email) {
        update_option('akpa_ison_from_email', $from_email);
    }
    if ($from_name) {
        update_option('akpa_ison_from_name', $from_name);
    }

    return new WP_REST_Response(['success' => true, 'message' => 'Settings updated'], 200);
}

/**
 * Send email via SendGrid
 */
function akpa_ison_send_via_sendgrid($to, $subject, $html) {
    $api_key = get_option('akpa_ison_sendgrid_key');
    $from_email = get_option('akpa_ison_from_email', get_option('admin_email'));
    $from_name = get_option('akpa_ison_from_name', get_bloginfo('name'));

    if (!$api_key) {
        // Fallback to WordPress wp_mail if no SendGrid key
        return wp_mail($to, $subject, $html, ['Content-Type: text/html; charset=UTF-8']);
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.sendgrid.com/v3/mail/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode([
            'personalizations' => [
                [
                    'to' => [['email' => $to]],
                    'subject' => $subject,
                ]
            ],
            'from' => [
                'email' => $from_email,
                'name' => $from_name,
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $html,
                ]
            ],
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return $http_code >= 200 && $http_code < 300;
}

/**
 * Get email template
 */
function akpa_ison_get_email_template($template_name) {
    $templates = [
        'deposit_confirmed' => [
            'subject' => 'Your deposit of {amount} {coin} has been received',
            'html' => '
                <div style="font-family: Arial, sans-serif; background: linear-gradient(135deg, #020c2b 0%, #0f1429 100%); padding: 40px; border-radius: 12px; color: #fff;">
                    <h2 style="color: #1dd1a1;">Deposit Confirmed</h2>
                    <p>You have successfully deposited <strong>{amount} {coin}</strong>.</p>
                    <p>Transaction ID: <code>{tx_id}</code></p>
                    <p>Your balance is now: <strong>{new_balance}</strong></p>
                </div>
            ',
        ],
        'withdrawal_initiated' => [
            'subject' => 'Your withdrawal of {amount} {coin} has been processed',
            'html' => '
                <div style="font-family: Arial, sans-serif; background: linear-gradient(135deg, #020c2b 0%, #0f1429 100%); padding: 40px; border-radius: 12px; color: #fff;">
                    <h2 style="color: #1dd1a1;">Withdrawal Initiated</h2>
                    <p>Your withdrawal of <strong>{amount} {coin}</strong> has been initiated.</p>
                    <p>Receiving Address: <code>{address}</code></p>
                    <p>Transaction ID: <code>{tx_id}</code></p>
                    <p>Status: <strong>{status}</strong></p>
                </div>
            ',
        ],
        'swap_completed' => [
            'subject' => 'Swap Completed: {from_amount} {from_coin} → {to_amount} {to_coin}',
            'html' => '
                <div style="font-family: Arial, sans-serif; background: linear-gradient(135deg, #020c2b 0%, #0f1429 100%); padding: 40px; border-radius: 12px; color: #fff;">
                    <h2 style="color: #1dd1a1;">Swap Completed</h2>
                    <p>You swapped <strong>{from_amount} {from_coin}</strong> to <strong>{to_amount} {to_coin}</strong>.</p>
                    <p>Rate: <strong>{rate}</strong></p>
                    <p>Fee: <strong>{fee} {fee_coin}</strong></p>
                    <p>Transaction ID: <code>{tx_id}</code></p>
                </div>
            ',
        ],
    ];

    return $templates[$template_name] ?? null;
}

/**
 * Replace variables in template
 */
function akpa_ison_replace_variables($text, $data = []) {
    foreach ($data as $key => $value) {
        $text = str_replace('{' . $key . '}', esc_html($value), $text);
    }
    return $text;
}

/**
 * Log email
 */
function akpa_ison_log_email($to, $template, $subject, $html, $status) {
    global $wpdb;

    $wpdb->insert(
        $wpdb->prefix . 'akpa_ison_logs',
        [
            'to_email' => $to,
            'template' => $template,
            'subject' => $subject,
            'html' => $html,
            'status' => $status,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ],
        ['%s', '%s', '%s', '%s', '%s', '%d', '%s']
    );
}

// Create database table on plugin activation
function akpa_ison_create_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'akpa_ison_logs';

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        to_email varchar(255) NOT NULL,
        template varchar(100) NOT NULL,
        subject text NOT NULL,
        html longtext NOT NULL,
        status varchar(50) NOT NULL,
        user_id bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY to_email (to_email),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

add_action('wp_loaded', 'akpa_ison_create_table');

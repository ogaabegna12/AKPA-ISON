-- Akpa ison Database Schema
-- Email logging and template management for MetaMonetize

-- Email Logs Table
CREATE TABLE IF NOT EXISTS wp_akpa_ison_logs (
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
  KEY status (status),
  KEY created_at (created_at),
  KEY template (template)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Templates Table
CREATE TABLE IF NOT EXISTS wp_akpa_ison_templates (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  template_key varchar(100) NOT NULL UNIQUE,
  name varchar(255) NOT NULL,
  subject text NOT NULL,
  html longtext NOT NULL,
  description text,
  active tinyint(1) DEFAULT 1,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY template_key (template_key),
  KEY active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Templates
INSERT INTO wp_akpa_ison_templates (template_key, name, subject, description, html) VALUES
(
  'deposit_confirmed',
  'Deposit Confirmed',
  'Your deposit of {amount} {coin} has been received',
  'Sent when a deposit transaction is confirmed',
  '<div style="font-family: Arial, sans-serif; background: linear-gradient(135deg, #020c2b 0%, #0f1429 100%); padding: 40px; border-radius: 12px; color: #fff;">
    <h2 style="color: #1dd1a1;">Deposit Confirmed</h2>
    <p>You have successfully deposited <strong>{amount} {coin}</strong>.</p>
    <p>Transaction ID: <code>{tx_id}</code></p>
    <p>Your balance is now: <strong>{new_balance}</strong></p>
    <hr style="border: none; border-top: 1px solid rgba(29, 209, 161, 0.2); margin: 20px 0;">
    <p style="font-size: 12px; color: #b8c5d6;">MetaMonetize &copy; 2026. All rights reserved.</p>
  </div>'
),
(
  'withdrawal_initiated',
  'Withdrawal Initiated',
  'Your withdrawal of {amount} {coin} has been processed',
  'Sent when a withdrawal is initiated',
  '<div style="font-family: Arial, sans-serif; background: linear-gradient(135deg, #020c2b 0%, #0f1429 100%); padding: 40px; border-radius: 12px; color: #fff;">
    <h2 style="color: #1dd1a1;">Withdrawal Initiated</h2>
    <p>Your withdrawal of <strong>{amount} {coin}</strong> has been initiated.</p>
    <p>Receiving Address: <code>{address}</code></p>
    <p>Transaction ID: <code>{tx_id}</code></p>
    <p>Status: <strong>{status}</strong></p>
    <hr style="border: none; border-top: 1px solid rgba(29, 209, 161, 0.2); margin: 20px 0;">
    <p style="font-size: 12px; color: #b8c5d6;">MetaMonetize &copy; 2026. All rights reserved.</p>
  </div>'
),
(
  'swap_completed',
  'Swap Completed',
  'Swap Completed: {from_amount} {from_coin} → {to_amount} {to_coin}',
  'Sent when a crypto swap is completed',
  '<div style="font-family: Arial, sans-serif; background: linear-gradient(135deg, #020c2b 0%, #0f1429 100%); padding: 40px; border-radius: 12px; color: #fff;">
    <h2 style="color: #1dd1a1;">Swap Completed</h2>
    <p>You swapped <strong>{from_amount} {from_coin}</strong> to <strong>{to_amount} {to_coin}</strong>.</p>
    <p>Rate: <strong>{rate}</strong></p>
    <p>Fee: <strong>{fee} {fee_coin}</strong></p>
    <p>Transaction ID: <code>{tx_id}</code></p>
    <hr style="border: none; border-top: 1px solid rgba(29, 209, 161, 0.2); margin: 20px 0;">
    <p style="font-size: 12px; color: #b8c5d6;">MetaMonetize &copy; 2026. All rights reserved.</p>
  </div>'
),
(
  'transaction_alert',
  'Transaction Alert',
  'New transaction: {type}',
  'Sent for suspicious or large transactions',
  '<div style="font-family: Arial, sans-serif; background: linear-gradient(135deg, #020c2b 0%, #0f1429 100%); padding: 40px; border-radius: 12px; color: #fff;">
    <h2 style="color: #1dd1a1;">Transaction Alert</h2>
    <p>We detected a new transaction on your account.</p>
    <p>Type: <strong>{type}</strong></p>
    <p>Amount: <strong>{amount} {coin}</strong></p>
    <p>Time: <strong>{timestamp}</strong></p>
    <p style="margin-top: 20px; padding: 16px; background: rgba(29, 209, 161, 0.1); border-left: 3px solid #1dd1a1;">
      If this wasn''t you, please secure your account immediately.
    </p>
    <hr style="border: none; border-top: 1px solid rgba(29, 209, 161, 0.2); margin: 20px 0;">
    <p style="font-size: 12px; color: #b8c5d6;">MetaMonetize &copy; 2026. All rights reserved.</p>
  </div>'
);

-- WordPress Options for Akpa ison Settings
INSERT INTO wp_options (option_name, option_value) VALUES
('akpa_ison_sendgrid_key', ''),
('akpa_ison_from_email', ''),
('akpa_ison_from_name', 'MetaMonetize')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);

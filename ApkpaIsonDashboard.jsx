import React, { useState, useEffect } from 'react';
import './AkpaIsonDashboard.css';

/**
 * Akpa ison - Email Dashboard
 * React component for MetaMonetize email management
 */

const AkpaIsonDashboard = () => {
  const [tab, setTab] = useState('send');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  // Send email state
  const [sendForm, setSendForm] = useState({
    to: '',
    template: 'deposit_confirmed',
    data: {}
  });

  // History state
  const [history, setHistory] = useState([]);
  const [settings, setSettings] = useState({
    sendgrid_key: '',
    from_email: '',
    from_name: ''
  });

  const [templates, setTemplates] = useState([]);

  // Fetch templates on mount
  useEffect(() => {
    fetchTemplates();
    if (tab === 'history') fetchHistory();
    if (tab === 'settings') fetchSettings();
  }, [tab]);

  // Fetch email templates
  const fetchTemplates = async () => {
    try {
      const response = await fetch(
        `/wp-json/akpa-ison/v1/templates`,
        {
          headers: {
            'X-WP-Nonce': window.wpAkpaIson?.nonce || ''
          }
        }
      );
      const data = await response.json();
      setTemplates(data);
    } catch (error) {
      console.error('Failed to fetch templates:', error);
    }
  };

  // Fetch email history
  const fetchHistory = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `/wp-json/akpa-ison/v1/history?limit=50`,
        {
          headers: {
            'X-WP-Nonce': window.wpAkpaIson?.nonce || ''
          }
        }
      );
      const data = await response.json();
      setHistory(data);
    } catch (error) {
      console.error('Failed to fetch history:', error);
      setMessage('Failed to load email history');
    } finally {
      setLoading(false);
    }
  };

  // Fetch settings
  const fetchSettings = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `/wp-json/akpa-ison/v1/settings`,
        {
          headers: {
            'X-WP-Nonce': window.wpAkpaIson?.nonce || ''
          }
        }
      );
      const data = await response.json();
      setSettings(data);
    } catch (error) {
      console.error('Failed to fetch settings:', error);
      setMessage('Failed to load settings');
    } finally {
      setLoading(false);
    }
  };

  // Send email
  const handleSendEmail = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage('');

    try {
      const response = await fetch(
        `/wp-json/akpa-ison/v1/send-email`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.wpAkpaIson?.nonce || ''
          },
          body: JSON.stringify(sendForm)
        }
      );

      const data = await response.json();

      if (data.success) {
        setMessage(`✓ Email sent to ${sendForm.to}`);
        setSendForm({ to: '', template: 'deposit_confirmed', data: {} });
      } else {
        setMessage(`✗ Error: ${data.error}`);
      }
    } catch (error) {
      console.error('Failed to send email:', error);
      setMessage('Failed to send email');
    } finally {
      setLoading(false);
    }
  };

  // Update settings
  const handleUpdateSettings = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage('');

    try {
      const response = await fetch(
        `/wp-json/akpa-ison/v1/settings`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.wpAkpaIson?.nonce || ''
          },
          body: JSON.stringify(settings)
        }
      );

      const data = await response.json();

      if (data.success) {
        setMessage('✓ Settings updated');
      } else {
        setMessage('✗ Failed to update settings');
      }
    } catch (error) {
      console.error('Failed to update settings:', error);
      setMessage('Failed to update settings');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="akpa-ison-dashboard">
      {/* Header */}
      <div className="akpa-header">
        <div className="akpa-logo">
          <svg width="32" height="32" viewBox="0 0 100 100" fill="none">
            <rect x="10" y="10" width="80" height="80" rx="8" fill="#020c2b" stroke="#1dd1a1" strokeWidth="2"/>
            <path d="M30 40 L50 60 L70 40" stroke="#1dd1a1" strokeWidth="3" fill="none" strokeLinecap="round"/>
            <circle cx="50" cy="75" r="3" fill="#1dd1a1"/>
          </svg>
          <h1>Akpa ison</h1>
        </div>
        <p className="akpa-tagline">MetaMonetize Email Manager</p>
      </div>

      {/* Tabs */}
      <div className="akpa-tabs">
        <button
          className={`akpa-tab ${tab === 'send' ? 'active' : ''}`}
          onClick={() => setTab('send')}
        >
          📧 Send Email
        </button>
        <button
          className={`akpa-tab ${tab === 'history' ? 'active' : ''}`}
          onClick={() => setTab('history')}
        >
          📋 History
        </button>
        <button
          className={`akpa-tab ${tab === 'settings' ? 'active' : ''}`}
          onClick={() => setTab('settings')}
        >
          ⚙️ Settings
        </button>
      </div>

      {/* Message */}
      {message && (
        <div className={`akpa-message ${message.includes('✓') ? 'success' : 'error'}`}>
          {message}
        </div>
      )}

      {/* Send Email Tab */}
      {tab === 'send' && (
        <div className="akpa-panel">
          <h2>Send Email</h2>
          <form onSubmit={handleSendEmail} className="akpa-form">
            {/* Email To */}
            <div className="akpa-form-group">
              <label>Send To</label>
              <input
                type="email"
                placeholder="user@example.com"
                value={sendForm.to}
                onChange={(e) => setSendForm({ ...sendForm, to: e.target.value })}
                required
              />
            </div>

            {/* Template Select */}
            <div className="akpa-form-group">
              <label>Email Template</label>
              <select
                value={sendForm.template}
                onChange={(e) => setSendForm({ ...sendForm, template: e.target.value })}
              >
                {Object.entries(templates).map(([key, template]) => (
                  <option key={key} value={key}>
                    {template.name} - {template.description}
                  </option>
                ))}
              </select>
            </div>

            {/* Template Variables */}
            {sendForm.template && templates[sendForm.template] && (
              <div className="akpa-variables">
                <p className="akpa-label">Template Variables</p>
                <div className="akpa-variable-inputs">
                  {[
                    { key: 'amount', label: 'Amount' },
                    { key: 'coin', label: 'Coin Symbol' },
                    { key: 'tx_id', label: 'Transaction ID' },
                    { key: 'new_balance', label: 'New Balance' },
                    { key: 'address', label: 'Wallet Address' },
                    { key: 'status', label: 'Status' },
                    { key: 'from_amount', label: 'From Amount' },
                    { key: 'from_coin', label: 'From Coin' },
                    { key: 'to_amount', label: 'To Amount' },
                    { key: 'to_coin', label: 'To Coin' },
                    { key: 'rate', label: 'Exchange Rate' },
                    { key: 'fee', label: 'Fee' },
                    { key: 'fee_coin', label: 'Fee Coin' }
                  ].map(variable => (
                    <div key={variable.key} className="akpa-variable-input">
                      <label>{variable.label}</label>
                      <input
                        type="text"
                        placeholder={variable.label}
                        value={sendForm.data[variable.key] || ''}
                        onChange={(e) =>
                          setSendForm({
                            ...sendForm,
                            data: { ...sendForm.data, [variable.key]: e.target.value }
                          })
                        }
                      />
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Submit */}
            <button type="submit" className="akpa-btn-primary" disabled={loading}>
              {loading ? 'Sending...' : 'Send Email'}
            </button>
          </form>
        </div>
      )}

      {/* History Tab */}
      {tab === 'history' && (
        <div className="akpa-panel">
          <h2>Email History</h2>
          {loading ? (
            <p>Loading...</p>
          ) : history.length === 0 ? (
            <p className="akpa-empty">No emails sent yet</p>
          ) : (
            <div className="akpa-history-table">
              <div className="akpa-table-header">
                <div className="col-email">To</div>
                <div className="col-template">Template</div>
                <div className="col-status">Status</div>
                <div className="col-date">Date</div>
              </div>
              {history.map((email, idx) => (
                <div key={idx} className="akpa-table-row">
                  <div className="col-email">{email.to_email}</div>
                  <div className="col-template">{email.template}</div>
                  <div className="col-status">
                    <span className={`akpa-status akpa-status-${email.status}`}>
                      {email.status}
                    </span>
                  </div>
                  <div className="col-date">
                    {new Date(email.created_at).toLocaleDateString()}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Settings Tab */}
      {tab === 'settings' && (
        <div className="akpa-panel">
          <h2>Settings</h2>
          <form onSubmit={handleUpdateSettings} className="akpa-form">
            <div className="akpa-form-group">
              <label>SendGrid API Key</label>
              <input
                type="password"
                placeholder="SG.xxxxxxxxxxxx"
                value={settings.sendgrid_key}
                onChange={(e) => setSettings({ ...settings, sendgrid_key: e.target.value })}
              />
              <small>Get your key at sendgrid.com</small>
            </div>

            <div className="akpa-form-group">
              <label>From Email</label>
              <input
                type="email"
                placeholder="noreply@metamonetize.com"
                value={settings.from_email}
                onChange={(e) => setSettings({ ...settings, from_email: e.target.value })}
              />
            </div>

            <div className="akpa-form-group">
              <label>From Name</label>
              <input
                type="text"
                placeholder="MetaMonetize"
                value={settings.from_name}
                onChange={(e) => setSettings({ ...settings, from_name: e.target.value })}
              />
            </div>

            <button type="submit" className="akpa-btn-primary" disabled={loading}>
              {loading ? 'Saving...' : 'Save Settings'}
            </button>
          </form>
        </div>
      )}
    </div>
  );
};

export default AkpaIsonDashboard;

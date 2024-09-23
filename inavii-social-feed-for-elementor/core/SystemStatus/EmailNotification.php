<?php

namespace Inavii\Instagram\SystemStatus;

use Inavii\Instagram\Wp\AppGlobalSettings;

class EmailNotification
{
    private $settings_link;
    private $website_link;
    private $disable_emails_link;
    private $globalSettings;

    public function __construct()
    {
        $this->settings_link = $this->settingsPageLink(). '#/accounts';
        $this->website_link = home_url();
        $this->disable_emails_link = $this->settingsPageLink() . '#/global-settings';
        $this->globalSettings = new AppGlobalSettings();
    }

    public function sendEmail($subject = 'Instagram Feed Issue on Your Site')
    {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $message = $this->getEmailTemplate();

        if (!$this->globalSettings->getEmailNotifications()) {
            return;
        }

        wp_mail($this->globalSettings->getEmail(), $subject, $message, $headers);
    }

    private function settingsPageLink(): string
    {
        return add_query_arg(
            array(
                'page' => 'inavii-instagram-settings',
            ),
            esc_url(admin_url('admin.php'))
        );
    }

    private function getEmailTemplate(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 16px;
            color: #333;
        }
        .container {
            width: 600px;
            max-width: 100%;
            margin: 0 auto;
            background-color: #f4f4f4;
        }
        .container-box {
            padding: 20px;
        }
        .logo-container {
            text-align: center;
            padding: 20px;
        }
        .header {
            background-color: #7A3CFF;
            color: #ffffff;
            padding: 10px;
            text-align: center;
        }
        .content {
            padding: 20px;
            text-align: left;
            border: 1px solid #ddd;
            background: #ffffff;
        }
        .footer {
            padding: 10px;
            background-color: #eee;
            text-align: center;
            font-size: 14px;
        }
        .footer a {
            color: #7A3CFF;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="container-box">
        <div class="logo-container">
            <img src="https://www.inavii.com/social-feed/inavii-logo-mail.png" alt="Logo Inavii" style="max-width: 100%; width:192px; height: 57px;">
        </div>
        <div class="header">
            <h3>There\'s an Issue with an Instagram Feed on Your Website</h3>
        </div>
        <div class="content">
            <p>An Instagram feed on your website is currently unable to connect to Instagram to retrieve new posts. Don\'t worry, your feed is still being displayed using a cached version, but is no longer able to display new posts.</p>
            <p>Reconnect your Instagram account because your access token has expired. Please go to the <a href="' . $this->settings_link . '" style="color: #7A3CFF;"><strong>Inavii Social feed » IG Accounts</strong></a> on your website and <strong>Reconnect Account</strong></p>
        </div>
        <div class="footer">
            This is an email sent from the Inavii Social Feed plugin on your website to alert you when there is an issue with one of your Instagram feeds.<br>
            Sent from <a href="' . $this->website_link . '">Your Website</a> | <a href="' . $this->disable_emails_link . '">Log in and disable these emails</a>
        </div>
    </div>
</div>
</body>
</html>';
    }
}

<?php
// lib/notifier.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function notifier_send_email(string $to, string $subject, string $htmlBody, array $opts = []): bool {
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n";
    $headers .= "From: no-reply@example.com\r\n";
    return mail($to, $subject, $htmlBody, $headers);
}

function notifier_onboarding_status_changed(array $provider, string $status) {
    $email = $provider['email'] ?? null;
    if (!$email) return false;
    $subject = "Your provider verification status: " . ucfirst($status);
    $body = "<p>Hello " . htmlspecialchars($provider['name'], ENT_QUOTES) . ",</p>";
    if ($status === 'pending') {
        $body .= "<p>Thanks for submitting your documents. Our team will review them shortly.</p>";
    } elseif ($status === 'verified') {
        $body .= "<p>Your provider profile has been verified. Congratulations!</p>";
    } elseif ($status === 'rejected') {
        $body .= "<p>Unfortunately your verification was rejected. Please check the admin note and resubmit.</p>";
    }
    $body .= "<p>— The Team</p>";
    return notifier_send_email($email, $subject, $body);
}

function notifier_send_invite_email(string $toEmail, string $inviteToken, array $opts = []) {
    // Build accept URL. Adjust domain as needed.
    $domain = $opts['domain'] ?? ($_SERVER['HTTP_HOST'] ?? 'example.com');
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $acceptUrl = $protocol . '://' . $domain . '/invite/accept.php?token=' . urlencode($inviteToken);

    $subject = $opts['subject'] ?? 'You have been invited';
    $body = "<p>Hello,</p>";
    $body .= "<p>An admin has invited you to join. Click the link below to accept the invitation and set your password. The link expires in 7 days.</p>";
    $body .= "<p><a href=\"" . htmlspecialchars($acceptUrl, ENT_QUOTES) . "\">Accept invitation</a></p>";
    $body .= "<p>If you did not expect this, ignore this email.</p>";
    $body .= "<p>— The Team</p>";

    return notifier_send_email($toEmail, $subject, $body);
}

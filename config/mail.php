<?php
declare (strict_types = 1);

use App\Core\Config;

$adminRecipients = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) Config::get('MAIL_ADMIN_TO', '')),
), static fn(string $email): bool => $email !== ''));

return [
    'enabled'          => Config::get('MAIL_ENABLED', '0') === '1',
    'host'             => trim((string) Config::get('MAIL_HOST', '')),
    'port'             => (int) Config::get('MAIL_PORT', 587),
    'smtp_auth'        => Config::get('MAIL_SMTP_AUTH', '1') !== '0',
    'username'         => trim((string) Config::get('MAIL_USERNAME', '')),
    'password'         => (string) Config::get('MAIL_PASSWORD', ''),
    'encryption'       => strtolower(trim((string) Config::get('MAIL_ENCRYPTION', 'tls'))),
    'timeout'          => (int) Config::get('MAIL_TIMEOUT', 15),
    'from_address'     => trim((string) Config::get('MAIL_FROM_ADDRESS', '')),
    'from_name'        => trim((string) Config::get('MAIL_FROM_NAME', Config::get('APP_NAME', 'Traiteur Passion'))),
    'admin_recipients' => $adminRecipients,
    'notify_admin'     => Config::get('MAIL_NOTIFY_ADMIN', '1') !== '0',
    'ack_client'       => Config::get('MAIL_ACK_CLIENT', '1') !== '0',
];

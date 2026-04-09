<?php
declare (strict_types = 1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    /** @var array<string,mixed> */
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require dirname(__DIR__, 2) . '/config/mail.php';
    }

    public function isEnabled(): bool
    {
        return ($this->config['enabled'] ?? false) === true;
    }

    /**
     * @param list<array{email:string,name?:string}> $recipients
     * @param array{email:string,name?:string}|null $replyTo
     */
    public function send(array $recipients, string $subject, string $htmlBody, string $textBody, ?array $replyTo = null): void
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('Le service mail est désactivé.');
        }

        $this->assertConfigured();

        $mail          = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = max(1, (int) ($this->config['timeout'] ?? 15));
        $mail->isSMTP();
        $mail->Host     = (string) $this->config['host'];
        $mail->Port     = (int) ($this->config['port'] ?? 587);
        $mail->SMTPAuth = ($this->config['smtp_auth'] ?? true) === true;

        if ($mail->SMTPAuth) {
            $mail->Username = (string) ($this->config['username'] ?? '');
            $mail->Password = (string) ($this->config['password'] ?? '');
        }

        $encryption = $this->normalizeEncryption((string) ($this->config['encryption'] ?? ''));
        if ($encryption !== '') {
            $mail->SMTPSecure = $encryption;
        }

        $mail->setFrom(
            (string) $this->config['from_address'],
            (string) ($this->config['from_name'] ?? 'Traiteur Passion'),
        );

        foreach ($recipients as $recipient) {
            $email = trim((string) ($recipient['email'] ?? ''));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $mail->addAddress($email, trim((string) ($recipient['name'] ?? '')));
        }

        if ($mail->getToAddresses() === []) {
            throw new \RuntimeException('Aucun destinataire e-mail valide.');
        }

        if ($replyTo !== null) {
            $replyToEmail = trim((string) ($replyTo['email'] ?? ''));
            if (filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyToEmail, trim((string) ($replyTo['name'] ?? '')));
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;
        $mail->send();
    }

    private function assertConfigured(): void
    {
        $required = [
            'host'         => (string) ($this->config['host'] ?? ''),
            'from_address' => (string) ($this->config['from_address'] ?? ''),
        ];

        foreach ($required as $key => $value) {
            if (trim($value) === '') {
                throw new \RuntimeException(sprintf('Configuration mail incomplète: %s manquant.', $key));
            }
        }

        if (! filter_var((string) $this->config['from_address'], FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Configuration mail invalide: MAIL_FROM_ADDRESS.');
        }

        if (($this->config['smtp_auth'] ?? true) === true) {
            $username = trim((string) ($this->config['username'] ?? ''));
            $password = (string) ($this->config['password'] ?? '');

            if ($username === '' || $password === '') {
                throw new \RuntimeException('Configuration mail incomplète: identifiants SMTP manquants.');
            }
        }
    }

    private function normalizeEncryption(string $value): string
    {
        $value = strtolower(trim($value));

        if ($value === 'ssl' || $value === 'smtps') {
            return PHPMailer::ENCRYPTION_SMTPS;
        }

        if ($value === 'tls' || $value === 'starttls') {
            return PHPMailer::ENCRYPTION_STARTTLS;
        }

        return '';
    }
}

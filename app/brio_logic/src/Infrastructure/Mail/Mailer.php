<?php
declare(strict_types=1);

namespace Brio\Infrastructure\Mail;

use Brio\Infrastructure\Logging\Logger;

/**
 * Sends email. Uses your mail server (SMTP) when one is saved in Settings; otherwise falls back to PHP's mail(),
 * which many hosts allow but which is more often marked as spam.
 */
final class Mailer
{
    /** @param \Closure():array $config host, port, secure (ssl|tls|none), user, pass, from, fromName */
    public function __construct(private readonly \Closure $config, private readonly string $siteHost = 'localhost') {}

    public function send(string $to, string $subject, string $html, string $text): bool
    {
        $c = ($this->config)();
        $to = $this->clean($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
        $from = filter_var($c['from'] ?? '', FILTER_VALIDATE_EMAIL) ? (string)$c['from'] : 'no-reply@' . preg_replace('/^www\./', '', $this->siteHost);
        $fromName = $this->clean((string)($c['fromName'] ?? ''));

        $boundary = 'b' . bin2hex(random_bytes(12));
        $headers = [
            'Date: ' . date('r'),
            'From: ' . ($fromName !== '' ? $this->encode($fromName) . " <$from>" : $from),
            'To: ' . $to,
            'Subject: ' . $this->encode($this->clean($subject)),
            'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . preg_replace('/[^a-z0-9.-]/i', '', $this->siteHost) . '>',
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
        ];
        $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
              . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
              . "--$boundary--\r\n";

        try {
            if (($c['host'] ?? '') !== '') return $this->smtp($c, $from, $to, $headers, $body);
            // mail() adds To and Subject itself
            $send = array_values(array_filter($headers, static fn($l) => !str_starts_with($l, 'Subject:') && !str_starts_with($l, 'To:')));
            return @mail($to, $this->encode($this->clean($subject)), $body, implode("\r\n", $send));
        } catch (\Throwable $e) {
            Logger::error('Email could not be sent', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** Like send() but reports why it failed (used by the "send a test email" button). */
    public function test(string $to): string
    {
        $c = ($this->config)();
        try {
            $ok = ($c['host'] ?? '') !== '' ? $this->smtpOrThrow($c, $to) : $this->send($to, 'Test email', '<p>Your store can send email.</p>', 'Your store can send email.');
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
        return $ok ? '' : 'The mail server did not accept the message.';
    }

    private function smtpOrThrow(array $c, string $to): bool
    {
        $from = filter_var($c['from'] ?? '', FILTER_VALIDATE_EMAIL) ? (string)$c['from'] : 'no-reply@' . $this->siteHost;
        $boundary = 'b' . bin2hex(random_bytes(8));
        $headers = ['Date: ' . date('r'), 'From: ' . $from, 'To: ' . $to, 'Subject: Test email', 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
        $this->smtp($c, $from, $to, $headers, "Your store can send email.\r\n", true);
        return true;
    }

    private function smtp(array $c, string $from, string $to, array $headers, string $body, bool $throw = false): bool
    {
        $secure = (string)($c['secure'] ?? 'ssl');
        $port = (int)($c['port'] ?: ($secure === 'ssl' ? 465 : 587));
        $host = (string)$c['host'];
        $errno = 0; $err = '';
        $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $err, 10);
        if (!$fp) return $this->fail("Could not connect to $host:$port ($err)", $throw);
        stream_set_timeout($fp, 12);
        try {
            $this->expect($fp, [220]);
            $name = preg_replace('/[^a-z0-9.-]/i', '', $this->siteHost) ?: 'localhost';
            $this->cmd($fp, "EHLO $name", [250]);
            if ($secure === 'tls') {
                $this->cmd($fp, 'STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new \RuntimeException('Could not start a secure (TLS) connection.');
                $this->cmd($fp, "EHLO $name", [250]);
            }
            if (($c['user'] ?? '') !== '') {
                $this->cmd($fp, 'AUTH LOGIN', [334]);
                $this->cmd($fp, base64_encode((string)$c['user']), [334]);
                $this->cmd($fp, base64_encode((string)$c['pass']), [235]);
            }
            $this->cmd($fp, "MAIL FROM:<$from>", [250]);
            $this->cmd($fp, "RCPT TO:<$to>", [250, 251]);
            $this->cmd($fp, 'DATA', [354]);
            $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
            $data = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $data));
            fwrite($fp, str_replace("\n", "\r\n", $data) . "\r\n.\r\n");
            $this->expect($fp, [250]);
            @fwrite($fp, "QUIT\r\n");
            fclose($fp);
            return true;
        } catch (\Throwable $e) {
            @fclose($fp);
            return $this->fail($e->getMessage(), $throw);
        }
    }

    private function fail(string $msg, bool $throw): bool
    {
        Logger::error('Email could not be sent', ['error' => $msg]);
        if ($throw) throw new \RuntimeException($msg);
        return false;
    }

    private function cmd($fp, string $line, array $ok): string
    {
        fwrite($fp, $line . "\r\n");
        return $this->expect($fp, $ok);
    }

    /** Reads one (possibly multi-line) SMTP reply and checks its status code. */
    private function expect($fp, array $ok): string
    {
        $reply = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $reply .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        $code = (int)substr($reply, 0, 3);
        if (!in_array($code, $ok, true)) throw new \RuntimeException('Mail server said: ' . trim(substr($reply, 0, 200)));
        return $reply;
    }

    private function clean(string $s): string { return trim(str_replace(["\r", "\n", "\0"], ' ', $s)); }

    private function encode(string $s): string { return preg_match('/[^\x20-\x7e]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s; }
}

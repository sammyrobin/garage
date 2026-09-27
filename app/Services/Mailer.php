<?php

declare(strict_types=1);

namespace Garage\Services;

use Garage\Core\Config;
use Garage\Core\Logger;

/**
 * Minimal SMTP client over PHP sockets (no Composer), ported from the portfolio's
 * mail.php: EHLO → STARTTLS/SSL → AUTH LOGIN → MAIL/RCPT → DATA → QUIT.
 * Sends multipart/alternative (text + HTML). The password is never logged.
 */
final class Mailer
{
    public function __construct(private readonly array $cfg)
    {
    }

    public static function fromConfig(): self
    {
        return new self((array) Config::get('mail', []));
    }

    public function send(string $to, string $subject, string $html, string $text): bool
    {
        if (empty($this->cfg['host']) || empty($this->cfg['from_email'])) {
            Logger::warning('Mail not configured; message skipped', ['subject' => $subject]);
            return false;
        }

        $from = (string) $this->cfg['from_email'];
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $boundary = '=_garage_' . bin2hex(random_bytes(12));

        $headers = 'Date: ' . date('r') . "\r\n"
            . 'From: ' . self::encodeHeader((string) ($this->cfg['from_name'] ?? 'GARAGE')) . " <{$from}>\r\n"
            . "To: <{$to}>\r\n"
            . 'Subject: ' . self::encodeHeader($subject) . "\r\n"
            . 'Message-ID: <' . bin2hex(random_bytes(16)) . "@{$domain}>\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

        $body = "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text))
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--{$boundary}--\r\n";

        return $this->smtp($to, $headers, $body);
    }

    private function smtp(string $to, string $headers, string $body): bool
    {
        $secure = (string) ($this->cfg['secure'] ?? 'tls');
        $verify = (bool) ($this->cfg['verify_tls'] ?? true);
        $context = stream_context_create(['ssl' => [
            'verify_peer' => $verify,
            'verify_peer_name' => $verify,
            'allow_self_signed' => !$verify,
        ]]);
        $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $this->cfg['host'] . ':' . (int) $this->cfg['port'];

        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$fp) {
            Logger::error('SMTP connect failed', ['remote' => $remote, 'error' => "{$errstr} ({$errno})"]);
            return false;
        }
        stream_set_timeout($fp, 15);

        $read = static function () use ($fp): string {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (strlen($line) < 4 || $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $step = static function (?string $cmd, array $codes, string $label) use ($fp, $read): bool {
            if ($cmd !== null) {
                fwrite($fp, $cmd . "\r\n");
            }
            $reply = $read();
            if (!in_array((int) substr($reply, 0, 3), $codes, true)) {
                Logger::error('SMTP step failed', ['step' => $label, 'reply' => trim($reply)]);
                return false;
            }
            return true;
        };

        $ehlo = 'EHLO ' . (parse_url((string) Config::get('app.url'), PHP_URL_HOST) ?: 'localhost');
        $ok = $step(null, [220], 'greeting') && $step($ehlo, [250], 'EHLO');

        if ($ok && $secure === 'tls') {
            $ok = $step('STARTTLS', [220], 'STARTTLS')
                && stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
                && $step($ehlo, [250], 'EHLO after STARTTLS');
        }

        if ($ok && !empty($this->cfg['username'])) {
            $ok = $step('AUTH LOGIN', [334], 'AUTH LOGIN')
                && $step(base64_encode((string) $this->cfg['username']), [334], 'AUTH user')
                && $step(base64_encode((string) ($this->cfg['password'] ?? '')), [235], 'AUTH password');
        }

        $ok = $ok
            && $step('MAIL FROM:<' . $this->cfg['from_email'] . '>', [250], 'MAIL FROM')
            && $step("RCPT TO:<{$to}>", [250, 251], 'RCPT TO')
            && $step('DATA', [354], 'DATA');

        if ($ok) {
            $data = $headers . "\r\n" . preg_replace('/\r\n|\r|\n/', "\r\n", $body);
            $data = preg_replace('/^\./m', '..', $data);
            $ok = $step($data . "\r\n.", [250], 'message body');
        }

        @fwrite($fp, "QUIT\r\n");
        fclose($fp);

        return $ok;
    }

    /** RFC 2047 encoded-words (UTF-8, base64), chunked without splitting characters. */
    public static function encodeHeader(string $text): string
    {
        if (preg_match('/^[A-Za-z0-9 .,:()!?\'-]*$/', $text)) {
            return $text;
        }
        preg_match_all('/./us', $text, $m);
        $chunks = [];
        $current = '';
        foreach ($m[0] as $char) {
            if (strlen($current) + strlen($char) > 45) {
                $chunks[] = $current;
                $current = '';
            }
            $current .= $char;
        }
        $chunks[] = $current;

        return implode("\r\n ", array_map(static fn (string $c): string => '=?UTF-8?B?' . base64_encode($c) . '?=', $chunks));
    }
}

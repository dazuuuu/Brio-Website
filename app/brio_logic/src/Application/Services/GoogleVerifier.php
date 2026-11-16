<?php
declare(strict_types=1);

namespace Brio\Application\Services;

use Brio\Domain\Exceptions\AuthException;
use Brio\Infrastructure\Logging\Logger;

/**
 * Checks a "Sign in with Google" ID token by asking Google to validate it (the tokeninfo endpoint), then confirms the token
 * was issued for THIS store's Client ID, hasn't expired, and carries a verified email address.
 */
final class GoogleVerifier
{
    /** @param (\Closure(string):?array)|null $http returns the decoded JSON for a URL (tests plug a fake in here) */
    public function __construct(
        private readonly string $tokenInfoUrl = 'https://oauth2.googleapis.com/tokeninfo',
        private readonly ?\Closure $http = null,
    ) {}

    /** @return array{sub:string,email:string,first:string,last:string} */
    public function verify(string $idToken, string $clientId): array
    {
        if ($clientId === '') throw new AuthException('Google sign-in is not set up on this store.');
        // A JWT is three base64url parts: reject anything else before it goes into a URL.
        if (!preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $idToken) || strlen($idToken) > 4096) {
            throw new AuthException('Google sign-in failed. Please try again.');
        }
        $info = ($this->http ?? $this->fetch(...))($this->tokenInfoUrl . '?id_token=' . rawurlencode($idToken));
        if (!is_array($info)) throw new AuthException('We could not reach Google. Please try again.');

        $okAudience = ($info['aud'] ?? null) === $clientId;
        $okIssuer = in_array($info['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
        $okTime = (int)($info['exp'] ?? 0) > time();
        $okEmail = filter_var($info['email'] ?? '', FILTER_VALIDATE_EMAIL) && filter_var($info['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $sub = (string)($info['sub'] ?? '');
        if (!$okAudience || !$okIssuer || !$okTime || !$okEmail || !preg_match('/^[0-9]{5,40}$/', $sub)) {
            Logger::warning('Google token rejected', ['audience' => $okAudience, 'issuer' => $okIssuer, 'fresh' => $okTime, 'email' => (bool)$okEmail]);
            throw new AuthException('Google sign-in failed. Please try again.');
        }
        $email = strtolower((string)$info['email']);
        $first = trim((string)($info['given_name'] ?? ''));
        return ['sub' => $sub, 'email' => $email, 'first' => $first !== '' ? $first : ucfirst(strstr($email, '@', true) ?: 'Customer'), 'last' => trim((string)($info['family_name'] ?? ''))];
    }

    /** @return array<string,mixed>|null */
    private function fetch(string $url): ?array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_SSL_VERIFYPEER => true]);
            $body = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $status !== 200) return null;
        } else {
            $body = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 6, 'ignore_errors' => false]]));
            if ($body === false) return null;
        }
        $json = json_decode((string)$body, true);
        return is_array($json) ? $json : null;
    }
}

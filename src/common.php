<?php
declare(strict_types=1);

/*
 * Shared helpers for session handling, CSRF protection, server URL validation,
 * output escaping and the ChurchTools API session.
 */

use \CTApi\CTClient;
use \CTApi\CTConfig;
use \CTApi\CTLog;
use \GuzzleHttp\Cookie\SetCookie;
use \PhpOffice\PhpSpreadsheet\Cell\DataType;

// The API client logs to a file inside vendor/ by default, which is inside the web root
CTLog::enableFileLog(false);
// A redirect could lead the server side requests to an internal host
CTClient::setClient(new CTClient(['allow_redirects' => false]));

/**
 * Error the user can correct; its message is safe to show and the session is kept
 */
class UserInputException extends Exception {}

/**
 * Start the PHP session with hardened cookie settings
 */
function ctStartSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => ctIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function ctIsHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/**
 * End the session and remove the session cookie
 */
function ctLogout(): void
{
    ctStartSession();
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'],
    ]);
    session_destroy();
}

/**
 * CSRF token of the current session, created on first use
 */
function ctCsrfToken(): string
{
    if (empty($_SESSION['csrfToken'])) {
        $_SESSION['csrfToken'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrfToken'];
}

/**
 * Reject a POST that does not carry the CSRF token of this session
 */
function ctCheckCsrf(): void
{
    $token = $_POST['csrfToken'] ?? '';
    if (empty($_SESSION['csrfToken']) || !is_string($token) || !hash_equals($_SESSION['csrfToken'], $token)) {
        throw new UserInputException('Das Formular ist abgelaufen. Bitte neu anmelden.');
    }
}

/**
 * Server URL from config.php, or null when the user may enter one
 */
function ctConfiguredServerURL(): ?string
{
    if (!file_exists(__DIR__.'/config.php')) {
        return null;
    }
    $configs = include(__DIR__.'/config.php');
    $serverURL = is_array($configs) ? ($configs['serverURL'] ?? null) : null;
    return (is_string($serverURL) && $serverURL !== '') ? $serverURL : null;
}

/**
 * Server the login goes to. A server from config.php always wins; a user entered
 * one must be a public host name, so the application cannot be used to reach
 * hosts in the internal network.
 */
function ctResolveServerURL(?string $userInput): string
{
    $configured = ctConfiguredServerURL();
    if ($configured !== null) {
        return $configured;
    }

    $host = strtolower(trim((string)$userInput));
    $host = preg_replace('#^https?://#', '', $host);
    $host = rtrim($host, '/');
    // Host name only: no port, path, user info or IP literal
    if (!preg_match('/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $host)) {
        throw new UserInputException('Ungültige Server URL. Bitte nur den Hostnamen angeben, z.B. meinegemeinde.church.tools');
    }
    $addresses = gethostbynamel($host) ?: [];
    foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
        $addresses[] = $record['ipv6'];
    }
    if (empty($addresses)) {
        throw new UserInputException('Der Server '.$host.' wurde nicht gefunden.');
    }
    foreach ($addresses as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new UserInputException('Der Server '.$host.' ist nicht erlaubt.');
        }
    }
    // Connect to the checked address, so a changed DNS answer cannot point elsewhere
    if (defined('CURLOPT_RESOLVE')) {
        $pinnedIp = str_contains($addresses[0], ':') ? '['.$addresses[0].']' : $addresses[0];
        CTClient::setClient(new CTClient([
            'allow_redirects' => false,
            'curl' => [CURLOPT_RESOLVE => [$host.':443:'.$pinnedIp]],
        ]));
    }
    return $host;
}

/**
 * Remember the ChurchTools session cookies instead of the password
 */
function ctSaveApiSession(string $serverURL): void
{
    $_SESSION['serverURL'] = $serverURL;
    $_SESSION['ctCookies'] = CTConfig::getRequestConfig()['cookies']->toArray();
}

/**
 * Restore the ChurchTools session saved by ctSaveApiSession()
 */
function ctRestoreApiSession(): void
{
    if (empty($_SESSION['serverURL']) || empty($_SESSION['ctCookies'])) {
        throw new UserInputException('Nicht angemeldet. Bitte neu anmelden.');
    }
    CTConfig::setApiUrl('https://'.ctResolveServerURL($_SESSION['serverURL']));
    $cookieJar = CTConfig::getRequestConfig()['cookies'];
    foreach ($_SESSION['ctCookies'] as $cookieData) {
        $cookieJar->setCookie(new SetCookie($cookieData));
    }
    if (!CTConfig::validateAuthentication()) {
        throw new UserInputException('Die ChurchTools Sitzung ist abgelaufen. Bitte neu anmelden.');
    }
}

/**
 * Escape for HTML output
 */
function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Only accept #rrggbb colors, anything else becomes the fallback
 */
function ctSafeColor(mixed $color, string $fallback = '#ffffff'): string
{
    return (is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) ? $color : $fallback;
}

function getContrastColor(mixed $hexcolor): string
{
    $hexcolor = ctSafeColor($hexcolor);
    $r = hexdec(substr($hexcolor, 1, 2));
    $g = hexdec(substr($hexcolor, 3, 2));
    $b = hexdec(substr($hexcolor, 5, 2));
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    return ($yiq >= 128) ? 'black' : 'white';
}

/**
 * Write a text cell; unlike setCellValue() a leading "=" does not create a formula
 */
function ctSetText($sheet, string $coordinate, mixed $value): void
{
    $sheet->setCellValueExplicit($coordinate, (string)($value ?? ''), DataType::TYPE_STRING);
}

/**
 * Only http(s) URLs become hyperlinks in the spreadsheet
 */
function ctIsWebUrl(mixed $url): bool
{
    return is_string($url) && preg_match('#^https?://#i', $url) === 1;
}

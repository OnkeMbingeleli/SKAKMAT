<?php
/**
 * Skakmat i18n helper — DeepL API-driven translation.
 *
 * UI text is defined in lang/strings.php. t() resolves the current language
 * from the skakmat_lang cookie, reads/writes disk cache entries, and uses
 * fallback_translations.php if DeepL is unavailable or lacks language support.
 *
 * Setup: put your key in frontend/.env as DEEPL_API_KEY=...
 * (see frontend/.env.example). Get a free key at
 * https://www.deepl.com/pro-api — no credit card required for the
 * Free tier (500,000 characters/month).
 */
require_once __DIR__ . '/../lang/strings.php';
require_once __DIR__ . '/../lang/fallback_translations.php';
/**
 * The target-language code DeepL's API uses for each language. null means
 * DeepL doesn't support this language — t() uses the fallback dictionary.
 */
function skakmat_language_codes(): array
{
    return [
        'English'    => null,
        'Afrikaans'  => 'AF',
        'isiXhosa'   => 'XH',
        'isiZulu'    => 'ZU',
        'Sepedi'     => null,
        'Sesotho'    => 'ST',
        'Setswana'   => 'TN',
        'siSwati'    => null,
        'Tshivenda'  => null,
        'XiTsonga'   => 'TS',
    ];
}
function skakmat_supported_languages(): array { return array_keys(skakmat_language_codes()); }
function skakmat_load_env(string $path): void { if (!file_exists($path)) return; foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) { if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue; [$key,$value]=explode('=', $line,2); $key=trim($key); $value=trim($value); if (preg_match('/^([\"\']).*\1$/', $value)) $value=substr($value,1,-1); if (!getenv($key)) { putenv("$key=$value"); $_ENV[$key]=$value; } } }
skakmat_load_env(__DIR__ . '/../../.env');
function current_language(): string { static $resolved=null; if ($resolved !== null) return $resolved; $candidate=$_COOKIE['skakmat_lang'] ?? 'English'; return $resolved=in_array($candidate, skakmat_supported_languages(), true) ? $candidate : 'English'; }
function skakmat_cache_path(string $language): string { $dir=__DIR__ . '/../../storage/i18n_cache'; if (!is_dir($dir)) @mkdir($dir,0775,true); return "$dir/$language.json"; }
function skakmat_load_cache(string $language): array { $path=skakmat_cache_path($language); $data=file_exists($path) ? json_decode((string)file_get_contents($path),true) : []; return is_array($data) ? $data : []; }
function skakmat_save_cache(string $language, array $cache): void { @file_put_contents(skakmat_cache_path($language), json_encode($cache, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); }
/**
 * Calls the DeepL API Free tier for a single string. Returns null on any
 * failure so callers can fall back gracefully instead of breaking the page.
 */
function skakmat_translate_via_api(string $text, string $targetLangCode): ?string
{
    $apiKey = getenv('DEEPL_API_KEY');

    if (!$apiKey) {
        return null;
    }

    if (!function_exists('curl_init')) {
        error_log('Skakmat i18n: PHP curl extension is not installed — cannot call translation API.');
        return null;
    }

    $payload = http_build_query([
        'text'        => $text,
        'source_lang' => 'EN',
        'target_lang' => $targetLangCode,
    ]);

    $ch = curl_init('https://api-free.deepl.com/v2/translate');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: DeepL-Auth-Key ' . $apiKey,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error !== '' || $httpCode !== 200 || !$response) {
        error_log("Skakmat i18n: DeepL API call failed ($httpCode) $error");
        return null;
    }

    $data = json_decode($response, true);
    $translated = $data['translations'][0]['text'] ?? null;

    return $translated ? html_entity_decode($translated, ENT_QUOTES | ENT_HTML5) : null;
}
function t(string $key): string { global $SKAKMAT_STRINGS,$SKAKMAT_FALLBACK_TRANSLATIONS; $source=$SKAKMAT_STRINGS[$key] ?? $key; $language=current_language(); if ($language === 'English') return $source; $cache=skakmat_load_cache($language); if (isset($cache[$key])) return $cache[$key]; $code=skakmat_language_codes()[$language] ?? null; $value=$code ? skakmat_translate_via_api($source,$code) : null; $cache[$key]=$value ?? $SKAKMAT_FALLBACK_TRANSLATIONS[$key][$language] ?? $source; skakmat_save_cache($language,$cache); return $cache[$key]; }

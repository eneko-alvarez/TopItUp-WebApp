<?php
/**
 * Language Management System
 * Handles language detection, cookie management, and translations
 */

// Supported languages
define('SUPPORTED_LANGUAGES', ['en', 'es']);
define('DEFAULT_LANGUAGE', 'en');

/**
 * Detect browser language from Accept-Language header
 * @return string Language code (en or es)
 */
function detectBrowserLanguage() {
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return DEFAULT_LANGUAGE;
    }
    
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    
    // Parse Accept-Language header (e.g., "es-ES,es;q=0.9,en;q=0.8")
    preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $acceptLanguage, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $lang) {
            // Extract primary language code (es-ES -> es)
            $langCode = strtok($lang, '-');
            
            if (in_array($langCode, SUPPORTED_LANGUAGES)) {
                return $langCode;
            }
        }
    }
    
    return DEFAULT_LANGUAGE;
}

/**
 * Get current language
 * Priority: GET parameter > Cookie > Browser detection
 * @return string Language code
 */
function getLang() {
    // Check if language is being changed via GET parameter
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGUAGES)) {
        setLang($_GET['lang']);
        return $_GET['lang'];
    }
    
    // Check cookie
    if (isset($_COOKIE['user_lang']) && in_array($_COOKIE['user_lang'], SUPPORTED_LANGUAGES)) {
        return $_COOKIE['user_lang'];
    }
    
    // Detect from browser and set cookie
    $detectedLang = detectBrowserLanguage();
    setLang($detectedLang);
    return $detectedLang;
}

/**
 * Set language and update cookie
 * @param string $lang Language code
 */
function setLang($lang) {
    if (!in_array($lang, SUPPORTED_LANGUAGES)) {
        $lang = DEFAULT_LANGUAGE;
    }
    
    setcookie('user_lang', $lang, [
        'expires' => time() + (365 * 24 * 60 * 60), // 1 year
        'path' => '/',
        'domain' => '',
        'secure' => true,      // HTTPS only
        'httponly' => true,    // Not accessible from JavaScript
        'samesite' => 'Strict' // CSRF protection
    ]);
    
    $_COOKIE['user_lang'] = $lang; // Set for current request
}

/**
 * Load translation dictionary for current language
 * @return array Translation array
 */
function loadTranslations() {
    $lang = getLang();
    $translationFile = __DIR__ . "/translations_{$lang}.php";
    
    if (file_exists($translationFile)) {
        return include $translationFile;
    }
    
    // Fallback to English
    return include __DIR__ . "/translations_en.php";
}

// Load translations globally
$GLOBALS['translations'] = loadTranslations();
$GLOBALS['current_lang'] = getLang();

/**
 * Translate a key
 * Supports dot notation for nested keys: "auth.login.title"
 * @param string $key Translation key
 * @param array $replacements Optional replacements for placeholders
 * @return string Translated text
 */
function t($key, $replacements = []) {
    $translations = $GLOBALS['translations'];
    $keys = explode('.', $key);
    $value = $translations;
    
    // Navigate through nested array
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            // Key not found, return the key itself for debugging
            return "[{$key}]";
        }
    }
    
    // Replace placeholders like {name}, {count}, etc.
    if (!empty($replacements) && is_string($value)) {
        foreach ($replacements as $placeholder => $replacement) {
            $value = str_replace("{{$placeholder}}", $replacement, $value);
        }
    }
    
    return $value;
}

/**
 * Get current language code
 * @return string
 */
function currentLang() {
    return $GLOBALS['current_lang'];
}

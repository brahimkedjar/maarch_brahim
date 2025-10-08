<?php
// Simple router for PHP built-in server to support:
// - Serving static assets (dist/, assets, etc.)
// - Forwarding /rest (and /<custom>/rest) to Slim
// - Falling back to SPA index for Angular routes

$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$root = __DIR__;

// Let php -S serve existing files directly
if (php_sapi_name() === 'cli-server') {
    $file = realpath($root . $uri);
    if ($file !== false && is_file($file)) {
        return false;
    }
}

// Utility: simple mime type guesser
function _mime(string $path): string {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'html' => 'text/html; charset=utf-8',
        'js'   => 'application/javascript; charset=utf-8',
        'css'  => 'text/css; charset=utf-8',
        'png'  => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'map'  => 'application/json; charset=utf-8',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'ico'  => 'image/x-icon',
        default => 'application/octet-stream',
    };
}

// Read custom.json to capture path prefixes (e.g., '/barid')
$customPaths = [];
$customFile = $root . '/custom/custom.json';
if (is_file($customFile)) {
    $json = json_decode(file_get_contents($customFile), true);
    if (is_array($json)) {
        foreach ($json as $entry) {
            if (!empty($entry['path'])) {
                $customPaths[] = '/' . trim($entry['path'], '/');
            }
        }
    }
}

// If URL is like '/{custom}/dist/...', map to real '/dist/...'
foreach ($customPaths as $prefix) {
    if (str_starts_with($uri, $prefix . '/dist/')) {
        $mapped = substr($uri, strlen($prefix)); // remove '/{custom}'
        $target = $root . $mapped;              // '/dist/...'
        if (is_file($target)) {
            header('Content-Type: ' . _mime($target));
            readfile($target);
            exit;
        }
    }
}

// Forward any request that contains '/rest' (supports '/{custom}/rest') to the Slim app
if (strpos($uri, '/rest') !== false) {
    // Ensure env vars expected by CoreConfigModel exist
    if (empty($_SERVER['SERVER_ADDR'])) {
        $_SERVER['SERVER_ADDR'] = $_SERVER['SERVER_NAME'] ?? '127.0.0.1';
    }
    $cwd = getcwd();
    chdir($root . '/rest');
    require $root . '/rest/index.php';
    chdir($cwd);
    exit;
}

// If hitting root or a custom base like '/barid', use the top-level index
if ($uri === '/' || preg_match('#^/[^/]+/?$#', $uri)) {
    require $root . '/index.php';
    exit;
}

// Fallback to SPA entrypoint (Angular client-side routes)
require $root . '/index.php';

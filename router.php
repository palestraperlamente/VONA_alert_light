<?php
// Router per il server built-in di PHP (php -S).
// Emula la regola di sniffetto/.htaccess (RewriteRule ^(.+)$ index.php?uri=$1)
// cosi' che http://localhost/sniffetto/... funzioni anche senza Apache.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$prefix = '/sniffetto';

if ($uri === $prefix || strpos($uri, $prefix . '/') === 0) {
    $path = ltrim(substr($uri, strlen($prefix)), '/');
    $file = __DIR__ . '/sniffetto/' . $path;

    // file statico esistente (es. asset) -> lascialo servire direttamente
    if ($path !== '' && is_file($file)) {
        return false;
    }

    $_GET['uri'] = $path;
    $_REQUEST['uri'] = $path;

    // in produzione sniffetto/ e' la document root reale del sito
    // (micron/core/Database/Database.php include "$_SERVER[DOCUMENT_ROOT]/config.php")
    $_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/sniffetto';

    chdir(__DIR__ . '/sniffetto');
    require __DIR__ . '/sniffetto/index.php';
    return true;
}

// fuori da /sniffetto: comportamento di default del server built-in
return false;

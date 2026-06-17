<?php

// Rygorystyczne ustawienia ciasteczka sesyjnego ===
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,              // Ciasteczko wygaśnie po zamknięciu przeglądarki
        'path' => '/',                // Dostępne w całej domenie aplikacji
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => false,            // Zmień na true, gdybyś wdrażał HTTPS
        'httponly' => true,           // KRYTYCZNE: JavaScript nie ma dostępu do ciasteczka (HttpOnly)
        'samesite' => 'Strict'        // Ochrona przed CSRF
    ]);

    session_start();
}

require_once 'Routing.php';

$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::run($path);


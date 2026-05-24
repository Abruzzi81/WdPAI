<?php

// Dołączamy repozytorium, aby móc odpytać bazę o statystyki gracza
require_once __DIR__ . '/../repositories/UsersRepository.php'; 

class AppController {
    
    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
    }
 
    protected function render(string $template = null, array $variables = [])
    {
        // === AUTOMATYCZNE POBIERANIE DANYCH DO HEADERA ===
        // 1. Sprawdzamy bezpiecznie, czy sesja istnieje
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Jeśli użytkownik jest zalogowany, dociągamy jego aktualne statystyki z bazy
        if (!empty($_SESSION['user_id'])) {
            $userRepo = new UsersRepository();
            $playerStats = $userRepo->getUserDetails($_SESSION['user_id']);
            
            // 3. Wstrzykujemy dane do zmiennych widoku pod kluczem 'loggedPlayer'
            // Dzięki temu zmienna $loggedPlayer będzie dostępna w KAŻDYM pliku .html/.php
            $variables['loggedPlayer'] = $playerStats;
        }
        // =================================================

        $templatePath = 'public/views/'. $template.'.html';
        $templatePath404 = 'public/views/404.html';
        $output = "";
                 
        if(file_exists($templatePath)){
            // Funkcja extract zamieni klucz 'loggedPlayer' na zmienną $loggedPlayer
            extract($variables);

            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        } else {
            ob_start();
            include $templatePath404;
            $output = ob_get_clean();
        }
        echo $output;
    }

    protected function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }
    }
}
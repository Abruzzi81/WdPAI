<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/MissionController.php';
require_once 'src/controllers/TrainingController.php';
require_once 'src/controllers/HangarController.php';
require_once 'src/controllers/ProfileController.php';

class Routing {

    // Tablica przechowująca konfigurację ścieżek bazowych
    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "dashboard" => [
            "controller" => "DashboardController",
            "action" => "index"
        ],
        "" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ],
        "logout" => [
            "controller" => "SecurityController",
            "action" => "logout"
        ],
        "mission" => [
            "controller" => "MissionController",
            "action" => "mission"
        ],
        "training" => [
            "controller" => "TrainingController",
            "action" => "training"
        ],
        "hangar" => [
            "controller" => "HangarController",
            "action" => "hangar"
        ],
        "profile" => [
            "controller" => "ProfileController",
            "action" => "profile"
        ]
        // Usunęliśmy stąd "easy" – teraz obsłuży to automatyczny REGEX poniżej!
    ];

    // SINGLETON - Tablica przechowująca już utworzone instancje kontrolerów
    private static $instances = [];

    // Metoda pomocnicza do pobierania/tworzenia pojedynczej instancji kontrolera (Singleton)
    private static function getControllerInstance(string $className) {
        if (!array_key_exists($className, self::$instances)) {
            self::$instances[$className] = new $className();
        }
        return self::$instances[$className];
    }

    public static function run(string $path) {
    $id = null;
    $customAction = null; // Dodatkowa zmienna na wypadek zmiany akcji

    // 1. REGEX - Obsługa adresów typu dashboard/12234
    if (preg_match('/^dashboard\/(\d+)$/', $path, $matches)) {
        $path = 'dashboard'; 
        $id = $matches[1];   
    }

    // 2. REGEX - Obsługa adresów typu training/easy, training/hard itp.
    if (preg_match('/^training\/([a-zA-Z]+)$/', $path, $matches)) {
        $path = 'training'; 
        $id = $matches[1];   
        
        // Zamiast modyfikować tablicę, zapisujemy, że chcemy odpalić metodę "game"
        $customAction = "game"; 
    }

    // Sprawdzamy, czy ścieżka istnieje w naszej mapie $routes
    if (array_key_exists($path, self::$routes)) {
        $controllerName = self::$routes[$path]["controller"];
        
        // JEŚLI REGEX ustawil własną akcję (game), użyj jej. W innym wypadku weź domyślną z tablicy.
        $action = ($customAction !== null) ? $customAction : self::$routes[$path]["action"];

        // Wywołujemy kontroler przez mechanizm Singletona
        $controllerObj = self::getControllerInstance($controllerName);
        
        // Uruchamiamy akcję i przekazujemy parametr (np. 'easy')
        $controllerObj->$action($id);
    } else {
        include 'public/views/404.html';
    }
}
}
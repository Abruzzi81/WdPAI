<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';

require_once 'src/controllers/MissionController.php';
require_once 'src/controllers/TrainingController.php';
require_once 'src/controllers/HangarController.php';
require_once 'src/controllers/ProfileController.php';

class Routing {

    // Tablica przechowująca konfigurację ścieżek
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
    ];

    // TODO 1: SINGLETON - Tablica przechowująca już utworzone instancje kontrolerów
    private static $instances = [];

    // Metoda pomocnicza do pobierania/tworzenia pojedynczej instancji kontrolera (Singleton)
    private static function getControllerInstance(string $className) {
        if (!array_key_exists($className, self::$instances)) {
            self::$instances[$className] = new $className();
        }
        return self::$instances[$className];
    }

    public static function run(string $path) {
        // Zmienna na ewentualne ID wyciągnięte z adresu URL
        $id = null;

        // TODO 2: REGEX - Obsługa adresów typu dashboard/12234
        // Szukamy wzorca: "dashboard/" po którym następuje ciąg cyfr (\d+)
        if (preg_match('/^dashboard\/(\d+)$/', $path, $matches)) {
            $path = 'dashboard'; // Ustawiamy ścieżkę bazową dla tablicy $routes
            $id = $matches[1];   // Wyciągamy złapane cyfry jako ID (np. 12234)
        }

        // TODO 3: Zmiana switch na array_key_exists
        // Sprawdzamy, czy oczyszczona ścieżka istnieje w naszej mapie $routes
        if (array_key_exists($path, self::$routes)) {
            $controllerName = self::$routes[$path]["controller"];
            $action = self::$routes[$path]["action"];

            // Wywołujemy kontroler przez mechanizm Singletona
            $controllerObj = self::getControllerInstance($controllerName);
            
            // Uruchamiamy akcję i przekazujemy ID (będzie liczbą lub null)
            $controllerObj->$action($id);
        } else {
            // Jeśli ścieżka nie istnieje w tablicy - błąd 404
            include 'public/views/404.html';
        }
    }
}
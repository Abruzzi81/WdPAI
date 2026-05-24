<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/MissionController.php';
require_once 'src/controllers/TrainingController.php';
require_once 'src/controllers/HangarController.php';
require_once 'src/controllers/ProfileController.php';

class Routing {

    // Tablica przechowująca konfigurację ścieżek bazowych (zazwyczaj dla żądań GET)
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
        $customAction = null; 
        $customController = null; // Zmienna pomocnicza do dynamicznego nadpisywania kontrolera

        // Przechwytujemy metodę HTTP (GET lub POST)
        $httpMethod = $_SERVER['REQUEST_METHOD'];

        // =================================================================
        // NOWOŚĆ: Przechwycenie żądania POST z JavaScriptu (Zapis wyniku treningu)
        // =================================================================
        if ($path === 'save-training' && $httpMethod === 'POST') {
            $path = 'save-training'; // Sztucznie utrzymujemy poprawny klucz, by przejść walidację
            $customController = "TrainingController";
            $customAction = "saveTrainingResult";
        }
        // =================================================================

        // 1. REGEX - Obsługa adresów typu dashboard/12234
        if (preg_match('/^dashboard\/(\d+)$/', $path, $matches)) {
            $path = 'dashboard'; 
            $id = $matches[1];   
        }

        // 2. REGEX - Obsługa adresów typu training/easy, training/hard itp.
        if (preg_match('/^training\/([a-zA-Z]+)$/', $path, $matches)) {
            $path = 'training'; 
            $id = $matches[1];   
            $customAction = "game"; 
        }

        // Jeśli to dynamiczna trasa 'save-training', wstrzykujemy ją tymczasowo do routingu
        if ($path === 'save-training' && $customController !== null) {
            $controllerName = $customController;
            $action = $customAction;
        } 
        // W innym wypadku sprawdzamy standardową mapę tras $routes
        elseif (array_key_exists($path, self::$routes)) {
            $controllerName = self::$routes[$path]["controller"];
            $action = ($customAction !== null) ? $customAction : self::$routes[$path]["action"];
        } else {
            include 'public/views/404.html';
            return;
        }

        // Wywołujemy kontroler przez mechanizm Singletona
        $controllerObj = self::getControllerInstance($controllerName);
        
        // Uruchamiamy akcję i przekazujemy parametr (w przypadku save-training $id będzie wynosić null)
        $controllerObj->$action($id);
    }
}
<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/MissionController.php';
require_once 'src/controllers/TrainingController.php';
require_once 'src/controllers/HangarController.php';
require_once 'src/controllers/ProfileController.php';

class Routing
{

    // Tablica przechowująca konfigurację ścieżek bazowych (zazwyczaj dla żądań GET)
    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
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
    private static function getControllerInstance(string $className)
    {
        if (!array_key_exists($className, self::$instances)) {
            self::$instances[$className] = new $className();
        }
        return self::$instances[$className];
    }

    public static function run(string $path)
    {
        $id = null;
        $customAction = null;
        $customController = null; // Zmienna pomocnicza do dynamicznego nadpisywania kontrolera

        // Przechwytujemy metodę HTTP (GET lub POST)
        $httpMethod = $_SERVER['REQUEST_METHOD'];

        // =================================================================
        // DYNAMICZNA OBSŁUGA ZAPYTAŃ ASYNCHRONICZNYCH (POST / FETCH API)
        // =================================================================
        if ($httpMethod === 'POST') {
            if ($path === 'save-training') {
                $customController = "TrainingController";
                $customAction = "saveTrainingResult";
            } elseif ($path === 'equip-avatar') {
                $customController = "HangarController";
                $customAction = "equipAvatar";
            }
            // NOWOŚĆ: Obsługa asynchronicznego zapisu zakończonej sukcesem misji
            elseif ($path === 'save-mission') {
                $customController = "MissionController";
                $customAction = "saveMissionResult";
            }
        }
        // =================================================================

        // 1. REGEX - Obsługa adresów typu dashboard/12234
        if (preg_match('/^dashboard\/(\d+)$/', $path, $matches)) {
            $path = 'dashboard';
            $id = $matches[1];
        }

        // 2. REGEX - Obsługa adresów typu training/easy, training/hard itp.
        if (preg_match('/^training\/(\d+)$/', $path, $matches)) {
            $path = 'training';
            $id = (int) $matches[1]; // Konwertujemy wycięte ID na liczbę całkowitą
            $customAction = "game";
        }
        // 3. REGEX - NOWOŚĆ: Obsługa wejścia do konkretnej misji (np. mission/1, mission/2)
        if (preg_match('/^mission\/(\d+)$/', $path, $matches)) {
            $path = 'mission';
            $id = (int) $matches[1]; // Konwertujemy ID poziomu na liczbę
            $customAction = "game"; // Nadpisujemy domyślną akcję na metodę urachamiającą grę misyjną
        }

        // Sprawdzamy, czy przypisaliśmy dynamiczny kontroler (dla naszych żądań POST)
        if ($customController !== null) {
            $controllerName = $customController;
            $action = $customAction;
        }
        // W innym wypadku sprawdzamy standardową mapę tras $routes (dla żądań GET)
        elseif (array_key_exists($path, self::$routes)) {
            $controllerName = self::$routes[$path]["controller"];
            $action = ($customAction !== null) ? $customAction : self::$routes[$path]["action"];
        } else {
            include 'public/views/404.html';
            return;
        }

        // Wywołujemy kontroler przez mechanizm Singletona
        $controllerObj = self::getControllerInstance($controllerName);

        // Uruchamiamy akcję i przekazujemy parametr ($id jako parametr metody w kontrolerze)
        $controllerObj->$action($id);
    }
}
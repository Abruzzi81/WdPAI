<?php

// .env 
require_once "config.php";

class Database {
    private $username;
    private $password;
    private $host;
    private $database;
    
    // Przechowujemy jedyne, współdzielone połączenie
    private static $instance = null;

    public function __construct()
    {
        $this->username = USERNAME;
        $this->password = PASSWORD;
        $this->host = HOST;
        $this->database = DATABASE;
    }

    public function connect()
    {
        // Jeśli połączenie już istnieje, nie twórz nowego – zwróć istniejące!
        if (self::$instance !== null) {
            return self::$instance;
        }

        try {
            // Tworzymy instancję połączenia tylko JEDEN RAZ w cyklu życia żądania
            // Parametr sslmode dopisujemy bezpośrednio w stringu DSN
            self::$instance = new PDO(
                "pgsql:host=$this->host;port=5432;dbname=$this->database;sslmode=prefer",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_EMULATE_PREPARES => false 
                ]
            );

            // set the PDO error mode to exception
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            return self::$instance;
        }
        catch(PDOException $e) {
            // change to error page e.g. 404 not found etc.
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function disconnect() {
        // Zamknięcie połączenia poprzez wyczyszczenie instancji statycznej
        self::$instance = null;
    }
}
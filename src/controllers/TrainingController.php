<?php

require_once 'AppController.php';

class TrainingController extends AppController
{
    /**
     * Główny widok Akademii Treningowej (Wybór Misji)
     * Ścieżka: /training
     */
    public function training()
    {
        // Zabezpieczenie: tylko zalogowani gracze mają wstęp do akademii
        $this->requireLogin();

        $title = "Galactic Math Explorer - Akademia";

        // Dane o zadaniach przekazywane do widoku wyboru poziomu
        $exercises = [
            ["id" => 1, "category" => "Dodawanie wektorów", "reward" => "200 Star Dust", "difficulty" => "Easy"],
            ["id" => 2, "category" => "Kalkulacja trajektorii", "reward" => "450 Star Dust", "difficulty" => "Medium"],
            ["id" => 3, "category" => "Anomalie kwantowe (Dzielenie)", "reward" => "900 Star Dust", "difficulty" => "Hard"]
        ];

        // Renderowanie widoku wyboru misji (training.php)
        return $this->render("training", [
            "title" => $title,
            "exercises" => $exercises
        ]);
    }

    /**
     * Widok samej rozgrywki matematycznej
     * Ścieżka: /training/easy, /training/hard itp.
     * * @param string $id Poziom trudności wycięty przez Router z adresu URL
     */
    public function game($id)
    {
        $this->requireLogin();
        $title = "OPERACJA: MNOŻENIE";

        $level = strtolower($id);
        $min = 2;
        $max = 9;

        if ($level === 'easy') {
            $min = 1;
            $max = 5;
        } elseif ($level === 'normal') {
            $min = 1;
            $max = 7;
        } elseif ($level === 'hard') {
            $min = 1;
            $max = 10;
        } elseif ($level === 'legendary') {
            $min = 1;
            $max = 15;
        }

        return $this->render("training-game", [
            "title" => $title,
            "level" => strtoupper($id),
            "number1" => rand($min, $max),
            "number2" => rand($min, $max)
        ]);
    }
}
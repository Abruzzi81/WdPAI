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
        // Zabezpieczenie: chronimy również sam ekran gry przed niezalogowanymi
        $this->requireLogin();

        $title = "OPERACJA: MNOŻENIE";

        // Generator liczb dla operacji mnożenia (zwraca liczby od 2 do 9)
        $number1 = rand(2, 9);
        $number2 = rand(2, 9);

        // Renderowanie widoku aktywnego reaktora matematycznego (training_game.php)
        return $this->render("training-game", [
            "title" => $title,
            "level" => strtoupper($id), // Zamienia np. 'easy' na 'EASY' do nagłówka
            "number1" => $number1,
            "number2" => $number2
        ]);
    }
}
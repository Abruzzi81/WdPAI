<?php

require_once 'AppController.php';

class TrainingController extends AppController {

    public function training() {
        $this->requireLogin();

        $title = "Galactic Math Explorer";
        
        // Przykładowe dane zadań (później mogą pochodzić z bazy danych)
        $exercises = [
            ["id" => 1, "category" => "Dodawanie wektorów", "reward" => "200 Star Dust", "difficulty" => "Easy"],
            ["id" => 2, "category" => "Kalkulacja trajektorii", "reward" => "450 Star Dust", "difficulty" => "Medium"],
            ["id" => 3, "category" => "Anomalie kwantowe (Dzielenie)", "reward" => "900 Star Dust", "difficulty" => "Hard"]
        ];

        return $this->render("training", [
            "title" => $title, 
            "exercises" => $exercises
        ]);
    }
}
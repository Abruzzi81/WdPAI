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

    public function saveTrainingResult() 
{
    // 1. Odbieramy surowe dane JSON wysłane za pomocą JavaScript Fetch API
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // 2. Upewniamy się, że sesja jest uruchomiona
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // 3. Walidacja: Sprawdzamy czy gracz jest zalogowany i czy przesłał poprawne dane
    if (!empty($_SESSION['user_id']) && isset($data['score']) && isset($data['level'])) {
        $userId = $_SESSION['user_id'];
        $score = (int)$data['score'];
        $level = $data['level']; // np. 'easy', 'hard'

        // 4. Wywołujemy repozytorium, które zaktualizuje bazę i zwróci obliczoną nagrodę
        $userRepo = new UsersRepository();
        $earnedStarDust = $userRepo->addTrainingReward($userId, $score, $level);

        // 5. Odsyłamy do JavaScript odpowiedź w formacie JSON
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'score' => $score,
            'earned_star_dust' => $earnedStarDust
        ]);
        exit();
    }

    // Jeśli coś poszło nie tak (np. brak autoryzacji)
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowe żądanie lub brak logowania']);
    exit();
}
}
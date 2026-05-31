<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repositories/TrainingRepository.php';

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

        $trainingRepo = new TrainingRepository();
        // Pobieramy dynamiczne poziomy z tabeli difficulty_levels
        $difficultyLevels = $trainingRepo->getDifficultyLevels();

        // Renderowanie zaktualizowanego widoku wyboru misji
        return $this->render("training", [
            "title" => "SYMULATOR TRENINGOWY",
            "difficultyLevels" => $difficultyLevels
        ]);
    }

    /**
     * Widok samej rozgrywki matematycznej
     * Ścieżka: /training/{id}
     * @param int $id ID poziomu trudności z bazy danych
     */
    public function game($id)
    {
        $this->requireLogin();

        $levelId = (int) $id;

        $trainingRepo = new TrainingRepository();
        $level = $trainingRepo->getDifficultyLevelDetails($levelId);

        // Jeśli poziom o danym ID nie istnieje w bazie, wracamy do wyboru treningów
        if (!$level) {
            header('Location: /training');
            exit();
        }

        // Dynamicznie pobieramy zakresy losowania bezpośrednio z bazy danych!
        $min = (int) $level['min_number'];
        $max = (int) $level['max_number'];

        // POPRAWKA: Przekazujemy klucze "min" oraz "max" do tablicy widoku!
        return $this->render("training-game", [
            "title" => "OPERACJA: MNOŻENIE - " . strtoupper($level['name']),
            "level" => strtoupper($level['name']),
            "min" => $min,                 // <-- Zostanie wstrzyknięte do data-min
            "max" => $max,                 // <-- Zostanie wstrzyknięte do data-max
            "number1" => rand($min, $max), // Pierwsza losowa liczba startowa
            "number2" => rand($min, $max)  // Druga losowa liczba startowa
        ]);
    }

    /**
     * Zapisywanie wyniku treningu po odebraniu żądania POST z JS Fetch API
     * Ścieżka: /save-training
     */
    public function saveTrainingResult()
    {
        // 1. Odbieramy surowe dane JSON wysłane z game.js
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        // 2. Walidacja przesłanych pól
        if (!empty($_SESSION['user_id']) && isset($data['score']) && isset($data['level'])) {
            $userId = $_SESSION['user_id'];
            $score = (int) $data['score'];
            $difficultyName = $data['level']; // Odbieramy np. 'easy', 'normal'...

            // Przeliczniki bazowe za JEDNĄ poprawną odpowiedź
            $starDustPerAnswer = 2;
            $expPerAnswer = 15; // Zmieniono na stałe 15 EXP za poprawną odp zgodnie z Twoim komentarzem

            try {
                $trainingRepo = new TrainingRepository();

                // Pobieramy multiplier z tabeli difficulty_levels
                $levelData = $trainingRepo->getDifficultyLevelByName($difficultyName);
                $multiplier = $levelData ? (float) $levelData['multiplier'] : 1.0;

                // Obliczamy nagrody końcowe: (ilość odp * stawka bazowa) * mnożnik poziomu
                $earnedStarDust = (int) round(($score * $starDustPerAnswer) * $multiplier);
                $earnedExp = (int) round(($score * $expPerAnswer) * $multiplier);

                // Zapisujemy zmiany w bazie danych
                $levelId = $levelData ? (int) $levelData['id'] : 1; // Pobieramy ID z odszukanego poziomu
                $trainingRepo->addTrainingRewards($userId, $earnedStarDust, $earnedExp, $levelId, $score);
                
                // 3. Zwracamy obiekt dokładnie dopasowany pod oczekiwania Twojego JS
                echo json_encode([
                    'status' => 'success',
                    'score' => $score,
                    'earned_star_dust' => $earnedStarDust,
                    'earned_exp' => $earnedExp
                ]);
                exit();

            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Awaria systemu zapisu akademii.']);
                exit();
            }
        }

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowy pakiet danych sieciowych.']);
        exit();
    }
}
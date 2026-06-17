<?php

require_once 'Repository.php';

class TrainingRepository extends Repository
{
    /**
     * Pobiera wszystkie poziomy trudności z bazy danych
     * Używane do wygenerowania kafelków na stronie /training
     */
    public function getDifficultyLevels(): array
    {
        $query = $this->database->connect()->prepare(
            "SELECT id, name, multiplier, description FROM training_levels ORDER BY id ASC;"
        );
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pobiera szczegóły jednego poziomu trudności na podstawie jego ID
     * Używane przy uruchamianiu gry: /training/game/[id]
     */
    public function getDifficultyLevelDetails(int $levelId): ?array
    {
        $query = $this->database->connect()->prepare(
            "SELECT id, name, multiplier, description, min_number, max_number 
             FROM training_levels 
             WHERE id = ?;"
        );
        $query->execute([$levelId]);
        return $query->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Szuka poziomu trudności po nazwie (np. 'easy', 'legendary')
     * Używane do weryfikacji mnożnika podczas zapisu nagrody w saveTrainingResult
     */
    public function getDifficultyLevelByName(string $name): ?array
    {
        $query = $this->database->connect()->prepare(
            "SELECT id, name, multiplier FROM training_levels WHERE LOWER(name) = LOWER(?);"
        );
        $query->execute([$name]);
        return $query->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Zapisuje ukończony trening w tabeli historycznej
     */
    public function saveTrainingToHistory(int $userId, int $levelId, int $score): void
    {
        $query = $this->database->connect()->prepare(
            "INSERT INTO training_history (user_id, level_id, score) 
             VALUES (?, ?, ?);"
        );
        $query->execute([$userId, $levelId, $score]);
    }

    /**
     * Aktualizuje stan portfela i doświadczenia użytkownika w bazie oraz zapisuje zdarzenie w historii.
     * Zastosowano bezpieczną transakcję na poziomie izolacji REPEATABLE READ.
     */
    public function addTrainingRewards(int $userId, int $starDust, int $exp, int $levelId, int $score): void
    {
        // Pobieramy jedno wspólne połączenie dla całej transakcji
        $db = $this->database->connect();

        try {
            // 1. Inicjalizacja transakcji w sterowniku PDO
            $db->beginTransaction();

            // 2. KRYTYCZNE DLA WYMAGAŃ: Wymuszenie poziomu izolacji w PostgreSQL
            // Zapobiega anomalii niepowtarzalnego odczytu (Non-repeatable Read)
            $db->exec("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");

            // 3. KROK A: Aktualizacja zasobów gracza (używamy bindowania parametrów przed SQL Injection)
            $queryRewards = $db->prepare(
                "UPDATE user_details 
                 SET star_dust = star_dust + :star_dust, 
                     exp = exp + :exp 
                 WHERE user_id = :user_id;"
            );
            $queryRewards->bindValue(':star_dust', $starDust, PDO::PARAM_INT);
            $queryRewards->bindValue(':exp', $exp, PDO::PARAM_INT);
            $queryRewards->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $queryRewards->execute();

            // 4. KROK B: Dodanie rekordu do tabeli historii treningów
            $queryHistory = $db->prepare(
                "INSERT INTO training_history (user_id, level_id, score) 
                 VALUES (:user_id, :level_id, :score);"
            );
            $queryHistory->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $queryHistory->bindValue(':level_id', $levelId, PDO::PARAM_INT);
            $queryHistory->bindValue(':score', $score, PDO::PARAM_INT);
            $queryHistory->execute();

            // 5. Jeśli oba kroki przeszły bez błędów atomowo zatwierdzamy zmiany
            $db->commit();

        } catch (Exception $e) {
            // W razie jakiegokolwiek błędu (np. utrata połączenia, błąd bazy),
            // cofamy wszystkie operacje wykonane od momentu beginTransaction()
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
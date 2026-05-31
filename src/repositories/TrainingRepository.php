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
     * Zastosowano bezpieczną transakcję (PDO Begintransaction), by uniknąć błędów niespójności danych.
     */
    public function addTrainingRewards(int $userId, int $starDust, int $exp, int $levelId, int $score): void
    {
        $db = $this->database->connect();

        try {
            // Uruchamiamy transakcję SQL
            $db->beginTransaction();

            // 1. Aktualizacja zasobów gracza
            $queryRewards = $db->prepare(
                "UPDATE user_details 
                 SET star_dust = star_dust + ?, 
                     exp = exp + ? 
                 WHERE user_id = ?;"
            );
            $queryRewards->execute([$starDust, $exp, $userId]);

            // 2. Dodanie rekordu do nowej tabeli historii treningów
            $queryHistory = $db->prepare(
                "INSERT INTO training_history (user_id, level_id, score) 
                 VALUES (?, ?, ?);"
            );
            $queryHistory->execute([$userId, $levelId, $score]);

            // Jeśli oba zapytania przeszły pomyślnie, zatwierdzamy zmiany w bazie
            $db->commit();

        } catch (Exception $e) {
            // W razie jakiegokolwiek błędu wycofujemy wszystkie zmiany z tej sesji
            $db->rollBack();
            throw $e;
        }
    }
}
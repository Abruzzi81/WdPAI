<?php

require_once 'Repository.php';

class MissionRepository extends Repository
{
    public function getMissionLevelsForUser(int $userId): array
    {
        $query = $this->database->connect()->prepare(
            "SELECT 
                ml.id, 
                ml.name, 
                ml.difficulty, 
                ml.min_number, 
                ml.max_number,
                ml.sequence_order,
                ml.reward,
                ml.exp_reward, 
                CASE WHEN um.level_id IS NOT NULL THEN 'completed'
                     WHEN ml.sequence_order = 1 THEN 'active'
                     WHEN EXISTS (
                         SELECT 1 FROM user_missions um2
                         JOIN mission_levels ml2 ON um2.level_id = ml2.id
                         WHERE um2.user_id = :user_id AND ml2.sequence_order = ml.sequence_order - 1
                     ) THEN 'active'
                     ELSE 'locked'
                END as status
             FROM mission_levels ml
             LEFT JOIN user_missions um ON ml.id = um.level_id AND um.user_id = :user_id
             ORDER BY ml.sequence_order ASC;"
        );

        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Pobiera szczegóły poziomu - Zoptymalizowane pod kątem minimalizacji pobierania danych
     */
    public function getLevelDetails(int $levelId): ?array
    {
        // POPRAWKA: Jawne wskazanie kolumn zamiast SELECT *
        $query = $this->database->connect()->prepare(
            "SELECT id, name, difficulty, min_number, max_number, reward, exp_reward 
             FROM mission_levels 
             WHERE id = ?;"
        );
        $query->execute([$levelId]);
        return $query->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Zapisuje sukces ukończonej misji.
     * Zastosowano bezpieczną transakcję na poziomie izolacji REPEATABLE READ.
     */
    public function completeMission(int $userId, int $levelId, int $reward, int $expReward): void
    {
        $db = $this->database->connect();
        try {
            // 1. Inicjalizacja transakcji w sterowniku PDO
            $db->beginTransaction();

            // 2. KRYTYCZNE: Wymuszenie rygorystycznego poziomu izolacji dla PostgreSQL
            $db->exec("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");

            // 3. Aktualizacja zasobów gracza (waluta oraz EXP) z bindowaniem typów
            $queryReward = $db->prepare(
                "UPDATE user_details 
                 SET star_dust = star_dust + :reward, 
                     exp = exp + :exp_reward 
                 WHERE user_id = :user_id;"
            );
            $queryReward->bindValue(':reward', $reward, PDO::PARAM_INT);
            $queryReward->bindValue(':exp_reward', $expReward, PDO::PARAM_INT);
            $queryReward->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $queryReward->execute();

            // 4. Odblokowanie kolejnego sektora/zapis postępu na mapie misji
            $queryProgress = $db->prepare(
                "INSERT INTO user_missions (user_id, level_id) 
                 VALUES (:user_id, :level_id) 
                 ON CONFLICT (user_id, level_id) DO NOTHING;"
            );
            $queryProgress->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $queryProgress->bindValue(':level_id', $levelId, PDO::PARAM_INT);
            $queryProgress->execute();

            // 5. Zatwierdzenie transakcji
            $db->commit();
        } catch (Exception $e) {
            // Bezpieczne cofnięcie transakcji w przypadku błędu
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
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
            ml.reward, -- Korzystamy wyłącznie z nowej kolumny nagrody
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

    public function getLevelDetails(int $levelId): ?array
    {
        $query = $this->database->connect()->prepare("SELECT * FROM mission_levels WHERE id = ?;");
        $query->execute([$levelId]);
        return $query->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function completeMission(int $userId, int $levelId, int $reward): void
    {
        $db = $this->database->connect();
        try {
            $db->beginTransaction();

            $queryReward = $db->prepare("UPDATE user_details SET star_dust = star_dust + ? WHERE user_id = ?;");
            $queryReward->execute([$reward, $userId]);

            $queryProgress = $db->prepare(
                "INSERT INTO user_missions (user_id, level_id) VALUES (?, ?) 
                 ON CONFLICT (user_id, level_id) DO NOTHING;"
            );
            $queryProgress->execute([$userId, $levelId]);

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
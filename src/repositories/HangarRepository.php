<?php

require_once 'Repository.php';

class HangarRepository extends Repository
{
    // Pobiera wszystkie awatary i sprawdza, czy dany user_id je posiada
    public function getAllAvatars(int $userId): array
    {
        $query = $this->database->connect()->prepare(
            "SELECT 
                a.id, 
                a.name, 
                a.price, 
                a.image_filename,
                CASE WHEN ua.avatar_id IS NOT NULL THEN TRUE ELSE FALSE END as is_owned
             FROM avatars a
             LEFT JOIN user_avatars ua ON a.id = ua.avatar_id AND ua.user_id = :user_id
             ORDER BY a.price ASC;"
        );

        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ustawia aktualny awatar użytkownika
    public function updateEquippedAvatar(int $userId, int $avatarId): bool
    {
        $query = $this->database->connect()->prepare(
            "UPDATE user_details 
             SET current_avatar_id = :avatar_id 
             WHERE user_id = :user_id;"
        );
        $query->bindValue(':avatar_id', $avatarId, PDO::PARAM_INT);
        $query->bindValue(':user_id', $userId, PDO::PARAM_INT);
        return $query->execute();
    }

    public function purchaseAvatar(int $userId, int $avatarId): array
    {
        $db = $this->database->connect();

        try {
            // 1. Uruchamiamy transakcję SQL
            $db->beginTransaction();

            // 2. KRYTYCZNE DLA WYMAGAŃ: Wymuszenie rygorystycznego poziomu izolacji (ochrona przed double-spending)
            $db->exec("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");

            // 3. Pobieramy cenę awatara (Zoptymalizowany SELECT pod konkretną kolumnę)
            $queryPrice = $db->prepare("SELECT price FROM avatars WHERE id = :avatar_id;");
            $queryPrice->bindValue(':avatar_id', $avatarId, PDO::PARAM_INT);
            $queryPrice->execute();
            $avatar = $queryPrice->fetch(PDO::FETCH_ASSOC);

            if (!$avatar) {
                throw new Exception("Moduł tożsamości nie istnieje.");
            }

            $price = (int) $avatar['price'];

            // 4. Pobieramy aktualny stan portfela gracza (POPRAWKA: Usunięto SELECT *)
            $queryUser = $db->prepare("SELECT star_dust FROM user_details WHERE user_id = :user_id;");
            $queryUser->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $queryUser->execute();
            $userData = $queryUser->fetch(PDO::FETCH_ASSOC);
            
            $currentDust = $userData ? (int) $userData['star_dust'] : 0;

            // 5. Weryfikacja finansowa
            if ($currentDust < $price) {
                throw new Exception("Niewystarczająca ilość Gwiezdnego Pyłu! Wymagane: ✨ " . $price);
            }

            // 6. Pobieramy opłatę (UPDATE)
            $queryDeduct = $db->prepare("UPDATE user_details SET star_dust = star_dust - :price WHERE user_id = :user_id;");
            $queryDeduct->bindValue(':price', $price, PDO::PARAM_INT);
            $queryDeduct->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $queryDeduct->execute();

            // 7. Dodajemy przedmiot do posiadanych (INSERT)
            $queryUnlock = $db->prepare("INSERT INTO user_avatars (user_id, avatar_id) VALUES (:user_id, :avatar_id);");
            $queryUnlock->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $queryUnlock->bindValue(':avatar_id', $avatarId, PDO::PARAM_INT);
            $queryUnlock->execute();

            // Zatwierdzamy zmiany atomowo w bazie danych
            $db->commit();

            return [
                'status' => 'success',
                'new_balance' => $currentDust - $price
            ];

        } catch (Exception $e) {
            // W razie błędu bezpiecznie wycofujemy transakcję
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
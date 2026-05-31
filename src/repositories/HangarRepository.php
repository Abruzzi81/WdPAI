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
             SET current_avatar_id = ? 
             WHERE user_id = ?;"
        );
        return $query->execute([$avatarId, $userId]);
    }

    public function purchaseAvatar(int $userId, int $avatarId): array
    {
        $db = $this->database->connect();

        try {
            // Uruchamiamy transakcję SQL, aby mieć pewność, że jeśli coś się nie powiedzie, baza nie straci spójności
            $db->beginTransaction();

            // 1. Pobieramy cenę awatara
            $queryPrice = $db->prepare("SELECT price FROM avatars WHERE id = ?;");
            $queryPrice->execute([$avatarId]);
            $avatar = $queryPrice->fetch(PDO::FETCH_ASSOC);

            if (!$avatar) {
                throw new Exception("Moduł tożsamości nie istnieje.");
            }

            $price = (int) $avatar['price'];

            // 2. Pobieramy aktualny stan portfela gracza
            $queryUser = $db->prepare("SELECT star_dust FROM user_details WHERE user_id = ?;");
            $queryUser->execute([$userId]);
            $userData = $queryUser->fetch(PDO::FETCH_ASSOC);
            $currentDust = (int) $userData['star_dust'];

            // 3. Weryfikacja finansowa
            if ($currentDust < $price) {
                throw new Exception("Niewystarczająca ilość Gwiezdnego Pyłu! Wymagane: ✨ " . $price);
            }

            // 4. Pobieramy opłatę (UPDATE)
            $queryDeduct = $db->prepare("UPDATE user_details SET star_dust = star_dust - ? WHERE user_id = ?;");
            $queryDeduct->execute([$price, $userId]);

            // 5. Dodajemy przedmiot do posiadanych (INSERT)
            $queryUnlock = $db->prepare("INSERT INTO user_avatars (user_id, avatar_id) VALUES (?, ?);");
            $queryUnlock->execute([$userId, $avatarId]);

            // Zatwierdzamy zmiany w bazie
            $db->commit();

            return [
                'status' => 'success',
                'new_balance' => $currentDust - $price
            ];

        } catch (Exception $e) {
            // W razie błędu wycofujemy wszystkie operacje z tej transakcji
            $db->rollBack();
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
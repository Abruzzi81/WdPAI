<?php

require_once 'Repository.php';

class UsersRepository extends Repository
{
    public function getUsers(): ?array
    {
        $query = $this->database->connect()->prepare(
            "SELECT 
                u.id, 
                u.username, 
                u.email, 
                u.role,
                u.status,
                COALESCE(ud.star_dust, 0) AS star_dust,
                COALESCE(a.image_filename, 'avatar_cadet.png') AS avatar_file
             FROM users u
             LEFT JOIN user_details ud ON u.id = ud.user_id
             LEFT JOIN avatars a ON ud.current_avatar_id = a.id
             ORDER BY u.id ASC;"
        );
        $query->execute();

        $users = $query->fetchAll(PDO::FETCH_ASSOC);
        return $users ?: [];
    }

    public function updateUserStatus(int $userId, string $status): void
    {
        $query = $this->database->connect()->prepare(
            "UPDATE users SET status = :status WHERE id = :id;"
        );
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->bindParam(':id', $userId, PDO::PARAM_INT);
        $query->execute();
    }

    public function getUserByEmail(string $email)
    {
        $query = $this->database->connect()->prepare(
            "SELECT * FROM users WHERE email = :email"
        );
        $query->bindParam(':email', $email);
        $query->execute();

        $user = $query->fetch(PDO::FETCH_ASSOC);
        return $user;
    }

    public function createUser(
        string $email,
        string $hashedPassword,
        string $username,
        string $bio = ''
    ) {
        $db = $this->database->connect();

        // 1. KROK: Wstawiamy dane do tabeli 'users'
        $query1 = $db->prepare(
            "INSERT INTO users (username, email, password)
             VALUES (?, ?, ?) RETURNING id;"
        );

        $query1->execute([
            $username,
            $email,
            $hashedPassword
        ]);

        $user = $query1->fetch(PDO::FETCH_ASSOC);
        $newUserId = $user['id'];

        // 2. KROK: Automatycznie tworzymy powiązany rekord w 'user_details'
        $query2 = $db->prepare(
            "INSERT INTO user_details (user_id, star_dust, exp, rank_id, current_avatar_id)
             VALUES (?, ?, ?, ?, ?);"
        );

        $query2->execute([
            $newUserId,
            0,      // star_dust
            0,      // exp
            null,   // rank_id
            1       // current_avatar_id (domyślny avatar_cadet.png)
        ]);
    }

    /**
     * Zwraca pełne, szczegółowe dane profilowe użytkownika (statystyki, ranga, awatar)
     * Obsługuje zarówno widoki gry, jak i panel profilu gracza.
     */
    public function getUserDetails(int $userId): ?array
    {
        $query = $this->database->connect()->prepare(
            "SELECT 
                u.id, 
                u.username, 
                u.role, 
                ud.star_dust, 
                ud.exp,
                ud.current_avatar_id,
                COALESCE(r.name, 'BRAK RANGI') AS rank_name,
                COALESCE(a.image_filename, 'avatar_cadet.png') AS avatar_file,
                COALESCE(a.name, 'Kadet Nowicjusz') AS avatar_name
             FROM users u
             JOIN user_details ud ON u.id = ud.user_id
             LEFT JOIN ranks r ON ud.rank_id = r.id
             LEFT JOIN avatars a ON ud.current_avatar_id = a.id
             WHERE u.id = :user_id;"
        );

        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->execute();

        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : null;
    }

    /**
     * NOWOŚĆ (z ProfileRepository): Pobiera historię treningów użytkownika z bazy danych
     * Wykorzystuje zaktualizowaną nazwę tabeli 'training_levels'
     */
    public function getTrainingHistory(int $userId): array
    {
        $query = $this->database->connect()->prepare(
            "SELECT 
                th.score, 
                th.completed_at, 
                tl.name AS level_name
             FROM training_history th
             JOIN training_levels tl ON th.level_id = tl.id
             WHERE th.user_id = ?
             ORDER BY th.completed_at DESC
             LIMIT 10;" // Zwraca 10 ostatnich wpisów historycznych
        );
        $query->execute([$userId]);
        return $query->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTotalTrainingsCount(int $userId): int
    {
        $query = $this->database->connect()->prepare(
            "SELECT COUNT(*) AS total FROM training_history WHERE user_id = ?;"
        );
        $query->execute([$userId]);
        $result = $query->fetch(PDO::FETCH_ASSOC);

        return $result ? (int) $result['total'] : 0;
    }
}
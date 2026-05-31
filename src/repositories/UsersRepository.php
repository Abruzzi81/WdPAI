<?php

require_once 'Repository.php';

class UsersRepository extends Repository
{

    public function getUsers(): ?array
    {
        $query = $this->database->connect()->prepare(
            "
            SELECT * FROM users;
            "
        );
        $query->execute();

        $users = $query->fetchAll(PDO::FETCH_ASSOC);
        return $users;
    }

    public function getUserByEmail(string $email)
    {
        $query = $this->database->connect()->prepare(
            "
            SELECT * FROM users WHERE email = :email
            "
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
            "
            INSERT INTO users (username, email, password)
            VALUES (?, ?, ?) RETURNING id;
            "
        );

        $query1->execute([
            $username,
            $email,
            $hashedPassword
        ]);

        $user = $query1->fetch(PDO::FETCH_ASSOC);
        $newUserId = $user['id'];

        // 2. KROK: Automatycznie tworzymy powiązany rekord w 'user_details'
        // Nowy gracz domyślnie otrzymuje 0 Gwiezdnego Pyłu (star_dust), 0 EXP, 
        // domyślny awatar o ID = 1 oraz brak rangi na starcie (null)
        $query2 = $db->prepare(
            "
            INSERT INTO user_details (user_id, star_dust, exp, rank_id, current_avatar_id)
            VALUES (?, ?, ?, ?, ?);
            "
        );

        $query2->execute([
            $newUserId,
            0,      // star_dust
            0,      // exp
            null,   // rank_id
            1       // current_avatar_id (domyślny avatar_cadet.png z tabeli avatars)
        ]);
    }

    public function getUserDetails(int $userId): ?array
    {
        // Zaktualizowane zapytanie z LEFT JOIN do tabeli avatars
        $query = $this->database->connect()->prepare(
            "SELECT 
                u.id, 
                u.username, 
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

    public function addTrainingReward(int $userId, int $score, string $level): int
    {
        $db = $this->database->connect();

        // 1. Wywołujemy funkcję SQL pobierającą mnożnik z tabeli słownikowej
        $queryReward = $db->prepare("SELECT fn_calculate_reward(?, ?) AS computed_reward;");
        $queryReward->execute([$score, $level]);
        $rewardData = $queryReward->fetch(PDO::FETCH_ASSOC);
        $calculatedReward = (int) $rewardData['computed_reward'];

        // 2. Dodajemy obliczoną nagrodę bezpośrednio do profilu gracza w user_details
        $queryUpdate = $db->prepare(
            "UPDATE user_details 
             SET star_dust = star_dust + ? 
             WHERE user_id = ?;"
        );
        $queryUpdate->execute([$calculatedReward, $userId]);

        return $calculatedReward;
    }
}
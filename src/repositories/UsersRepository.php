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
        // Pobieramy jedno współdzielone połączenie z bazą danych
        $db = $this->database->connect();

        // 1. KROK: Wstawiamy dane do tabeli 'users' (bez żadnych zmian struktury)
        // Dodajemy na końcu RETURNING id, aby PostgreSQL od razu powiedział nam, jakie ID dostał ten użytkownik
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

        // Wyciągamy wygenerowane przez bazę ID
        $user = $query1->fetch(PDO::FETCH_ASSOC);
        $newUserId = $user['id'];

        // 2. KROK: Automatycznie tworzymy powiązany rekord w 'user_details'
        // Przekazujemy pobrane przed chwilą $newUserId, aby spiąć tabele relacją 1:1
        $query2 = $db->prepare(
            "
            INSERT INTO user_details (user_id, star_dust, rank_id)
            VALUES (?, ?, ?);
            "
        );

        // Nowy gracz domyślnie otrzymuje 0 Gwiezdnego Pyłu (star_dust) i brak rangi (null)
        $query2->execute([
            $newUserId,
            0,
            null
        ]);
    }

    public function getUserDetails(int $userId): ?array
    {
        $query = $this->database->connect()->prepare(
            "SELECT 
            u.id, 
            u.username, 
            ud.star_dust, 
            ud.exp,
            COALESCE(r.name, 'BRAK RANGI') AS rank_name
         FROM users u
         JOIN user_details ud ON u.id = ud.user_id
         LEFT JOIN ranks r ON ud.rank_id = r.id
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

        // 1. Wywołujemy funkcję SQL, którą stworzyłeś w pgAdminie w Opcji B
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

        // Zwracamy wartość, aby kontroler wiedział, ile pyłu dopisano
        return $calculatedReward;
    }
}
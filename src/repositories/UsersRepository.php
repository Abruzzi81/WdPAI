<?php

require_once 'Repository.php';

class UsersRepository extends Repository {

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

    public function getUserByEmail(string $email) {
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
}
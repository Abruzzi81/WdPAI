<?php

require_once 'AppController.php';
// WAŻNE: Dołączamy repozytorium, aby móc wywołać metodę aktualizacji statusu
require_once __DIR__ . '/../repositories/UsersRepository.php'; 

class SecurityController extends AppController
{

    public function login()
    {
        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ['messages' => 'Fill all fields']);
        }

        $userRepository = new UsersRepository();
        $user = $userRepository->getUserByEmail($email);

        if (!$user) {
            return $this->render('login', ['messages' => 'User not found']);
        }

        if (!password_verify($password, $user['password'])) {
            return $this->render('login', ['messages' => 'Wrong password']);
        }

        // === NOWOŚĆ: Blokada logowania dla zbanowanych kadetów ===
        if (isset($user['status']) && $user['status'] === 'banned') {
            return $this->render('login', [
                'messages' => 'Twoje konto zostało zablokowane przez Galactic Command Center.'
            ]);
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_firstname'] = $user['firstname'] ?? null;
        $_SESSION['is_logged_in'] = true;

        // === Przełączamy status użytkownika na ONLINE w bazie danych ===
        $userRepository->updateUserStatus($user['id'], 'online');

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/mission");
        exit();
    }

    public function register()
    {
        $userRepository = new UsersRepository();

        if ($this->isPost()) {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';
            $username = $_POST['username'] ?? '';

            // Przygotowujemy dane do ponownego wstrzyknięcia w formularz
            $formData = [
                'email' => $email,
                'username' => $username
            ];

            // 1. Sprawdzenie czy pola nie są puste
            if (empty($email) || empty($password) || empty($username)) {
                return $this->render('register', ['messages' => 'Fill all fields', 'data' => $formData]);
            }

            // 2. Sprawdzenie czy hasła są identyczne
            if ($password !== $password2) {
                return $this->render('register', ['messages' => 'Passwords do not match', 'data' => $formData]);
            }

            // 3. Walidacja złożoności hasła
            $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
            if (!preg_match($passwordRegex, $password)) {
                return $this->render('register', [
                    'messages' => 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.',
                    'data' => $formData
                ]);
            }

            // 4. Sprawdzenie czy użytkownik już istnieje
            $user = $userRepository->getUserByEmail($email);
            if ($user) {
                return $this->render("register", ["messages" => "User exists", 'data' => $formData]);
            }

            // 5. Haszowanie i zapis do bazy (status domyślnie ustawi się jako 'offline')
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $userRepository->createUser($email, $hashedPassword, $username);

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            return;
        }

        return $this->render("register");
    }

    public function logout()
    {
        // Upewniamy się, że sesja jest uruchomiona
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // === Przełączamy użytkownika na OFFLINE przed wyczyszczeniem sesji ===
        if (!empty($_SESSION['user_id'])) {
            $userRepository = new UsersRepository();
            $userRepository->updateUserStatus($_SESSION['user_id'], 'offline');
        }

        // Czyścimy wszystkie dane sesji
        $_SESSION = [];

        // Kasujemy ciasteczko sesji po stronie przeglądarki
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Niszczymy sesję
        session_destroy();

        // Przekierowanie na ekran logowania
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        exit(); 
    }
}
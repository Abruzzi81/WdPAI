<?php

require_once 'AppController.php';
// WAŻNE: Dołączamy repozytorium, aby móc wywołać metodę aktualizacji statusu
require_once __DIR__ . '/../repositories/UsersRepository.php';

class SecurityController extends AppController
{

    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Zmienna na komunikaty błędów, które przekażemy do widoku
        $messages = null;

        // === PRZETWARZANIE DANYCH (TYLKO DLA POST) ===
        if ($this->isPost()) {
            $userCsrfToken = $_POST['csrf_token'] ?? '';

            if (empty($_SESSION['csrf_token']) || $userCsrfToken !== $_SESSION['csrf_token']) {
                unset($_SESSION['csrf_token']);
                return $this->render('login', ['messages' => 'Nieprawidłowy lub wygasły token CSRF']);
            }

            $email = $_POST["email"] ?? '';
            $password = $_POST["password"] ?? '';

            if (strlen($email) > 255 || strlen($password) > 72) {
                return $this->render('login', ['messages' => 'Input length exceeded']);
            }

            if (empty($email) || empty($password)) {
                return $this->render('login', ['messages' => 'Fill all fields']);
            }

            $userRepository = new UsersRepository();
            $user = $userRepository->getUserByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                // Dla bezpieczeństwa nie zdradzamy, czy e-mail czy hasło było błędne
                return $this->render('login', ['messages' => 'Nieprawidłowy e-mail lub hasło']);
            }

            if (isset($user['status']) && $user['status'] === 'banned') {
                return $this->render('login', [
                    'messages' => 'Twoje konto zostało zablokowane przez Galactic Command Center.'
                ]);
            }

            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_logged_in'] = true;

            $userRepository->updateUserStatus($user['id'], 'online');

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/mission");
            exit();
        }

        // === RENDEROWANIE WIDOKU (DLA GET LUB PO POMINIĘCIU LOGOWANIA) ===
        return $this->render('login', [
            'csrf_token' => $_SESSION['csrf_token'],
            'messages' => $messages
        ]);
    }

    public function register()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // === PRZETWARZANIE DANYCH (TYLKO DLA POST) ===
        if ($this->isPost()) {
            $userRepository = new UsersRepository();
            $userCsrfToken = $_POST['csrf_token'] ?? '';

            if (empty($_SESSION['csrf_token']) || $userCsrfToken !== $_SESSION['csrf_token']) {
                unset($_SESSION['csrf_token']);
                return $this->render('register', ['messages' => 'Nieprawidłowy lub wygasły token CSRF']);
            }

            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';
            $username = $_POST['username'] ?? '';

            if (strlen($email) > 255 || strlen($username) > 50 || strlen($password) > 72) {
                return $this->render('register', [
                    'messages' => 'Input length exceeded',
                    'data' => ['email' => $email, 'username' => $username]
                ]);
            }

            $formData = ['email' => $email, 'username' => $username];

            if (empty($email) || empty($password) || empty($username)) {
                return $this->render('register', ['messages' => 'Fill all fields', 'data' => $formData]);
            }

            if ($password !== $password2) {
                return $this->render('register', ['messages' => 'Passwords do not match', 'data' => $formData]);
            }

            $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
            if (!preg_match($passwordRegex, $password)) {
                return $this->render('register', [
                    'messages' => 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.',
                    'data' => $formData
                ]);
            }

            $user = $userRepository->getUserByEmail($email);
            if ($user) {
                return $this->render("register", ["messages" => "User exists", 'data' => $formData]);
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $userRepository->createUser($email, $hashedPassword, $username);

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        // === RENDEROWANIE WIDOKU (DLA GET) ===
        return $this->render("register", [
            'csrf_token' => $_SESSION['csrf_token']
        ]);
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
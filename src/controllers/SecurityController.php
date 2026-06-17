<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repositories/UsersRepository.php';

class SecurityController extends AppController
{

    public function login()
    {
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
                return $this->render('login', ['messages' => 'Przekroczono dopuszczalną długość']);
            }

            if (empty($email) || empty($password)) {
                return $this->render('login', ['messages' => 'Wypełnij wszystkie pola']);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->render('login', ['messages' => 'Nieprawidłowy e-mail lub hasło']);
            }

            $userRepository = new UsersRepository();
            $user = $userRepository->getUserByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $this->logFailedLogin($email);
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
            $username = strip_tags(trim($_POST['username'] ?? ''));

            if (strlen($email) > 255 || strlen($username) > 50 || strlen($password) > 72) {
                return $this->render('register', [
                    'messages' => 'Input length exceeded',
                    'data' => ['email' => $email, 'username' => $username]
                ]);
            }

            $formData = ['email' => $email, 'username' => $username];

            if (empty($email) || empty($password) || empty($username)) {
                return $this->render('register', ['messages' => 'Wypełnij wszystkie pola', 'data' => $formData]);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->render('register', [
                    'messages' => 'Podany adres kosmicznego ID (e-mail) ma nieprawidłowy format.', 
                    'data' => $formData
                ]);
            }

            if ($password !== $password2) {
                return $this->render('register', ['messages' => 'Hasła nie są identyczne', 'data' => $formData]);
            }

            $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
            if (!preg_match($passwordRegex, $password)) {
                return $this->render('register', [
                    'messages' => 'Hasło musi mieć co najmniej 8 znaków i zawierać co najmniej jedną wielką literę, jedną małą literę i jedną cyfrę.',
                    'data' => $formData
                ]);
            }

            $user = $userRepository->getUserByEmail($email);
            if ($user) {
                return $this->render("register", ["messages" => "Nie można utworzyć konta przy użyciu podanych danych uwierzytelniających.", 'data' => $formData]);
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

    // === METODA POMOCNICZA DO AUDYTU BEZPIECZEŃSTWA (LOGOWANIE BEZ HASEŁ) ===
    private function logFailedLogin(string $email)
    {
        $logFile = __DIR__ . '/../../data/security.log';

        // Tworzymy katalog 'data' jeśli nie istnieje
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP';
        $timestamp = date('Y-m-d H:i:s');

        // Bezpieczny format wpisu - brak pola hasła!
        $logEntry = sprintf(
            "[%s] FAILED LOGIN ATTEMPT - Email: %s - IP: %s%s",
            $timestamp,
            $email,
            $ipAddress,
            PHP_EOL
        );

        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
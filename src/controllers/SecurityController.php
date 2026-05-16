<?php

require_once 'AppController.php';
// Upewnij się, że plik z repozytorium jest zaimportowany, jeśli nie działa automatyczne ładowanie klas:
// require_once __DIR__.'/../repository/UsersRepository.php'; 

class SecurityController extends AppController {

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

        // NAPRAWA BŁĘDU: Tworzymy instancję repozytorium i szukamy użytkownika
        $userRepository = new UsersRepository();
        $user = $userRepository->getUserByEmail($email);
      
        // Teraz zmienna $user już istnieje, więc ten warunek zadziała poprawnie
        if (!$user) {
            return $this->render('login', ['messages' => 'User not found']);
        }

        // Weryfikacja hasła (zakładając, że $user to tablica asocjacyjna zwracana z bazy)
        if (!password_verify($password, $user['password'])) {
            return $this->render('login', ['messages' => 'Wrong password']);
        }

        // Logowanie powiodło się - ustawiamy ciasteczko sesyjne
        setcookie("username", $user['email'], time() + 3600, '/');

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/mission");
        exit(); // Dobra praktyka po przekierowaniu header Location
    }

    public function register() {
       $userRepository = new UsersRepository();

       if ($this->isPost()) {
               $email = trim($_POST['email'] ?? '');
               $password = $_POST['password'] ?? '';
               $password2 = $_POST['password2'] ?? '';
               $username = $_POST['username'] ?? '';

               if (empty($email) || empty($password) || empty($username)) {
                   return $this->render('register', ['messages' => 'Fill all fields']);
               }

               // TODO: Porównać czy password === password2 przed rejestracją
               if ($password !== $password2) {
                   return $this->render('register', ['messages' => 'Passwords do not match']);
               }

               $user = $userRepository->getUserByEmail($email);
               if($user) {
                   return $this->render("register", ["messages" => "User exists"]);
               }

               $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
               $userRepository->createUser($email, $hashedPassword, $username);

               $url = "http://$_SERVER[HTTP_HOST]";
               header("Location: {$url}/login");
               return;
       }

       return $this->render("register");
   }
}
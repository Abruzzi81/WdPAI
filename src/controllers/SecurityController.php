<?php

require_once 'AppController.php';

class SecurityController extends AppController {

    public function login()
    {
        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        // var_dump($email);

        if (empty($email) || empty($password)) {
            return $this->render('login', ['messages' => 'Fill all fields']);
        }

       //TODO get from database user with given email
      
        if (!$user) {
            return $this->render('login', ['messages' => 'User not found']);
        }

        if (!password_verify($password, $user['password'])) {
            return $this->render('login', ['messages' => 'Wrong password']);
        }

        // TODO możemy przechowywać sesje użytkowika lub token
        // setcookie("username", $user['email'], time() + 3600, '/');

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
    }


    
    public function register() {
       $userRepository = new UsersRepository();


       // TOTO
       if ($this->isPost()) {
               $email = trim($_POST['email'] ?? '');
               $password = $_POST['password'] ?? '';
               $password2 = $_POST['password2'] ?? '';
               $username = $_POST['username'] ?? '';


               if (empty($email) || empty($password) || empty($username)) {
                   return $this->render('register', ['messages' => 'Fill all fields']);
               }


               // TODO porównać czy hasło1 równe z hasło2


               // TODO check if user exists
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
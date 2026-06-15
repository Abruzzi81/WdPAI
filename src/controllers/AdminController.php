<?php

require_once 'AppController.php';
// Dołączamy repozytorium, aby móc zapytać o aktualną rolę w bazie
require_once __DIR__ . '/../repositories/UsersRepository.php'; 

class AdminController extends AppController
{
    public function users()
    {
        $this->requireLogin();

        $userRepo = new UsersRepository();
        $playerStats = $userRepo->getUserDetails($_SESSION['user_id']);

        if (!isset($playerStats['role']) || $playerStats['role'] !== 'admin') {
            header("Location: /profile");
            exit();
        }

        // POBIERANIE Z BAZY: Wyciągamy wszystkich użytkowników przez istniejącą metodę
        $allUsers = $userRepo->getUsers();

        $title = "Zarządzanie Załogą";

        // Przekazujemy tablicę $allUsers do widoku pod kluczem 'users'
        $this->render('admin_panel', [
            'title' => $title,
            'users' => $allUsers
        ]);
    }

    public function deleteUser()
    {
        // 1. Zabezpieczenie: Sprawdzamy, czy to żądanie POST i czy wykonuje je admin
        if (!$this->isPost()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Niedozwolona metoda']);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRepo = new UsersRepository();
        $playerStats = $userRepo->getUserDetails($_SESSION['user_id']);

        if (!isset($playerStats['role']) || $playerStats['role'] !== 'admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Brak uprawnień administratora']);
            exit();
        }

        // 2. Pobieramy ID użytkownika do zbanowania
        $userIdToBan = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        // Zabezpieczenie przed zbanowaniem samego siebie
        if ($userIdToBan === (int)$_SESSION['user_id']) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nie możesz zbanować samego siebie!']);
            exit();
        }

        if ($userIdToBan > 0) {
            // Wykorzystujemy stworzoną wcześniej uniwersalną metodę przełączania statusu
            $userRepo->updateUserStatus($userIdToBan, 'banned');

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Kadet został pomyślnie zbanowany.']);
            exit();
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowe ID użytkownika']);
        exit();
    }

    public function restoreUser()
    {
        if (!$this->isPost()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Niedozwolona metoda']);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRepo = new UsersRepository();
        $playerStats = $userRepo->getUserDetails($_SESSION['user_id']);

        if (!isset($playerStats['role']) || $playerStats['role'] !== 'admin') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Brak uprawnień administratora']);
            exit();
        }

        $userIdToRestore = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($userIdToRestore > 0) {
            // Przywracamy domyślny status sieciowy 'offline'
            $userRepo->updateUserStatus($userIdToRestore, 'offline');

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Kadet został pomyślnie przywrócony do służby.']);
            exit();
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Nieprawidłowe ID użytkownika']);
        exit();
    }
}
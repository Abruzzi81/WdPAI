<?php

require_once 'AppController.php';
// Rejestrujemy zunifikowane repozytorium użytkowników i historii
require_once __DIR__ . '/../repositories/UsersRepository.php';

class ProfileController extends AppController
{
    /**
     * Wyświetla profil użytkownika wraz z jego dynamicznymi statystykami i historią misji treningowych
     * Ścieżka: /profile
     */
    public function profile()
    {
        // Strażnik dostępu: tylko zalogowani gracze mają wstęp do profilu
        $this->requireLogin();

        // Upewniamy się, że sesja jest aktywna, aby odczytać ID zalogowanego gracza
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'];
        $usersRepo = new UsersRepository();

        // 1. Pobieramy pełne dane profilowe gracza (punkty, rangę, plik i nazwę awatara)
        $loggedPlayer = $usersRepo->getUserDetails($userId);

        // Jeśli z jakiegoś powodu dane użytkownika nie istnieją w bazie, wylogowujemy go bezpiecznie
        if (!$loggedPlayer) {
            header('Location: /logout');
            exit();
        }

        // 2. Pobieramy dynamiczną historię ostatnich sesji treningowych z nowej tabeli
        $trainingHistory = $usersRepo->getTrainingHistory($userId);

        // 3. Pobieramy łączną liczbę wszystkich treningów bezpośrednio z bazy danych
        $totalTrainings = $usersRepo->getTotalTrainingsCount($userId);

        // Tytuł karty przeglądarki dopasowany do nazwy kadeta
        $title = "Profil kadeta - " . strtoupper($loggedPlayer['username']);

        // 4. Renderujemy widok profilu wstrzykując KOMPLET zmiennych (teraz $totalTrainings bez problemu dotrze do HTML)
        return $this->render("profile", [
            "title" => $title,
            "loggedPlayer" => $loggedPlayer,
            "trainingHistory" => $trainingHistory,
            "totalTrainings" => $totalTrainings 
        ]);
    }
}
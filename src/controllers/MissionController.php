<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/MissionRepository.php';

class MissionController extends AppController {

    public function mission() 
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];
        
        $missionRepo = new MissionRepository();
        $levels = $missionRepo->getMissionLevelsForUser($userId);

        return $this->render('mission', [
            'title' => 'GALACTIC MISSIONS - SEKTORY',
            'levels' => $levels
        ]);
    }

    public function game(int $levelId) 
    {
        $this->requireLogin();
        $missionRepo = new MissionRepository();
        $level = $missionRepo->getLevelDetails($levelId);

        if (!$level) {
            header('Location: /mission');
            exit();
        }

        return $this->render('mission_game', [
            'title' => 'MISJA: ' . strtoupper($level['name']),
            'level' => $level
        ]);
    }

    public function saveMissionResult() 
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        if (!empty($_SESSION['user_id']) && isset($data['level_id']) && isset($data['status'])) {
            if ($data['status'] === 'victory') {
                $userId = $_SESSION['user_id'];
                $levelId = (int)$data['level_id'];
                
                $missionRepo = new MissionRepository();
                $level = $missionRepo->getLevelDetails($levelId);
                
                if (!$level) {
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Sektor nie istnieje.']);
                    exit();
                }
                
                // ZMIANA: Nagrodę pobieramy bezpośednio z kolumny w bazie danych
                $reward = (int)$level['reward'];
                
                try {
                    $missionRepo->completeMission($userId, $levelId, $reward);
                    
                    echo json_encode([
                        'status' => 'success', 
                        'reward' => $reward
                    ]);
                    exit();
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error', 
                        'message' => 'Błąd zapisu rejestru misji.'
                    ]);
                    exit();
                }
            }
        }

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowe żądanie.']);
        exit();
    }
}
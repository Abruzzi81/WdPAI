<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repositories/HangarRepository.php';

class HangarController extends AppController
{

    public function hangar()
    {
        $this->requireLogin();
        $userId = $_SESSION['user_id'];

        $hangarRepo = new HangarRepository();

        // Pobieramy listę awatarów (metoda render z AppController automatycznie dorzuci $loggedPlayer)
        $avatars = $hangarRepo->getAllAvatars($userId);

        return $this->render('hangar', [
            'title' => 'Galactic Hangar',
            'avatars' => $avatars
        ]);
    }
    public function equipAvatar()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['user_id']) && isset($data['avatar_id'])) {
            $userId = $_SESSION['user_id'];
            $avatarId = (int) $data['avatar_id'];

            $hangarRepo = new HangarRepository();

            // 1. Sprawdzamy, czy użytkownik już jest właścicielem tego awatara
            $avatars = $hangarRepo->getAllAvatars($userId);
            $isOwned = false;
            foreach ($avatars as $av) {
                if ($av['id'] === $avatarId && ($av['is_owned'] || $av['price'] == 0)) {
                    $isOwned = true;
                    break;
                }
            }

            header('Content-Type: application/json');

            // 2. Jeśli nie posiada – inicjujemy proces zakupu
            if (!$isOwned) {
                $purchaseResult = $hangarRepo->purchaseAvatar($userId, $avatarId);
                if ($purchaseResult['status'] === 'error') {
                    echo json_encode(['status' => 'error', 'message' => $purchaseResult['message']]);
                    exit();
                }
                // Jeśli zakup się udał, zapisujemy nowy stan konta, by przekazać go do JS
                $newBalance = $purchaseResult['new_balance'];
            }

            // 3. Skoro gracz już posiada awatar (lub właśnie go kupił) – zakładamy go!
            $success = $hangarRepo->updateEquippedAvatar($userId, $avatarId);

            if ($success) {
                echo json_encode([
                    'status' => 'success',
                    'purchased' => !$isOwned,
                    'new_balance' => $newBalance ?? null
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Moduł zakupiony, ale nie udało się go wyposażyć.']);
            }
            exit();
        }

        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Błąd systemu autoryzacji.']);
        exit();
    }
}
<?php

require_once 'AppController.php';

class ProfileController extends AppController {

    public function profile() {
        $this->requireLogin();

        $title = "Galactic Math Explorer";

        return $this->render("profile", [
            "title" => $title, 
        ]);
    }
}
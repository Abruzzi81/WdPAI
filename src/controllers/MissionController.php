<?php

require_once 'AppController.php';

class MissionController extends AppController {

    public function mission() {
        $this->requireLogin();      // daje dostep tylko dla zalogowanych uzytkownikow

        
        $title = "Galactic Math Explorer";

        return $this->render("mission", [
            "title" => $title, 
        ]);
    }
}
<?php

require_once 'AppController.php';

class HangarController extends AppController {

    public function hangar() {
        $this->requireLogin();

        $title = "Galactic Math Explorer";

        return $this->render("hangar", [
            "title" => $title, 
        ]);
    }
}
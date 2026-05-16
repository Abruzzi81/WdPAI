<?php

require_once 'AppController.php';

class ProfileController extends AppController {

    public function profile() {
        $title = "CENTRUM TRENINGOWE GALAXY";

        return $this->render("profile", [
            "title" => $title, 
        ]);
    }
}
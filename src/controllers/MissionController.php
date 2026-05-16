<?php

require_once 'AppController.php';

class MissionController extends AppController {

    public function mission() {
        $title = "CENTRUM TRENINGOWE GALAXY";

        return $this->render("mission", [
            "title" => $title, 
        ]);
    }
}
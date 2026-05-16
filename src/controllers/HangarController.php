<?php

require_once 'AppController.php';

class HangarController extends AppController {

    public function hangar() {
        $title = "CENTRUM TRENINGOWE GALAXY";

        return $this->render("hangar", [
            "title" => $title, 
        ]);
    }
}
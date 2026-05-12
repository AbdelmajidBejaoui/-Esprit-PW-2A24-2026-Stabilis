<?php

class Pack {
    public $nom;
    public $description;
    public $prix;
    public $image_url;
    public $active;

    public function __construct($nom, $description, $prix, $image_url = null, $active = 1) {
        $this->nom = $nom;
        $this->description = $description;
        $this->prix = floatval($prix);
        $this->image_url = $image_url;
        $this->active = intval($active);
    }
}

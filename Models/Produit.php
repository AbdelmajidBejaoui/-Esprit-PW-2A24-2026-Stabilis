<?php

class Produit {
    public $nom, $prix, $description, $stock, $categorie, $image_url, $promo_prix, $coming_soon;

    public function __construct($nom, $prix, $description, $stock, $categorie, $image_url, $promo_prix = null, $coming_soon = 0) {
        $this->nom = $nom;
        $this->prix = $prix;
        $this->description = $description;
        $this->stock = $stock;
        $this->categorie = $categorie;
        $this->image_url = $image_url;
        $this->promo_prix = $promo_prix;
        $this->coming_soon = (int)$coming_soon;
    }
}

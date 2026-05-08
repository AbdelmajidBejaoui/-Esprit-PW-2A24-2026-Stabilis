<?php

class Commande {
    public $produit_id;
    public $quantite;
    public $prenom;
    public $nom;
    public $email;
    public $telephone;
    public $adresse;
    public $code_postal;
    public $ville;
    public $pays;
    public $notes;
    public $paiement;
    public $statut;
    public $total;

    public function __construct(
        $produit_id,
        $quantite,
        $prenom,
        $nom,
        $email,
        $telephone,
        $adresse,
        $code_postal,
        $ville,
        $pays,
        $notes,
        $paiement,
        $total,
        $statut = 'En attente'
    ) {
        $this->produit_id = intval($produit_id);
        $this->quantite = intval($quantite);
        $this->prenom = $prenom;
        $this->nom = $nom;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->adresse = $adresse;
        $this->code_postal = $code_postal;
        $this->ville = $ville;
        $this->pays = $pays;
        $this->notes = $notes;
        $this->paiement = $paiement;
        $this->statut = $statut;
        $this->total = floatval($total);
    }
}

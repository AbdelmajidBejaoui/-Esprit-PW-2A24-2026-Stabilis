<?php
class User
{
    private $id;
    private $nom;
    private $email;
    private $password;
    private $role;
    private $preference_alimentaire;
    private $date_inscription;
    private $statut_compte;
    private $face_image;
    private $face_descriptor;

    public function __construct(
        $id,
        $nom,
        $email,
        $password,
        $role,
        $preference_alimentaire,
        $date_inscription,
        $statut_compte,
        $face_image = null,
        $face_descriptor = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->preference_alimentaire = $preference_alimentaire;
        $this->date_inscription = $date_inscription;
        $this->statut_compte = $statut_compte;
        $this->face_image = $face_image;
        $this->face_descriptor = $face_descriptor;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getPreferenceAlimentaire()
    {
        return $this->preference_alimentaire;
    }

    public function getDateInscription()
    {
        return $this->date_inscription;
    }

    public function getStatutCompte()
    {
        return $this->statut_compte;
    }

    public function getFaceImage()
    {
        return $this->face_image;
    }

    public function getFaceDescriptor()
    {
        return $this->face_descriptor;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setNom($nom)
    {
        $this->nom = $nom;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function setPreferenceAlimentaire($preference_alimentaire)
    {
        $this->preference_alimentaire = $preference_alimentaire;
    }

    public function setDateInscription($date_inscription)
    {
        $this->date_inscription = $date_inscription;
    }

    public function setStatutCompte($statut_compte)
    {
        $this->statut_compte = $statut_compte;
    }

    public function setFaceImage($face_image)
    {
        $this->face_image = $face_image;
    }

    public function setFaceDescriptor($face_descriptor)
    {
        $this->face_descriptor = $face_descriptor;
    }
}
?>

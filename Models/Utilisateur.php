<?php
class Utilisateur {
    private $id;
    private $nom;
    private $email;
    private $password;
    private $poids;
    private $taille;
    private $age;
    private $sexe;
    private $created_at;

    public function __construct($id,$nom,$email,$password,$poids,$taille,$age,$sexe,$created_at=null){
        $this->id=$id; $this->nom=$nom; $this->email=$email; $this->password=$password;
        $this->poids=$poids; $this->taille=$taille; $this->age=$age; $this->sexe=$sexe;
        $this->created_at=$created_at;
    }
    public function getId()        { return $this->id; }
    public function getNom()       { return $this->nom; }
    public function getEmail()     { return $this->email; }
    public function getPassword()  { return $this->password; }
    public function getPoids()     { return $this->poids; }
    public function getTaille()    { return $this->taille; }
    public function getAge()       { return $this->age; }
    public function getSexe()      { return $this->sexe; }
    public function getCreatedAt() { return $this->created_at; }

    public function setId($v)        { $this->id=$v; }
    public function setNom($v)       { $this->nom=$v; }
    public function setEmail($v)     { $this->email=$v; }
    public function setPassword($v)  { $this->password=$v; }
    public function setPoids($v)     { $this->poids=$v; }
    public function setTaille($v)    { $this->taille=$v; }
    public function setAge($v)       { $this->age=$v; }
    public function setSexe($v)      { $this->sexe=$v; }
}

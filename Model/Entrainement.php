<?php
class Entrainement {
    private $id;
    private $nom;
    private $description;
    private $type_sport;
    private $niveau;
    private $met_value;
    private $is_custom;
    private $user_id;
    private $created_at;

    public function __construct($id,$nom,$description,$type_sport,$niveau,$met_value,$is_custom=0,$user_id=null,$created_at=null){
        $this->id=$id; $this->nom=$nom; $this->description=$description;
        $this->type_sport=$type_sport; $this->niveau=$niveau; $this->met_value=$met_value;
        $this->is_custom=$is_custom; $this->user_id=$user_id; $this->created_at=$created_at;
    }
    public function getId()          { return $this->id; }
    public function getNom()         { return $this->nom; }
    public function getDescription() { return $this->description; }
    public function getTypeSport()   { return $this->type_sport; }
    public function getNiveau()      { return $this->niveau; }
    public function getMetValue()    { return $this->met_value; }
    public function getIsCustom()    { return $this->is_custom; }
    public function getUserId()      { return $this->user_id; }
    public function getCreatedAt()   { return $this->created_at; }

    public function setId($v)          { $this->id=$v; }
    public function setNom($v)         { $this->nom=$v; }
    public function setDescription($v) { $this->description=$v; }
    public function setTypeSport($v)   { $this->type_sport=$v; }
    public function setNiveau($v)      { $this->niveau=$v; }
    public function setMetValue($v)    { $this->met_value=$v; }
    public function setIsCustom($v)    { $this->is_custom=$v; }
    public function setUserId($v)      { $this->user_id=$v; }

    /** Calories auto-calculées : MET × poids(kg) × durée(h) */
    public static function calculerCalories(float $met, float $poids_kg, int $duree_min): float {
        return round($met * $poids_kg * ($duree_min / 60), 1);
    }
}

<?php
require_once __DIR__ . '/../Service/CalorieService.php';
require_once __DIR__ . '/../Service/EntityHelper.php';

/**
 * Seance — Entité support : enregistrement d'une séance complétée
 *
 * Entité support qui lie un utilisateur à un entraînement.
 * Les calculs caloriques sont délégués au CalorieService.
 */
class Seance
{
    private $id,$utilisateur_id,$entrainement_id,$duree_minutes,$calories,$intensite,$fc_moyenne,$notes,$completed_at;

    public function __construct($id,$utilisateur_id,$entrainement_id,$duree_minutes,$calories,$intensite='moderee',$fc_moyenne=null,$notes=null,$completed_at=null)
    {
        $this->id=$id; $this->utilisateur_id=$utilisateur_id; $this->entrainement_id=$entrainement_id;
        $this->duree_minutes=$duree_minutes; $this->calories=$calories; $this->intensite=$intensite;
        $this->fc_moyenne=$fc_moyenne; $this->notes=$notes; $this->completed_at=$completed_at;
    }

    public function getId()             { return $this->id; }
    public function getUtilisateurId()  { return $this->utilisateur_id; }
    public function getEntrainementId() { return $this->entrainement_id; }
    public function getDureeMinutes()   { return $this->duree_minutes; }
    public function getCalories()       { return $this->calories; }
    public function getIntensite()      { return $this->intensite; }
    public function getFcMoyenne()      { return $this->fc_moyenne; }
    public function getNotes()          { return $this->notes; }
    public function getCompletedAt()    { return $this->completed_at; }

    public function setId($v)           { $this->id=$v; }
    public function setDureeMinutes($v) { $this->duree_minutes=$v; }
    public function setCalories($v)     { $this->calories=$v; }
    public function setIntensite($v)    { $this->intensite=$v; }
    public function setFcMoyenne($v)    { $this->fc_moyenne=$v; }
    public function setNotes($v)        { $this->notes=$v; }

    /**
     * Calcul calorique délégué au CalorieService avec prise en compte de l'intensité.
     */
    public function calculerCaloriesAvecMET(float $met, float $poids_kg): float
    {
        return CalorieService::parDuree($met, $poids_kg, $this->duree_minutes, $this->intensite);
    }

    public function getIntensite_label(): string { return EntityHelper::intensite($this->intensite); }
}

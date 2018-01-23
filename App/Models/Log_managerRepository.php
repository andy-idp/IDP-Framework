<?php

namespace App\Models;

use \PDO;
use Core\Repository;

class Log_managerRepository extends Repository
{
    public function __construct($application)
    {
        parent::__construct($application, 'log_manager', 'Log_manager');  
    }

    public function deleteExpire()
    {
        // 6 mois
        $date_exp = time() - (60 * 60 * 24 * 180);
        $requete = $this->bdd->query("DELETE FROM `log_manager` WHERE `date` <= " . $date_exp . ";");
    }

    public function getAllLimit($limit = 100)
    {
        $retour = array();
        $requete = $this->bdd->query("SELECT * FROM `log_manager` ORDER BY `date` DESC LIMIT " . $limit . ";");
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultat as $donnees) {
            $retour[] = new Log_manager($donnees);
        }
        return $retour;
    }
}

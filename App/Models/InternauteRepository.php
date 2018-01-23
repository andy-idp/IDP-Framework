<?php

namespace App\Models;

use \PDO;
use Core\Repository;

class InternauteRepository extends Repository
{
    public function __construct($application)
    {
        parent::__construct($application, 'internaute', 'Internaute');        
    }        

    public function getPage($page = 1, $limite = 10, $where = "", $ordre = "id", $sens_ordre = "ASC")
    {
        $index_debut = ($page - 1) * $limite;
        $retour = array();
        $requete = $this->bdd->query("SELECT * FROM `internaute` WHERE `id` > 0" . $where . " ORDER BY `" . $ordre . "` " . $sens_ordre . " LIMIT " . $index_debut . ", " . $limite . ";");
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultat as $donnees) {
            $retour[] = new Internaute($donnees);
        }
        return $retour;
    }

    public function connexion($email, $pass)
    {
        $requete = $this->bdd->prepare("SELECT * FROM `internaute` WHERE email = :email;");
        $requete->execute(array(":email" => $email));
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($resultat) && count($resultat) == 1) {
            if ($resultat[0]['pass'] == $pass) {
                $internaute = new Internaute($resultat[0]);
                if ($internaute->id && $internaute->actif) {
                    $session = $this->application->getSession();
                    $request = $this->application->getRequest();
                    $session->set('session_internaute_id', $resultat[0]['id']);
                    $session->set('session_internaute_ipaddr', $request->server->get('REMOTE_ADDR'));                    
                    return $internaute;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function deconnexion()
    {
        unset($_SESSION['session_internaute_id']);
        unset($_SESSION['session_internaute_ipaddr']);
    }

    public function checkConnexion()
    {
        $session = $this->application->getSession();
        $request = $this->application->getRequest();

        $session_internaute_id     = $session->get('session_internaute_id');
        $session_internaute_ipaddr = $session->get('session_internaute_ipaddr');

        if (!empty($session_internaute_id) && !empty($session_internaute_ipaddr)) {
            if ($request->server->get('REMOTE_ADDR') == $session_internaute_ipaddr) {
                if ($internaute = $this->get('id', $session_internaute_id)) {                
                    return $internaute;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }    
}

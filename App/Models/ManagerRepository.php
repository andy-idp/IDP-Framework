<?php

namespace App\Models;

use \PDO;
use Core\Repository;

class ManagerRepository extends Repository
{    
    public function __construct($application)
    {
        parent::__construct($application, 'manager', 'Manager');  
    }
    
    public function getPage($page = 1, $limite = 10, $where = "", $ordre = "id", $sens_ordre = "ASC")
    {
        $index_debut = ($page - 1) * $limite;
        $retour = array();
        $requete = $this->bdd->query("SELECT * FROM `manager` WHERE `id` > 0" . $where . " ORDER BY `" . $ordre . "` " . $sens_ordre . " LIMIT " . $index_debut . ", " . $limite . ";");
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultat as $donnees) {
            $retour[] = new Manager($donnees);
        }
        return $retour;
    }

    public function connexion($email, $pass)
    {
        $requete = $this->bdd->prepare("SELECT * FROM `manager` WHERE email = :email;");
        $requete->execute(array(":email" => $email));
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($resultat) && count($resultat) == 1) {
            if ($resultat[0]['pass'] == $pass) {
                $administrateur = new Manager($resultat[0]);
                if ($administrateur->id && $administrateur->statut) {
                    $session = $this->application->getSession();
                    $request = $this->application->getRequest();
                    $session->set('session_manager', $resultat[0]['id']);
                    $session->set('session_manager_ipaddr', $request->server->get('REMOTE_ADDR'));
                    $session->set('session_manager_last_access', time());                                     
                    return $administrateur;
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
        $session = $this->application->getSession();
        $session->remove('session_manager');
        $session->remove('session_manager_ipaddr');
        $session->remove('session_manager_last_access');        
    }

    public function checkConnexion()
    {
        $session = $this->application->getSession();
        $request = $this->application->getRequest();

        $session_manager             = $session->get('session_manager');
        $session_manager_ipaddr      = $session->get('session_manager_ipaddr');
        $session_manager_last_access = $session->get('session_manager_last_access');

        if (!empty($session_manager) && !empty($session_manager_ipaddr) && !empty($session_manager_last_access)) {
            if (time() - $session_manager_last_access < 14400) {
                if ($request->server->get('REMOTE_ADDR') == $session_manager_ipaddr) {
                    $session->set('session_manager_last_access', time());
                    if ($administrateur = $this->get('id', $session_manager)) {
                        return $administrateur;
                    } else {
                        return false;
                    }
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

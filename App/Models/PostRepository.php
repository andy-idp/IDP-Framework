<?php

namespace App\Models;

use \PDO;
use Core\Repository;

class PostRepository extends Repository
{
    public function __construct($application)
    {
        parent::__construct($application, 'post', 'Post');        
    }    

    public function getAllPosts($langue, $type)
    {
        $retour = array();
        $requete = $this->bdd->prepare("SELECT * FROM `post` WHERE `actif` = 1 AND `langue` = :langue AND `type` = :type ORDER BY `ordre` ASC;");
        $requete->execute(array(
            ':langue' => $langue,
            ':type' => $type
        ));
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultat as $donnees) {
            $retour[] = new Post($donnees);
        }
        return $retour;
    }

    public function returnMaxOrdre($langue, $type)
    {
        $requete = $this->bdd->prepare("SELECT MAX(`ordre`) AS ordre FROM `post` WHERE `langue` = :langue AND `type` = :type;");
        $requete->execute(array(
            ':langue' => $langue,
            ':type' => $type
        ));
        $resulat = $requete->fetch(PDO::FETCH_ASSOC);
        if (empty($resulat['ordre'])) {
            $resulat['ordre'] = 0;
        }
        return $resulat['ordre'] + 1;
    }

    public function getPage($langue, $type, $page = 1, $limite = 10, $where = "", $ordre = "id", $sens_ordre = "ASC")
    {
        $index_debut = ($page - 1) * $limite;
        $retour = array();
        $requete = $this->bdd->query("SELECT * FROM `post` WHERE `langue` = '" . $langue . "' AND `type` = '" . $type . "'" . $where . " ORDER BY `" . $ordre . "` " . $sens_ordre . " LIMIT " . $index_debut . ", " . $limite . ";");
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultat as $donnees) {
            $retour[] = new Post($donnees);
        }
        return $retour;
    }

    public function totalPost($langue, $type)
    {
        $requete = $this->bdd->prepare("SELECT COUNT(*) AS total FROM `post` WHERE `langue` = :langue AND `type` = :type;");
        $requete->execute(array(
            ':langue' => $langue,
            ':type' => $type
        ));
        $resultat = $requete->fetch(PDO::FETCH_ASSOC);

        return $resultat['total'];
    }

    public function ordreRemise($post, $langue, $type)
    {
        $requete = $this->bdd->query("SELECT * FROM `post` WHERE `ordre` >= " . $post->ordre . " AND `id` != " . $post->id . " AND `langue` = '" . $langue . "' AND `type` = '" . $type . "' ORDER BY `ordre` ASC;");
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        $ordre = $post->ordre + 1;
        foreach ($resultat as $donnees) {
            $post = new Post($donnees);
            $post->ordre = $ordre;
            $this->update($post);
            $ordre++;
        }
    }


    /* Post Images */
    public function returnMaxOrdreImage($id)
    {
        $requete = $this->bdd->query("SELECT MAX(`ordre`) AS ordre FROM `post_image` WHERE `id_post` = " . $id . ";");
        $resulat = $requete->fetch(PDO::FETCH_ASSOC);
        if (empty($resulat['ordre'])) {
            $resulat['ordre'] = 0;
        }
        return $resulat['ordre'] + 1;
    }

    public function insertImage(Post_image $post_image)
    {
        $requete = $this->bdd->prepare("INSERT INTO `post_image` (`id_post`, `fichier`, `legende`, `ordre`) VALUES (:id_post, :fichier, :legende, :ordre);");
        $requete->execute(array(
            ':id_post' => $post_image->id_post,
            ':fichier' => $post_image->fichier,
            ':legende' => $post_image->legende,
            ':ordre'   => $post_image->ordre
        ));
    }

    public function updateImage(Post_image $post_image)
    {        
        $requete = $this->bdd->prepare("UPDATE `post_image` SET `ordre` = :ordre WHERE `id` = :id;");
        $requete->execute(array(
            ':id'    => $post_image->id,
            ':ordre' => $post_image->ordre
        ));
    }

    public function getImage($id)
    {
        $requete = $this->bdd->prepare("SELECT * FROM `post_image` WHERE `id` = :id;");
        $requete->execute(array(
            ':id' => $id
        ));
        $donnees = $requete->fetchAll(PDO::FETCH_ASSOC);
        if (count($donnees) == 1) {
            return new Post_image($donnees[0]);
        } else {
            return false;
        }
    }

    public function getAllImage($id)
    {
        $retour = array();
        $requete = $this->bdd->prepare("SELECT * FROM `post_image` WHERE `id_post` = :id ORDER BY `ordre` ASC;");
        $requete->execute(array(
            ':id' => $id
        ));
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultat as $donnees) {
            $retour[] = new Post_image($donnees);
        }
        return $retour;
    }

    public function deleteImage($id)
    {
        $requete = $this->bdd->prepare("DELETE FROM `post_image` WHERE `id` = :id;");
        $requete->execute(array(
            ':id' => $id
        ));
    }

    public function deleteAllImage($id)
    {
        $requete = $this->bdd->prepare("DELETE FROM `post_image` WHERE `id_post` = :id;");
        $requete->execute(array(
            ':id' => $id
        ));
    }


    /* Post Documents */
    public function returnMaxOrdreDocument($id)
    {
        $requete = $this->bdd->query("SELECT MAX(`ordre`) AS ordre FROM `post_document` WHERE `id_post` = " . $id . ";");
        $resulat = $requete->fetch(PDO::FETCH_ASSOC);
        if (empty($resulat['ordre'])) {
            $resulat['ordre'] = 0;
        }
        return $resulat['ordre'] + 1;
    }

    public function insertDocument(Post_document $post_document)
    {
        $requete = $this->bdd->prepare("INSERT INTO `post_document` (`id_post`, `fichier`, `legende`, `ordre`) VALUES (:id_post, :fichier, :legende, :ordre);");
        $requete->execute(array(
            ':id_post' => $post_document->id_post,
            ':fichier' => $post_document->fichier,
            ':legende' => $post_document->legende,
            ':ordre'   => $post_document->ordre
        ));
    }

    public function updateDocument(Post_document $post_document)
    {
        $requete = $this->bdd->prepare("UPDATE `post_document` SET `ordre` = :ordre WHERE `id` = :id;");
        $requete->execute(array(
            ':id'    => $post_document->id,
            ':ordre' => $post_document->ordre
        ));
    }

    public function getDocument($id)
    {
        $requete = $this->bdd->prepare("SELECT * FROM `post_document` WHERE `id` = :id;");
        $requete->execute(array(
            ':id' => $id
        ));
        $donnees = $requete->fetchAll(PDO::FETCH_ASSOC);
        if (count($donnees) == 1) {
            return new Post_document($donnees[0]);
        } else {
            return false;
        }
    }

    public function getAllDocument($id)
    {
        $retour = array();
        $requete = $this->bdd->prepare("SELECT * FROM `post_document` WHERE `id_post` = :id ORDER BY `ordre` ASC;");
        $requete->execute(array(
            ':id' => $id
        ));
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultat as $donnees) {
            $retour[] = new Post_document($donnees);
        }
        return $retour;
    }

    public function deleteDocument($id)
    {
        $requete = $this->bdd->prepare("DELETE FROM `post_document` WHERE `id` = :id;");
        $requete->execute(array(
            ':id' => $id
        ));
    }

    public function deleteAllDocument($id)
    {
        $requete = $this->bdd->prepare("DELETE FROM `post_document` WHERE `id_post` = :id;");
        $requete->execute(array(
            ':id' => $id
        ));
    }

    
    /* Post option */
    public function getOption($id, $libelle)
    {
        $requete = $this->bdd->prepare("SELECT * FROM `post_option` WHERE `id_post` = :id AND `libelle` = :libelle;");
        $requete->execute(array(
            ':id' => $id,
            ':libelle' => $libelle
        ));
        $donnees = $requete->fetchAll(PDO::FETCH_ASSOC);
        if (count($donnees) == 1) {
            return $donnees[0]['valeur'];
        } else {
            return null;
        }
    }

    public function getAllOption($id)
    {
        $retour = array();
        $requete = $this->bdd->prepare("SELECT * FROM `post_option` WHERE `id_post` = :id;");
        $requete->execute(array(
            ':id' => $id
        ));
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultat as $donnees) {
            $retour[$donnees['libelle']] = $donnees['valeur'];
        }
        return $retour;
    }

    public function setOption($id, $libelle, $valeur) {
        $option_tmp = $this->getOption($id, $libelle);
        if (isset($option_tmp)) {
            $requete = $this->bdd->prepare('UPDATE `post_option` SET `valeur` = :valeur WHERE `id_post` = :id AND `libelle` = :libelle;');
            $requete->execute(array(
                ':id' => $id,
                ':libelle' => $libelle,
                ':valeur' => $valeur
            ));
        } else {
            $requete = $this->bdd->prepare('INSERT INTO `post_option` (`id_post`, `libelle`, `valeur`) VALUES(:id, :libelle, :valeur);');
            $requete->execute(array(
                ':id' => $id,
                ':libelle' => $libelle,
                ':valeur' => $valeur
            ));   
        }
    }
}

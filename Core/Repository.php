<?php

namespace Core;

use \PDO;

abstract class Repository
{
    /**
     *
     * @var Application $application
     * @access protected
     */
    protected $application;

    /**
     * @var PDO $bdd
     */
    protected $bdd;

    /**
     * @var string $table_name
     */
    protected $table_name;

    /**
     * @var string $class_name
     */
    protected $class_name;

    /**
     * @param Application $application
     */
    public function __construct(Application $application, $table, $class_name)
    {
        $this->application = $application;        
        $this->bdd = $application::$bdd;
        $this->table_name = $table;
        $this->class_name = $class_name;
    }

    /**
     * Insertion
     *
     * @param $object
     */
    public function insert($object)
    {
        if (!empty($object)) {
            $sql_insert  = '(';
            $sql_value   = '(';
            $sql_execute = array();
            $first_data  = true;

            foreach ($object as $key => $value) {
                if ($key != 'id') {
                    if ($first_data) {
                        $sql_insert .= '`' . $key . '`';
                        $sql_value  .= ':' . $key;
                    } else {
                        $sql_insert .= ', `' . $key . '`';
                        $sql_value  .= ', :' . $key;
                    }
                    $sql_execute[':' . $key] = $value;
                    $first_data = false;
                }
            }

            $sql_insert .= ')';
            $sql_value  .= ')';
    
            $requete = $this->bdd->prepare('INSERT INTO `' . $this->table_name . '` ' . $sql_insert . ' VALUES ' . $sql_value . ';');
            $requete->execute($sql_execute);
        } else {
            trigger_error('Insert ' . $this->class_name . ' object is not possible with the empty object !', E_USER_ERROR);
        }        
    }

    /**
     * Suppression
     *
     * @param int $id Identifiant unique
     */
    public function delete($objet)
    {
        if (!empty($objet->id)) {
            $requete = $this->bdd->prepare('DELETE FROM `' . $this->table_name . '` WHERE `id` = :id;');
            $requete->execute(array(':id' => $objet->id ));
        } else {
            trigger_error('Delete ' . $this->class_name . ' object is not possible because the object is null !', E_USER_ERROR);
        }
    }

    /**
     * Modification
     *
     * @param $object
     */
    public function update($object)
    {
        if (!empty($object)) {
            $sql_update  = '';
            $sql_execute = array();
            $first_data  = true;

            foreach ($object as $key => $value) {
                if ($key != 'id') {
                    if ($first_data) {
                        $sql_update .= '`' . $key . '` = :' . $key;
                    } else {
                        $sql_update .= ', `' . $key . '` = :' . $key;
                    }
                    $first_data = false;
                }
                $sql_execute[':' . $key] = $value;
            }

            $requete = $this->bdd->prepare('UPDATE `' . $this->table_name . '` SET ' . $sql_update . ' WHERE `id` = :id;');
            $requete->execute($sql_execute);
        } else {
            trigger_error('Update ' . $this->class_name . ' object is not possible with the empty object !', E_USER_ERROR);
        }
    }

    /**
     * Return an entry
     *
     * @param string $column
     * @param mixed $value
     */
    public function get($column, $value)
    {
        $requete = $this->bdd->prepare('SELECT * FROM `' . $this->table_name . '` WHERE `' . $column . '` = :value;');
        $requete->execute(array(':value' => $value));
        $donnees = $requete->fetchAll(PDO::FETCH_ASSOC);
        if (count($donnees) == 1) {
            $class_name = '\\App\\Models\\' . $this->class_name;
            return new $class_name($donnees[0]);
        } else {
            return false;
        }
    }

    /**
     * Return all entries    
     */
    public function getAll()
    {
        $retour   = array();
        $requete  = $this->bdd->query('SELECT * FROM `' . $this->table_name . '`;');
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        foreach ($resultat as $donnees) {
            $class_name = '\\App\\Models\\' . $this->class_name;
            $retour[]   = new $class_name($donnees);
        }
        return $retour;
    }


    /**
     * Returns the number of records in bdd   
     */
    public function total()
    {        
        $requete = $this->bdd->prepare('SELECT COUNT(*) AS total FROM `' . $this->table_name . '`;');
        $requete->execute();
        $resultat = $requete->fetch(PDO::FETCH_ASSOC);
        return (int) $resultat['total'];
    }
}

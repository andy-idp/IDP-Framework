<?php

namespace App\Models;

use Core\Model;

class Internaute extends Model
{

    /**
     * @var int $id
     * @access public
     */
    public $id;

    /**
     * @var varchar $nom
     * @access public
     */
    public $nom;

    /**
     * @var varchar $prenom
     * @access public
     */
    public $prenom;

    /**
     * @var varchar $email
     * @access public
     */
    public $email;

    /**
     * @var varchar $pass
     * @access public
     */
    public $pass;

    /**
     * @var tinyint $actif
     * @access public
     */
    public $actif;

    /*
     * Formatage avant affichage
     *
     * @return string
     * @access public
     */

    public function __tostring()
    {
        return $this->prenom . " " . $this->nom;
    }
}

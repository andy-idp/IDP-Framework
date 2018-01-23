<?php

namespace App\Models;

use Core\Model;

class Ticket extends Model
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
     * @var varchar $telephone
     * @access public
     */
    public $telephone;

    /**
     * @var varchar $email
     * @access public
     */
    public $email;

    /**
     * @var text $demande
     * @access public
     */
    public $demande;

    /**
     * @var float $date
     * @access public
     */
    public $date;

    /*
     * Formatage avant affichage
     *
     * @return string
     * @access public
     */

    public function __tostring()
    {
        return "";
    }
}

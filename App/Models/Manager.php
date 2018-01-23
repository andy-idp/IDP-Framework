<?php

namespace App\Models;

use Core\Model;

class Manager extends Model
{
    /**
     * @var int $id
     * @access public
     */
    public $id;
    
    /**
     * @var string $nom
     * @access public
     */
    public $nom;
    
    /**
     * @var string $prenom
     * @access public
     */
    public $prenom;
    
    /**
     * @var string $email
     * @access public
     */
    public $email;
    
    /**
     * @var int $pass
     * @access public
     */
    public $pass;
    
    /**
     * @var boolean $statut
     * @access public
     */
    public $statut;
    
    /**
     * @var string $telephone
     * @access public
     */
    public $telephone;
    
    /**
     * @var string $portable
     * @access public
     */
    public $portable;
    
    /**
     * @var string $description
     * @access public
     */
    public $description;
    
    /*
     * Formatage avant affichage
     * 
     * @return string
     * @access public
     */
    public function __tostring()
    {
        return $this->nom." ".$this->prenom;
    }
}

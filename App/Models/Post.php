<?php

namespace App\Models;

use Core\Model;

class Post extends Model
{

    /**
     * @var int $id
     * @access public
     */
    public $id;

    /**
     * @var varchar $titre
     * @access public
     */
    public $titre;

    /**
     * @var varchar $sous_titre
     * @access public
     */
    public $sous_titre;

    /**
     * @var int $date
     * @access public
     */
    public $date;

    /**
     * @var int $ordre
     * @access public
     */
    public $ordre;

    /**
     * @var tinyint $actif
     * @access public
     */
    public $actif;

    /**
     * @var longtext $texte
     * @access public
     */
    public $texte;
    
    /**
     * @var varchar $langue
     * @access public
     */
    public $langue;
    
    /**
     * @var varchar $type
     * @access public
     */
    public $type;

    /*
     * Formatage avant affichage
     *
     * @return string
     * @access public
     */
    
    public function __tostring()
    {
        return $this->titre;
    }
}

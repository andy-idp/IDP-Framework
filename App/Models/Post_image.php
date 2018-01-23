<?php

namespace App\Models;

use Core\Model;

class Post_image extends Model
{

    /**
     * @var int $id
     * @access public
     */
    public $id;

    /**
     * @var int $id_post
     * @access public
     */
    public $id_post;

    /**
     * @var varchar $fichier
     * @access public
     */
    public $fichier;

    /**
     * @var varchar $legende
     * @access public
     */
    public $legende;

    /**
     * @var int $ordre
     * @access public
     */
    public $ordre;

    /*
     * Formatage avant affichage
     *
     * @return string
     * @access public
     */

    public function __tostring()
    {
    }
}

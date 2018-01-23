<?php

namespace App\Models;

use Core\Model;

class Log_manager extends Model
{

    /**
     * @var int $id
     * @access public
     */
    public $id;

    /**
     * @var int $date
     * @access public
     */
    public $date;

    /**
     * @var varchar $qui
     * @access public
     */
    public $qui;

    /**
     * @var text $action
     * @access public
     */
    public $action;

    /*
     * Formatage avant affichage
     *
     * @return string
     * @access public
     */

    public function __tostring()
    {
        return "<strong>" . date("d/m/Y H:i:s", $this->date) . "</strong> - <i>" . $this->qui . "</i> : " . $this->action;
    }
}

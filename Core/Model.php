<?php
namespace Core;

abstract class Model
{
    /**
     * @var array $donnees
     * @access public
     * @return void
     */
    public function __construct($donnees = array())
    {
        $this->hydrate($donnees);
    }
    
    /**
     * @var array $donnees
     * @access public
     * @return void
     */
    public function hydrate($donnees)
    {
        foreach ($donnees as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            return "";
        }
    }
    
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}

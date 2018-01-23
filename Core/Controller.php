<?php

namespace Core;

abstract class Controller
{
    /**
     *
     * @var Application $application
     * @access protected
     */
    protected $application;

    /**
     * @var array $datas
     * @access public
     */
    public $datas = array();

    /**
     * @param Application $application
     * @access public
     * @return void
     */
    public function __construct(Application $application, $datas = array())
    {
        $this->application = $application;
        $this->datas       = $datas;
    }

    /**
     * @param type $variable
     * @access public
     * @return string
     */
    public function __get($variable)
    {
        if (property_exists($this, $variable)) {
            return $this->$variable;
        } else {
            return "";
        }
    }
}

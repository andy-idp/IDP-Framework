<?php

namespace Core;

use Symfony\Component\HttpFoundation\Request;

class Config
{
    /**
     * @var string
     * @access public
     */
    public $url;

    /**
     * @var string
     * @access public
     */
    public $name;

    /**
     * @var string
     * @access public
     */
    public $mail_contact;

    /**
     * @var string
     * @access public
     */
    public $host_bdd;

    /**
     * @var string
     * @access public
     */
    public $user_bdd;

    /**
     * @var string
     * @access public
     */
    public $pass_bdd;

    /**
     * @var string
     * @access public
     */
    public $base_bdd;

    /**
     * @var string
     * @access public
     */
    public $date_timezone;

    /**
     * @var string
     * @access public
     */
    public $google_analytics;

    /**
     * @var string
     * @access public
     */
    public $google_analytics_old;

    /**
     * @var array
     * @access public
     */
    public $languages = array();

    /**
     * @var string
     * @access public
     */
    public $hash_key;

    /**
     * @var boolean
     * @access public
     */
    public $cache;

    /**
     * SwiftMailer
     */
    public $swift_actif;
    public $swift_smtp;
    public $swift_port;
    public $swift_login;
    public $swift_pass;
    
    /**
     * @var string
     * @access public
     */
    public $recaptcha_public_key;
    
    /**
     * @var string
     * @access public
     */
    public $recaptcha_secret_key;

    /**
     * @var string
     * @access public
     */
    public $version;


    /**
     * @access public
     * @return void
     */
    public function __construct(Request $request)
    {
        $xml = new \DOMDocument;
        if (is_file(__DIR__ . '/../App/config/app.xml')) {
            $xml->load(__DIR__ . '/../App/config/app.xml');
        } else {
            trigger_error("Error loading app.xml!", E_USER_ERROR);
            exit();
        }

        $elements = $xml->getElementsByTagName('parameter');

        // Browsing all the parameters
        foreach ($elements as $element) {
            $variable = $element->getAttribute('var');
            if (property_exists($this, $variable)) {
                if ($variable == "languages") {
                    foreach (explode(',', $element->getAttribute('value')) as $value) {
                        $this->languages[] = $value;
                    }
                } elseif ($variable == "url") {
                    $urls = explode(',', \str_replace(array('http://', 'https://'), '', $element->getAttribute('value')));
                    if (!empty($urls) && in_array($request->server->get('HTTP_HOST'), $urls)) {
                        if ((!empty($request->server->get('HTTPS')) && $request->server->get('HTTPS') !== 'off') || $request->server->get('SERVER_PORT') == 443) {
                            $this->$variable = "https://" . $request->server->get('HTTP_HOST');
                        } else {
                            $this->$variable = "http://" . $request->server->get('HTTP_HOST');
                        }                        
                    } else {
                        trigger_error("The url requested is invalid or incorrect!", E_USER_ERROR);
                        exit();
                    }
                } else {
                    $this->$variable = $element->getAttribute('value');
                }
            }
        }
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
            return null;
        }
    }
}
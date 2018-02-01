<?php

namespace Core;

use \PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class Application
{
    /**
     * @var array $variables_url
     * @access private
     */
    private $variables_url = array();
    
    /**
     * @var array $variables_get
     * @access private
     */
    private $variables_get = array();

    /**
     * @var array $translation
     * @access private
     */
    private $translation = array();

    /**
     * @var Twig_Loader_Filesystem $twig_loader
     * @access protected
     */
    private $twig_loader;

    /**
     * @var Twig_Environment $twig
     * @access protected
     */
    private $twig;

    /**
     * @var Swift_Mailer $swiftmailer
     * @access protected
     */
    private $swiftmailer;

    /**
     * @var Request
     * @access protected
     */
    private $request;
    
    /**
     * @var Response
     * @access protected
     */
    private $response;    

    /**
     * @var Session
     * @access protected
     */
    private $session;

    /**
     * @var PDO $bdd
     * @static
     */
    public static $bdd = null;

    /**
     * @var Config $config
     * @access public
     */
    public $config;

    /**
     * @var string $language
     * @access public
     */
    public $language;

    /**
     * @var string $controller
     * @access public
     */
    public $controller;

    /**
     * @var string $action
     * @access public
     */
    public $action;
     

    /**
     * @access public
     * @return void
     */
    public function __construct(Request $request)
    {
        // Create the HTTPFoundation\Request object for all application
        $this->request = $request;
        
        // Create the HTTPFoundation\Response object for all application
        $this->response = new Response();
        $this->response->setCharset('UTF-8');
        $this->response->setProtocolVersion('1.1');

        // Session
        $this->session = new Session();
        $this->session->start();        

        // Construct the config application with xml file
        $this->config = new Config($this->request);
        
        // Timezone
        if ($this->config->date_timezone) {
            date_default_timezone_set($this->config->date_timezone);
        } else {
            date_default_timezone_set('Europe/Paris');
        }
        
        // BDD instance
        if (!empty($this->config->base_bdd)) {
            if (!isset(self::$bdd)) {
                try {
                    self::$bdd = new \PDO("mysql:host=" . $this->config->host_bdd . ";dbname=" . $this->config->base_bdd . "", $this->config->user_bdd, $this->config->pass_bdd);
                    self::$bdd->exec("SET CHARACTER SET utf8");
                    self::$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (Exception $e) {
                    trigger_error("Error connecting to the database!", E_USER_ERROR);
                    exit();
                }
            }
        }

        // Parse URL
        $this->checkUrl();

        // SwiftMailer
        if ($this->config->swift_actif) {
            $transport = \Swift_SmtpTransport::newInstance($this->config->swift_smtp, $this->config->swift_port)
                    ->setAuthMode("login")
                    ->setUsername($this->config->swift_login)
                    ->setPassword($this->config->swift_pass);
            $this->swiftmailer = \Swift_Mailer::newInstance($transport);
        }

        // Twig
        $this->twig_loader = new \Twig_Loader_Filesystem([
            __DIR__ . "/../App/templates",
            __DIR__ . "/../App/templates/frontend",
            __DIR__ . "/../App/templates/mails",
            __DIR__ . "/../App/templates/manager",
        ]);
        $this->twig = new \Twig_Environment($this->twig_loader, array(
            'cache' => ($this->config->cache) ? __DIR__ . "/../cache" : false
        ));
        // Add Global variable for Twig
        $this->twig->addGlobal("WEB_DIR_URL", $this->config->url);
        $this->twig->addGlobal("WEB_URL", $this->getUrl());
        $this->twig->addGlobal("TITLE", $this->config->name);
        $this->twig->addGlobal("MAIL_CONTACT", $this->config->mail_contact);
        $this->twig->addGlobal("GOOGLE_ANALYTICS", $this->config->google_analytics);
        $this->twig->addGlobal("GOOGLE_ANALYTICS_OLD", $this->config->google_analytics_old);
        $this->twig->addGlobal("CURRENT_LANGUAGE", $this->language);
        $this->twig->addGlobal("LANGUAGES", $this->config->languages);
        $this->twig->addGlobal("RECAPTCHA_PUBLIC_KEY", $this->config->recaptcha_public_key);
        $this->twig->addGlobal("RECAPTCHA_SECRET_KEY", $this->config->recaptcha_secret_key);
        $this->twig->addGlobal("TRANSLATION", $this->translation);
        $this->twig->addGlobal("VERSION", $this->config->version);
        // Adding custom extension class
        $this->twig->addExtension(new \Core\TwigExtension());
    }

    /**
     * Stores all the variables of the URL and defines the language before removing it from the variables
     *
     * @access private
     * @return void
     */
    private function checkUrl()
    {        
        if (!empty($this->request->server->get('REQUEST_URI')) && $this->request->server->get('REQUEST_URI') != "/") {
            $url_finale   = $this->request->server->get('REQUEST_URI');
            $position_get = strpos($url_finale, "?");
            if ($position_get) {
                $url_finale = substr($url_finale, 0, $position_get);
            }
            $this->variables_url = explode("/", substr($url_finale, 1));
        }

        // Deleting the language of variables for processing
        if (!empty($this->variables_url) && in_array($this->variables_url[0], $this->config->languages)) {
            $this->language = $this->variables_url[0];
            unset($this->variables_url[0]);
            $this->variables_url = array_values($this->variables_url);
        } else {
            if (!empty($this->config->languages) && !empty($this->config->languages[0])) {
                $this->language = $this->config->languages[0];
            } else {
                trigger_error("Please set a default language in the application configuration file!", E_USER_ERROR);
                exit();
            }
        }

        foreach ($this->config->languages as $language) {
            if (is_file(__DIR__ . "/../App/translations/" . $language . ".php")) {
                require_once(__DIR__ . "/../App/translations/" . $language . ".php");
            }   
        }        
    }

    /**
     * Translate
     *
     * @param string $texte_a_traduire
     * @return string
     */
    public function __($cle_texte_a_traduire, $language = "")
    {
        if (empty($language)) {
            $language = $this->language;
        }
        if (!empty($this->translation[$language][$cle_texte_a_traduire])) {
            return $this->translation[$language][$cle_texte_a_traduire];
        } else {
            return $cle_texte_a_traduire;
        }

        return $this;        
    }

    /**
     *
     * @access public
     * @return string
     */
    public function getUrl()
    {
        $url = "";
        if (count($this->config->languages) > 1) {
            $url .= $this->config->url . "/" . $this->language;
        } else {
            $url .= $this->config->url;
        }
        return $url;
    }

    /**
     * @param string $url
     * @access public
     * @return null
     */
    public function redirection($url = "")
    {
        if (!empty($url)) {
            header("Location: " . $this->getUrl() . "/" . $url);
        } else {
            header("Location: " . $this->getUrl());
        }
        exit();
    }    

    /**
     * Cache delete
     */
    public function viderLeCache()
    {
        $this->DeleteDir(__DIR__ . "/../cache");

        return $this;  
    }

    private function DeleteDir($path, $first = true)
    {
        if ((is_dir($path) === true)) {
            $files = array_diff(scandir($path), array('.', '..'));

            foreach ($files as $file) {
                $this->DeleteDir(realpath($path) . '/' . $file, false);
            }
            if (!$first) {
                return rmdir($path);
            }
        } elseif (is_file($path) === true) {
            if (basename($path) != "index.html") {
                return unlink($path);
            }
        }

        return false;
    }

    /**
     * Return a response with Twig template
     */
    public function render($template, $parameters = array())
    {
        $content = $this->twig->render($template, $parameters);
        $this->response->setContent($content);        
        $this->response->send();

        return $this;  
    }

    /**
     * Add global variable in twig
     */
    public function addGlobalToTwig($name, $value) {
        $this->twig->addGlobal($name, $value); 
        
        return $this;
    }

    /**
     * Return the HttpFoundation Response object
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Return the HttpFoundation Request object
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Return the SwiftMailer object
     */
    public function getSwiftMailer() {
        return $this->swiftmailer;
    }

    /**
     * Return the HttpFoundation Session object
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * Return twig
     */
    public function getTwig() {
        return $this->twig;
    }

    /**
     * Retrun array with error / report message
     */
    public function getMessageTop() {
        $messages = array();
        $messages['error']  = $this->session->getFlashBag()->get('error');
        $messages['report'] = $this->session->getFlashBag()->get('report');

        return $messages;
    }

    /**
     * @access protected
     * @return Controller
     */
    public function launch()
    {
        $xml = new \DOMDocument;
        if (is_file(__DIR__ . '/../App/config/routes.xml')) {
            $xml->load(__DIR__ . '/../App/config/routes.xml');
        } else {
            trigger_error("Error loading routes.xml!", E_USER_ERROR);
            exit();
        }

        $routes = $xml->getElementsByTagName('route');

        foreach ($routes as $route) {
            if ($route->hasAttribute('url') && $route->hasAttribute('controller') && $route->hasAttribute('action')) {
                $pattern = $route->getAttribute('url');
                $this->controller = $route->getAttribute('controller');
                $this->action = $route->getAttribute('action') . "Action";
                if (preg_match("#^" . $pattern . "$#", "/" . implode("/", $this->variables_url), $this->variables_get)) {
                    $cont = "\\App\\Controller\\" . $this->controller;

                    // Deleting the entire reference
                    if (!empty($this->variables_get)) {
                        unset($this->variables_get[0]);
                        $this->variables_get = array_values($this->variables_get);
                    }
 
                    // Data for the controller
                    $datas = array();
                    if ($route->hasAttribute('vars')) {
                        $nom_datas = explode(",", $route->getAttribute("vars"));
                        $cpt = 0;
                        foreach ($nom_datas as $nom) {
                            if (!empty($this->variables_get[$cpt])) {
                                $datas[$nom] = $this->variables_get[$cpt];
                            }
                            $cpt++;
                        }
                    }

                    $controleur = new $cont($this, $datas);
                    if (method_exists($controleur, $this->action)) {
                        $action = $this->action;                       
                        $controleur->$action();
                    } else {
                        trigger_error("Error loading action " . $this->action . " !", E_USER_ERROR);
                        exit();
                    }
                    return null;
                    break;
                }
            }
        }

        $this->redirection("404");
    }
}

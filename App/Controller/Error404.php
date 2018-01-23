<?php

namespace App\Controller;

use Core\Controller;
use Core\Application;
use Symfony\Component\HttpFoundation\Response;

class Error404 extends Controller
{
    
    public function __construct(Application $application, $datas = array())
    {
        parent::__construct($application, $datas);
    }

    public function indexAction()
    {
        $response = $this->application->getResponse();
        $response->setStatusCode(Response::HTTP_NOT_FOUND);
        $response->headers->set('Status', '404 not Found');             

        $this->application->render('404.twig');
    }
}

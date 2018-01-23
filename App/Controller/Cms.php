<?php

namespace App\Controller;

use Core\Controller;
use Core\Application;

class Cms extends Controller
{    
    public function __construct(Application $application, $datas = array())
    {
        parent::__construct($application, $datas);
    }

    public function indexAction()
    {
        $session = $this->application->getSession();    
        $session->getFlashBag()->add('report', 'Yeah !');           

        $messages_top = $this->application->getMessageTop();       
        
        $this->application->render('home.twig', ['messages_top' => $messages_top]);
    }

    public function mentionsLegalesAction()
    {
        $this->application->render('mentions-legales.twig');
    }

    // Ajax example
    /*
    public function ajaxAction()
    {
        $request = $this->application->getRequest();
        if ($request->isMethod('POST')) {
            $response = $this->application->getResponse();            
            $response->setContent(\json_encode(array(
                'data' => ($request->request->get('data')) ? $request->request->get('data') : 0,
                'nom' => ($request->request->get('nom')) ? $request->request->get('nom') : '',
                'prenom' => ($request->request->get('prenom')) ? $request->request->get('prenom') : '',
                'texte' => ($request->request->get('texte')) ? $request->request->get('texte') : ''
            )));
            $response->headers->set('Content-Type', 'application/json');
            $response->send();
        }
    }*/
}

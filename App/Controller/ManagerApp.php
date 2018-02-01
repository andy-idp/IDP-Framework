<?php

namespace App\Controller;

use slugifier;
use Core\Validate;
use Core\Securite;
use App\Models\Post;
use Core\Controller;
use Core\Application;
use App\Models\Ticket;
use App\Models\Manager;
use App\Models\Post_image;
use App\Models\Internaute;
use App\Models\Log_manager;
use App\Models\PostRepository;
use App\Models\TicketRepository;
use App\Models\Post_document;
use App\Models\ManagerRepository;
use App\Models\InternauteRepository;
use App\Models\Log_managerRepository;

class ManagerApp extends Controller
{
    // Manager
    public $manager_repository;
    public $internaute_repository;
    public $log_repository;
    public $post_repository;
    public $ticket_repository;

    // Objects
    public $manager;

    // Public variable
    public $token;

    // Const
    const LIMIT_PER_PAGE = 20;
    const MAX_UPLOAD     = 4194304;

    public function __construct(Application $application, $datas = array())
    {
        parent::__construct($application, $datas);

        // Manager
        $this->manager_repository     = new ManagerRepository($this->application);
        $this->log_repository         = new Log_managerRepository($this->application);
        $this->ticket_repository      = new TicketRepository($this->application);
        $this->internaute_repository  = new InternauteRepository($this->application);
        $this->post_repository        = new PostRepository($this->application);

        // Check the manager connection
        $this->manager = $this->manager_repository->checkConnexion();
        $this->application->addGlobalToTwig('manager', $this->manager);

        // Get the token
        $this->token = Securite::getToken();
    }


    // ---------- Home ---------- //
    public function indexAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        // Get the request
        $request = $this->application->getRequest();

        $ticket = new Ticket();        

        // If post
        if ($request->isMethod('POST')) {
            // Check the token
            if (!Securite::verifToken($request->request->get('token'))) {
                trigger_error('Send a ticket in manger with an invalid token!', E_USER_ERROR);
                exit();
            }

            $session = $this->application->getSession();

            // Post values
            $ticket->hydrate($request->request->get('ticket'));

            $form_error  = false;

            if (empty($ticket->telephone)) {
                $session->getFlashBag()->add('error', '- Merci de renseigner un numéro de téléphone');
                $form_error  = true;
            }
            if (empty($ticket->email)) {
                $session->getFlashBag()->add('error', '- Merci de renseigner un e-mail');
                $form_error  = true;
            }
            if (empty($ticket->demande)) {
                $session->getFlashBag()->add('error', '- Merci de renseigner une demande');
                $form_error  = true;
            }
            if (!empty($ticket->telephone) && !Validate::phone($ticket->telephone)) {
                $session->getFlashBag()->add('error', '- Merci de renseigner un numéro de téléphone valide');
                $form_error  = true;
            }
            if (!empty($ticket->email) && !Validate::email($ticket->email)) {
                $session->getFlashBag()->add('error', '- Merci de renseigner un e-mail valide');
                $form_error  = true;
            }
            if (!$form_error) {               
                $html = $this->application->getTwig()->render('mail-ticket.twig', [
                    'nom_du_site' => $this->application->config->nom,
                    'prenom' => $ticket->prenom,
                    'nom' => $ticket->nom,
                    'telephone' => $ticket->telephone,
                    'email' => $ticket->email,
                    'demande' => nl2br($ticket->demande),
                ]);

                $message = \Swift_Message::newInstance()
                ->setFrom($ticket->email)
                ->setTo('ticket@id-parallele.com')
                ->setSubject('Déclaration d\'un incident')
                ->setBody($html, 'text/html');
                $this->application->getSwiftMailer()->send($message);
                                
                $ticket->date = time();

                $this->ticket_repository->insert($ticket);
                
                $session->getFlashBag()->add('report', 'Votre demande a bien été enregistrée. Vous allez être recontacter dès que possible.');

                $this->application->redirection('manager');
            }
        }

        $this->log_repository->deleteExpire();

        $messages_top = $this->application->getMessageTop();
        $this->application->render('manager-accueil.twig', [
            'messages_top' => $messages_top,
            'token'        => $this->token,
            'current_menu' => 1,
            'ticket'       => $ticket
        ]);
    }


    // ---------- Cache ---------- //
    public function viderLeCacheAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        $this->application->viderLeCache();

        // Insert trace in logs
        $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Vider le cache.')));

        $session = $this->application->getSession();
        $session->getFlashBag()->add('report', 'Le cache a bien été vidé.');

        $this->application->redirection('manager');
    }


    // ---------- Logs ----------//
    public function logsAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        $logs = $this->log_repository->getAllLimit();

        $this->application->render('manager-logs.twig', [
            'current_menu' => 5,
            'logs'         => $logs
        ]);
    }


    // ---------- Connection ---------- //
    public function formConnexionAction()
    {
        if ($this->manager) {
            $this->application->redirection('manager');
        }

        // Get the request
        $request = $this->application->getRequest();

        $email_value = '';

        // If post
        if ($request->isMethod('POST')) {
            // Check the token
            if (!Securite::verifToken($request->request->get('token'))) {
                trigger_error('External connection to the unauthorized manager with an invalid token!', E_USER_ERROR);
                exit();
            }

            $session = $this->application->getSession();

            // Post values
            $email_value = $request->request->get('email');
            $pass_value  = $request->request->get('pass');

            $form_error  = false;
            
            if (empty($email_value) || !Validate::email($email_value) || empty($pass_value)) {
                $session->getFlashBag()->add('error', 'E-mail ou mot de passe incorrect !');
                $form_error = true;
            }
            if (!$form_error) {
                if ($this->manager_repository->connexion($email_value, Securite::hashWithToken($pass_value, $this->application->config->hash_key))) {
                    // Insert trace in logs
                    $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $_POST['email'], 'action' => 'Connexion au manager.')));

                    $this->application->redirection('manager');
                } else {
                    $session->getFlashBag()->add('error', 'E-mail ou mot de passe incorrect !');
                }
            }
        }

        $messages_top = $this->application->getMessageTop();
        $this->application->render('manager-connection.twig', [
            'messages_top'     => $messages_top,
            'token'            => $this->token,
            'footer_connexion' => 1,
            'email_value'      => $email_value
        ]);
    }

    public function deconnexionAction()
    {
        $this->manager_repository->deconnexion();
        $this->application->redirection('manager');
    }

    
    //---------- Manager ----------//
    public function managersAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        // Get the request
        $request = $this->application->getRequest();

        $total     = $this->manager_repository->total();
        $page      = (!empty($request->query->get('page')) && $request->query->get('page') <= ceil($total / self::LIMIT_PER_PAGE)) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        $debut_pagination = ($page - 5 >= 1) ? $page - 5 : 1;
        $fin_pagination = ($page + 5 <= ceil($total / self::LIMIT_PER_PAGE)) ? $page + 5 : ceil($total / self::LIMIT_PER_PAGE);
        
        $managers = $this->manager_repository->getPage($page, self::LIMIT_PER_PAGE, '', $tri, $tri_ordre);

        $messages_top = $this->application->getMessageTop();
        $this->application->render('manager-managers.twig', [
            'messages_top'     => $messages_top,
            'current_menu'     => 4,
            'managers'         => $managers,
            'debut_pagination' => $debut_pagination,
            'fin_pagination'   => $fin_pagination,
            'page'             => $page,
            'tri'              => $tri,
            'tri_ordre'        => $tri_ordre
        ]);
    }

    public function ajouterUnManagerAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        $manager_u = new Manager();

        // Get the request
        $request = $this->application->getRequest();

        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        // If post
        if ($request->isMethod('POST')) {
            // Check the token
            if (!Securite::verifToken($request->request->get('token'))) {
                trigger_error('Adding a new manager with an invalid token!', E_USER_ERROR);
                exit();
            }

            $session = $this->application->getSession();
            $form_error  = false;

            $manager_u->nom    = $request->request->get('nom');
            $manager_u->prenom = $request->request->get('prenom');
            $manager_u->email  = $request->request->get('email');
            $manager_u->statut = (!empty($request->request->get('statut'))) ? 1 : 0;
            $mot_de_passe      = $request->request->get('pass');

            if (empty($manager_u->nom)) {
                $session->getFlashBag()->add('error', 'Un nom est obligatoire !');
                $form_error = true;
            }
            if (empty($manager_u->prenom)) {
                $session->getFlashBag()->add('error', 'Un prénom est obligatoire !');
                $form_error = true;
            }
            if (empty($manager_u->email)) {
                $session->getFlashBag()->add('error', 'Un e-mail est obligatoire !');
                $form_error = true;
            }
            if (empty($mot_de_passe)) {
                $session->getFlashBag()->add('error', 'Un mot de passe est obligatoire !');
                $form_error = true;
            }
            if (!empty($manager_u->email) && !Validate::email($manager_u->email)) {
                $session->getFlashBag()->add('error', 'L\'e-mail n\'est pas valide !');
                $form_error  = true;
            }

            if (!$form_error) {
                // Email exist?
                $manager_tmp = $this->manager_repository->get('email', $manager_u->email);
                if (empty($manager_tmp)) {
                    $manager_u->pass = Securite::hashWithToken($mot_de_passe, $this->application->config->hash_key);
                    
                    $this->manager_repository->insert($manager_u);
    
                    $session->getFlashBag()->add('report', 'Nouveau manager ajouté avec succès.');
    
                    // Insert trace in logs
                    $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Ajout du manager ' . $manager_u->nom . ' ' . $manager_u->prenom)));
    
                    $this->application->redirection('manager/managers?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
                } else {
                    $session->getFlashBag()->add('error', 'Cet e-mail est déjà utilisé !');
                }
            }
        }

        $messages_top = $this->application->getMessageTop();
        $this->application->render('manager-ajouter-un-manager.twig', [
            'messages_top'     => $messages_top,
            'current_menu'     => 4,
            'manager_u'        => $manager_u,
            'page'             => $page,
            'tri'              => $tri,
            'tri_ordre'        => $tri_ordre,
            'token'            => $this->token
        ]);
    }

    public function modifierUnManagerAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        $session = $this->application->getSession();

        // Get the request
        $request = $this->application->getRequest();
        
        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        // Check the manager exist or the manager is not demo
        $manager_u = $this->manager_repository->get('id', $this->datas['id_manager']);
        if (!$manager_u) {
            $session->getFlashBag()->add('error', 'Le manager demandé n\'existe pas !');
            $this->application->redirection('manager/managers?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
        } elseif ($manager_u->id == 1) {
            $session->getFlashBag()->add('error', 'Le manager demandé est celui par défaut et ne peut pas être modifié ou supprimé !');
            $this->application->redirection('manager/managers?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
        }

        // If post
        if ($request->isMethod('POST')) {
            // Check the token
            if (!Securite::verifToken($request->request->get('token'))) {
                trigger_error('Update manager with an invalid token!', E_USER_ERROR);
                exit();
            }

            $form_error  = false;

            $manager_u->nom    = $request->request->get('nom');
            $manager_u->prenom = $request->request->get('prenom');
            $old_email = $manager_u->email;
            $manager_u->email  = $request->request->get('email');
            $manager_u->statut = (!empty($request->request->get('statut'))) ? 1 : 0;

            if (empty($manager_u->nom)) {
                $session->getFlashBag()->add('error', 'Un nom est obligatoire !');
                $form_error = true;
            }
            if (empty($manager_u->prenom)) {
                $session->getFlashBag()->add('error', 'Un prénom est obligatoire !');
                $form_error = true;
            }
            if (empty($manager_u->email)) {
                $session->getFlashBag()->add('error', 'Un e-mail est obligatoire !');
                $form_error = true;
            }
            if (!empty($manager_u->email) && !Validate::email($manager_u->email)) {
                $session->getFlashBag()->add('error', 'L\'e-mail n\'est pas valide !');
                $form_error  = true;
            }

            if (!$form_error) {
                // Email exist?
                $manager_tmp = $this->manager_repository->get('email', $manager_u->email);
                if (empty($manager_tmp) || $manager_tmp->email == $old_email) {
                    if (!empty($request->request->get('pass'))) {
                        $manager_u->pass = Securite::hashWithToken($request->request->get('pass'), $this->application->config->hash_key);
                    }
                    
                    $this->manager_repository->update($manager_u);
    
                    $session->getFlashBag()->add('report', 'Manager modifié avec succès.');
    
                    // Insert trace in logs
                    $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Modification du manager ' . $manager_u->nom . ' ' . $manager_u->prenom)));
    
                    $this->application->redirection('manager/managers?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
                } else {
                    $session->getFlashBag()->add('error', 'Cet e-mail est déjà utilisé !');
                }
            }
        }

        $messages_top = $this->application->getMessageTop();
        $this->application->render('manager-modifier-un-manager.twig', [
            'messages_top'     => $messages_top,
            'current_menu'     => 4,
            'manager_u'        => $manager_u,
            'page'             => $page,
            'tri'              => $tri,
            'tri_ordre'        => $tri_ordre,
            'token'            => $this->token
        ]);
    }

    public function supprimerUnManagerAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        $session = $this->application->getSession();

        // Get the request
        $request = $this->application->getRequest();
        
        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        // Check the manager exist or the manager is not demo
        $manager_u = $this->manager_repository->get('id', $this->datas['id_manager']);
        if (!$manager_u) {
            $session->getFlashBag()->add('error', 'Le manager demandé n\'existe pas !');
        } elseif ($manager_u->id == 1) {
            $session->getFlashBag()->add('error', 'Le manager demandé est celui par défaut et ne peut pas être modifié ou supprimé !');
        } else {
            $this->manager_repository->delete($manager_u);
            $session->getFlashBag()->add('report', 'Manager supprimé avec succès.');
            
            // Insert trace in logs
            $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Suppression du manager ' . $manager_u->nom . ' ' . $manager_u->prenom)));
        }

        $this->application->redirection('manager/managers?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
    }


    //---------- Clients ----------//
    public function clientsAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        // Get the request
        $request = $this->application->getRequest();

        $total     = $this->internaute_repository->total();
        $page      = (!empty($request->query->get('page')) && $request->query->get('page') <= ceil($total / self::LIMIT_PER_PAGE)) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        $debut_pagination = ($page - 5 >= 1) ? $page - 5 : 1;
        $fin_pagination   = ($page + 5 <= ceil($total / self::LIMIT_PER_PAGE)) ? $page + 5 : ceil($total / self::LIMIT_PER_PAGE);
        $fin_pagination   = ($fin_pagination > 0)  ? $fin_pagination : 1;
        
        $clients = $this->internaute_repository->getPage($page, self::LIMIT_PER_PAGE, '', $tri, $tri_ordre);

        $messages_top = $this->application->getMessageTop();
        $this->application->render('manager-clients.twig', [
            'messages_top'     => $messages_top,
            'current_menu'     => 2,
            'clients'          => $clients,
            'debut_pagination' => $debut_pagination,
            'fin_pagination'   => $fin_pagination,
            'page'             => $page,
            'tri'              => $tri,
            'tri_ordre'        => $tri_ordre
        ]);
    }

    public function ajouterUnClientAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        $client = new Internaute();

        // Get the request
        $request = $this->application->getRequest();

        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        // If post
        if ($request->isMethod('POST')) {
            // Check the token
            if (!Securite::verifToken($request->request->get('token'))) {
                trigger_error('Adding a new client with an invalid token!', E_USER_ERROR);
                exit();
            }

            $session = $this->application->getSession();
            $form_error  = false;

            $client->nom    = $request->request->get('nom');
            $client->prenom = $request->request->get('prenom');
            $client->email  = $request->request->get('email');
            $client->actif  = (!empty($request->request->get('actif'))) ? 1 : 0;
            $mot_de_passe   = $request->request->get('pass');

            if (empty($client->nom)) {
                $session->getFlashBag()->add('error', 'Un nom est obligatoire !');
                $form_error = true;
            }
            if (empty($client->prenom)) {
                $session->getFlashBag()->add('error', 'Un prénom est obligatoire !');
                $form_error = true;
            }
            if (empty($client->email)) {
                $session->getFlashBag()->add('error', 'Un e-mail est obligatoire !');
                $form_error = true;
            }
            if (empty($mot_de_passe)) {
                $session->getFlashBag()->add('error', 'Un mot de passe est obligatoire !');
                $form_error = true;
            }
            if (!empty($client->email) && !Validate::email($client->email)) {
                $session->getFlashBag()->add('error', 'L\'e-mail n\'est pas valide !');
                $form_error  = true;
            }

            if (!$form_error) {
                // Email exist?
                $client_tmp = $this->internaute_repository->get('email', $client->email);
                if (empty($client_tmp)) {
                    $client->pass = Securite::hashWithToken($mot_de_passe, $this->application->config->hash_key);
                    
                    $this->internaute_repository->insert($client);
    
                    $session->getFlashBag()->add('report', 'Nouveau client ajouté avec succès.');
    
                    // Insert trace in logs
                    $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Ajout du client ' . $client->nom . ' ' . $client->prenom)));
    
                    $this->application->redirection('manager/clients?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
                } else {
                    $session->getFlashBag()->add('error', 'Cet e-mail est déjà utilisé !');
                }
            }
        }

        $messages_top = $this->application->getMessageTop();
        $this->application->render('manager-ajouter-un-client.twig', [
            'messages_top'     => $messages_top,
            'current_menu'     => 2,
            'client'           => $client,
            'page'             => $page,
            'tri'              => $tri,
            'tri_ordre'        => $tri_ordre,
            'token'            => $this->token
        ]);
    }

    public function modifierUnClientAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        $session = $this->application->getSession();

        // Get the request
        $request = $this->application->getRequest();
        
        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        // Check the manager exist or the manager is not demo
        $client = $this->internaute_repository->get('id', $this->datas['id_internaute']);
        if (!$client) {
            $session->getFlashBag()->add('error', 'Le client demandé n\'existe pas !');
            $this->application->redirection('manager/clients?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
        }

        // If post
        if ($request->isMethod('POST')) {
            // Check the token
            if (!Securite::verifToken($request->request->get('token'))) {
                trigger_error('Update client with an invalid token!', E_USER_ERROR);
                exit();
            }

            $form_error  = false;

            $client->nom    = $request->request->get('nom');
            $client->prenom = $request->request->get('prenom');
            $old_email      = $client->email;
            $client->email  = $request->request->get('email');
            $client->actif  = (!empty($request->request->get('actif'))) ? 1 : 0;

            if (empty($client->nom)) {
                $session->getFlashBag()->add('error', 'Un nom est obligatoire !');
                $form_error = true;
            }
            if (empty($client->prenom)) {
                $session->getFlashBag()->add('error', 'Un prénom est obligatoire !');
                $form_error = true;
            }
            if (empty($client->email)) {
                $session->getFlashBag()->add('error', 'Un e-mail est obligatoire !');
                $form_error = true;
            }
            if (!empty($client->email) && !Validate::email($client->email)) {
                $session->getFlashBag()->add('error', 'L\'e-mail n\'est pas valide !');
                $form_error  = true;
            }

            if (!$form_error) {
                // Email exist?
                $client_tmp = $this->internaute_repository->get('email', $client->email);
                if (empty($client_tmp) || $client_tmp->email == $old_email) {
                    if (!empty($request->request->get('pass'))) {
                        $client->pass = Securite::hashWithToken($request->request->get('pass'), $this->application->config->hash_key);
                    }
                    
                    $this->internaute_repository->update($client);
    
                    $session->getFlashBag()->add('report', 'Client modifié avec succès.');
    
                    // Insert trace in logs
                    $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Modification du client ' . $client->nom . ' ' . $client->prenom)));
    
                    $this->application->redirection('manager/clients?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
                } else {
                    $session->getFlashBag()->add('error', 'Cet e-mail est déjà utilisé !');
                }
            }
        }

        $messages_top = $this->application->getMessageTop();
        $this->application->render('manager-modifier-un-client.twig', [
            'messages_top'     => $messages_top,
            'current_menu'     => 2,
            'client'           => $client,
            'page'             => $page,
            'tri'              => $tri,
            'tri_ordre'        => $tri_ordre,
            'token'            => $this->token
        ]);
    }

    public function supprimerUnClientAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }

        $session = $this->application->getSession();

        // Get the request
        $request = $this->application->getRequest();
        
        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        // Check the manager exist or the manager is not demo
        $client = $this->internaute_repository->get('id', $this->datas['id_internaute']);
        if (!$client) {
            $session->getFlashBag()->add('error', 'Le client demandé n\'existe pas !');
        } else {
            $this->internaute_repository->delete($client);
            $session->getFlashBag()->add('report', 'Client supprimé avec succès.');
            
            // Insert trace in logs
            $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Suppression du client ' . $client->nom . ' ' . $client->prenom)));
        }

        $this->application->redirection('manager/clients?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
    }

    //---------- Posts ----------//
    public function postsAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }
        
        // Get the request
        $request = $this->application->getRequest();
        
        $total     = $this->post_repository->totalPost($this->application->language, $this->datas['type']);
        $page      = (!empty($request->query->get('page')) && $request->query->get('page') <= ceil($total / self::LIMIT_PER_PAGE)) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        $debut_pagination = ($page - 5 >= 1) ? $page - 5 : 1;
        $fin_pagination   = ($page + 5 <= ceil($total / self::LIMIT_PER_PAGE)) ? $page + 5 : ceil($total / self::LIMIT_PER_PAGE);
        $fin_pagination   = ($fin_pagination > 0)  ? $fin_pagination : 1;
        
        $posts = $this->post_repository->getPage($this->application->language, $this->datas['type'], $page, self::LIMIT_PER_PAGE, '', $tri, $tri_ordre);

        $messages_top = $this->application->getMessageTop();

        switch ($this->datas['type']) {
            case 'actualite':
                $current_menu = 3;
                break;            
            default:
                $current_menu = 0;
                break;
        }

        $this->application->render('manager-posts.twig', [
            'messages_top'     => $messages_top,
            'current_menu'     => $current_menu,
            'posts'            => $posts,
            'debut_pagination' => $debut_pagination,
            'fin_pagination'   => $fin_pagination,
            'page'             => $page,
            'tri'              => $tri,
            'tri_ordre'        => $tri_ordre,
            'type'             => $this->datas['type']
        ]);
    }

    public function ajouterUnPostAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }    

        $post = new Post(array('date' => time(), 'ordre' => 1, 'type' => $this->datas['type'], 'langue' => $this->application->language));
        $options = array();
        
        // Get the request
        $request = $this->application->getRequest();

        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        // If post
        if ($request->isMethod('POST')) {
            // Check the token
            if (!Securite::verifToken($request->request->get('token'))) {
                trigger_error('Adding a new post (' . $this->datas['type'] . ') with an invalid token!', E_USER_ERROR);
                exit();
            }

            $session     = $this->application->getSession();
            $form_error  = false;

            if (!empty($request->request->get('date'))) {
                $post->date    = strtotime(substr($request->request->get('date'), 3, 2) . '/' . substr($request->request->get('date'), 0, 2) . '/' . substr($request->request->get('date'), 6, 4));
            } else {
                $post->date    = '';
            }            
            $post->titre       = $request->request->get('titre');
            $post->sous_titre  = $request->request->get('sous_titre');
            $post->texte       = $request->request->get('texte');
            $post->ordre       = $request->request->get('ordre');
            $post->actif       = (!empty($request->request->get('actif'))) ? 1 : 0;            

            if (empty($post->date)) {
                $session->getFlashBag()->add('error', 'Une date est obligatoire !');
                $form_error = true;
            }
            if (empty($post->titre)) {
                $session->getFlashBag()->add('error', 'Un titre est obligatoire !');
                $form_error = true;
            }
            if (empty($post->texte)) {
                $session->getFlashBag()->add('error', 'Un texte est obligatoire !');
                $form_error = true;
            }

            // Options save if error and if post type is correct
            if ($this->datas['type'] == 'actualite') {
                $options['auteur'] = (!empty($request->request->get('auteur'))) ? $request->request->get('auteur') : '';
            }

            if (!$form_error) {                   
                $this->post_repository->insert($post);

                $post->id = Application::$bdd->lastInsertId();

                $this->post_repository->ordreRemise($post, $this->application->language, $this->datas['type']);

                // Options save
                if ($this->datas['type'] == 'actualite') {
                    $this->post_repository->setOption($post->id, 'auteur', $options['auteur']);
                }

                $session->getFlashBag()->add('report', 'Nouveau post ajouté avec succès.');

                // Insert trace in logs
                $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Ajout d\'un post (' . $this->datas['type'] . ')')));

                $this->application->redirection('manager/posts-' . $this->datas['type'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);                
            }
            
        } 

        $messages_top = $this->application->getMessageTop();

        switch ($this->datas['type']) {
            case 'actualite':
                $current_menu = 3;
                break;            
            default:
                $current_menu = 0;
                break;
        }

        $this->application->render('manager-ajouter-un-post.twig', [
            'messages_top'     => $messages_top,
            'current_menu'     => $current_menu,
            'post'             => $post,
            'page'             => $page,
            'tri'              => $tri,
            'tri_ordre'        => $tri_ordre,
            'token'            => $this->token,
            'type'             => $this->datas['type'],
            'options'          => $options
        ]);
    }

    public function modifierUnPostAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }            

        $session = $this->application->getSession();

        // Get the request
        $request = $this->application->getRequest();

        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        $post = $this->post_repository->get('id', $this->datas['id_post']);
        $options = $this->post_repository->getAllOption($post->id);

        if (!$post) {
            $session->getFlashBag()->add('error', 'Le post demandé n\'existe pas !');
            $this->application->redirection('manager/posts-' . $this->datas['type'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);
        }

        $post_images = $this->post_repository->getAllImage($post->id);
        $post_documents = $this->post_repository->getAllDocument($post->id);

        // If post
        if ($request->isMethod('POST')) {
            // Check the token
            if (!Securite::verifToken($request->request->get('token'))) {
                trigger_error('Update a post (' . $this->datas['type'] . ') with an invalid token!', E_USER_ERROR);
                exit();
            }

            $form_error  = false;

            $action = $request->request->get('action');
            if ($action == "update") {
                if (!empty($request->request->get('date'))) {
                    $post->date    = strtotime(substr($request->request->get('date'), 3, 2) . '/' . substr($request->request->get('date'), 0, 2) . '/' . substr($request->request->get('date'), 6, 4));
                }
                $post->titre       = $request->request->get('titre');
                $post->sous_titre  = $request->request->get('sous_titre');
                $post->texte       = $request->request->get('texte');
                $post->ordre       = $request->request->get('ordre');
                $post->actif       = (!empty($request->request->get('actif'))) ? 1 : 0;   
    
                if (empty($post->date)) {
                    $session->getFlashBag()->add('error', 'Une date est obligatoire !');
                    $form_error = true;
                }
                if (empty($post->titre)) {
                    $session->getFlashBag()->add('error', 'Un titre est obligatoire !');
                    $form_error = true;
                }
                if (empty($post->texte)) {
                    $session->getFlashBag()->add('error', 'Un texte est obligatoire !');
                    $form_error = true;
                }

                // Options save if error
                if ($this->datas['type'] == 'actualite') {
                    $options['auteur'] = (!empty($request->request->get('auteur'))) ? $request->request->get('auteur') : $options['auteur'];
                }
    
                if (!$form_error) {
                    $this->post_repository->update($post);
                        
                    $this->post_repository->ordreRemise($post, $this->application->language, $this->datas['type']);

                    // Options save
                    if ($this->datas['type'] == 'actualite') {
                        $this->post_repository->setOption($post->id, 'auteur', $options['auteur']);
                    }
    
                    $session->getFlashBag()->add('report', 'Post modifié avec succès.');
    
                    // Insert trace in logs
                    $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Modification d\'un post (' . $this->datas['type'] . ')')));
    
                    $this->application->redirection('manager/posts-' . $this->datas['type'] . '/modifier-un-post/' . $post->id . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
                }       
            }

            if ($action == "add_image") {                
                if ($request->files->get('fichier')) {
                    if ($request->files->get('fichier')->getMimeType() == "image/jpeg" || $request->files->get('fichier')->getMimeType() == "image/png") {
                        if ($request->files->get('fichier')->getClientSize() < self::MAX_UPLOAD) {
                            if ($request->files->get('fichier')->getMimeType() == "image/jpeg")
                                $fichier = slugifier\slugify(str_replace(array('.jpg', '.JPG', '.jpeg', '.JPEG'), array("", "", "", ""), $request->files->get('fichier')->getClientOriginalName())) . "-" . rand(1, 100) . ".jpg";
                            else
                                $fichier = slugifier\slugify(str_replace(array('.png', '.PNG'), array("", ""), $request->files->get('fichier')->getClientOriginalName())) . "-" . rand(1, 100) . ".png";
                            $request->files->get('fichier')->move(__DIR__ . '/../../web/downloads/posts/', $fichier);
                            $fichier_resize = new \Gumlet\ImageResize(__DIR__ . "/../../web/downloads/posts/" . $fichier);
                            $fichier_resize->crop(110, 110)->save(__DIR__ . "/../../web/downloads/posts/thumb-" . $fichier);

                            if (!empty($request->request->get('fichier_fw')) && !empty($request->request->get('fichier_fh')) && !empty($request->request->get('fichier_w')) && !empty($request->request->get('fichier_h'))) {
                                $fichier_resize->freecrop($request->request->get('fichier_w'), $request->request->get('fichier_h'), $request->request->get('fichier_x'), $request->request->get('fichier_y'))->save(__DIR__ . "/../../web/downloads/posts/crop-" . $fichier);
                                $fichier_resize_final = new \Gumlet\ImageResize(__DIR__ . "/../../web/downloads/posts/crop-" . $fichier);                                
                                $fichier_resize_final->resize($request->request->get('fichier_fw'), $request->request->get('fichier_fh'))->save(__DIR__ . "/../../web/downloads/posts/crop-" . $fichier);
                            }

                            $post_image = new Post_image(array(
                                "id_post" => $post->id,
                                "fichier" => $fichier,
                                "legende" => $_POST['legende'],
                                "ordre" => $this->post_repository->returnMaxOrdreImage($post->id)
                            ));

                            $this->post_repository->insertImage($post_image);

                            $session->getFlashBag()->add('report', 'Image ajoutée.'); 

                            $this->application->redirection('manager/posts-' . $this->datas['type'] . '/modifier-un-post/' . $post->id . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
                        } else {
                            $session->getFlashBag()->add('error', 'L\'image ne doît dépasser 4Mo !'); 
                        } 
                    } else {
                        $session->getFlashBag()->add('error', 'L\'image doit être au format "jpg/jpeg" ou "png" !'); 
                    }
                } else {
                    $session->getFlashBag()->add('error', 'Merci de renseigner une image !'); 
                }
            }

            if ($action == "add_document") {                
                if ($request->files->get('fichier')) {
                    if ($request->files->get('fichier')->getMimeType() == "application/pdf") {
                        if ($request->files->get('fichier')->getClientSize() < self::MAX_UPLOAD) {
                            $fichier = slugifier\slugify(str_replace(array('.pdf', '.PDF'), array("", ""), $request->files->get('fichier')->getClientOriginalName())) . "-" . rand(1, 100) . ".pdf";
                            $request->files->get('fichier')->move(__DIR__ . '/../../web/downloads/posts/', $fichier);

                            $post_document = new Post_document(array(
                                "id_post" => $post->id,
                                "fichier" => $fichier,
                                "legende" => $_POST['legende'],
                                "ordre" => $this->post_repository->returnMaxOrdreDocument($post->id)
                            ));

                            $this->post_repository->insertDocument($post_document);

                            $session->getFlashBag()->add('report', 'Document ajouté.'); 

                            $this->application->redirection('manager/posts-' . $this->datas['type'] . '/modifier-un-post/' . $post->id . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
                        } else {
                            $session->getFlashBag()->add('error', 'Le document ne doît dépasser 4Mo !'); 
                        } 
                    } else {
                        $session->getFlashBag()->add('error', 'Le document doit être au format "pdf" !'); 
                    }
                } else {
                    $session->getFlashBag()->add('error', 'Merci de renseigner un document !'); 
                }
            }
            
        }

        $messages_top = $this->application->getMessageTop();
        
            switch ($this->datas['type']) {
                case 'actualite':
                    $current_menu = 3;
                    break;            
                default:
                    $current_menu = 0;
                    break;
            }
    
            $this->application->render('manager-modifier-un-post.twig', [
                'messages_top'     => $messages_top,
                'current_menu'     => $current_menu,
                'post'             => $post,
                'page'             => $page,
                'tri'              => $tri,
                'tri_ordre'        => $tri_ordre,
                'token'            => $this->token,
                'type'             => $this->datas['type'],
                'post_images'      => $post_images,
                'post_documents'   => $post_documents,
                'options'          => $options
            ]);
    }

    public function ordrePostImageAction()
    {
        if ($this->manager) {                    
            $request = $this->application->getRequest();
            if ($request->isMethod('POST')) {
                $response = $this->application->getResponse();  
                $response->headers->set('Content-Type', 'application/json');

                $json = ["error" => 0];

                if (!empty($request->request->get('id_post_image')) && !empty($request->request->get('ordre'))) {                    
                    $image = $this->post_repository->getImage($request->request->get('id_post_image'));
                    
                    if ($image) {
                        $image->ordre = $_POST['ordre'];
                        $this->post_repository->updateImage($image);
                    }
                }            

                $response->setContent(\json_encode($json));
                $response->send();
            }
        }
    }

    public function supprimerUnPostImageAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }            

        // Get the request
        $request = $this->application->getRequest();

        $session = $this->application->getSession();

        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        $image = $this->post_repository->getImage($this->datas['id_post_image']);
        if (!$image) {           
            $session->getFlashBag()->add('error', 'Erreur lors de la suppression de l\'image !'); 
            $this->application->redirection('manager/posts-' . $this->datas['type'] . '/modifier-un-post/' . $this->datas['id_post'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
        }

        $this->post_repository->deleteImage($image->id);

        unlink(__DIR__ . "/../../web/downloads/posts/" . $image->fichier);
        unlink(__DIR__ . "/../../web/downloads/posts/thumb-" . $image->fichier);
        unlink(__DIR__ . "/../../web/downloads/posts/crop-" . $image->fichier);

        $session->getFlashBag()->add('report', 'Image supprimée.'); 

        $this->application->redirection('manager/posts-' . $this->datas['type'] . '/modifier-un-post/' . $this->datas['id_post'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
    }

    public function supprimerUnPostDocumentAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }            

        // Get the request
        $request = $this->application->getRequest();

        $session = $this->application->getSession();

        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        $document = $this->post_repository->getDocument($this->datas['id_post_document']);
        if (!$document) {
            $session->getFlashBag()->add('error', 'Erreur lors de la suppression du document !'); 
            $this->application->redirection('manager/posts-' . $this->datas['type'] . '/modifier-un-post/' . $this->datas['id_post'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
        }

        $this->post_repository->deleteDocument($document->id);

        unlink(__DIR__ . "/../../web/downloads/posts/" . $document->fichier);
        
        $session->getFlashBag()->add('report', 'Document supprimé.'); 

        $this->application->redirection('manager/posts-' . $this->datas['type'] . '/modifier-un-post/' . $this->datas['id_post'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
    }

    public function supprimerUnPostAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }            

        // Get the request
        $request = $this->application->getRequest();

        $session = $this->application->getSession();

        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        $post = $this->post_repository->get('id', $this->datas['id_post']);
        if (!$post) {
            $session->getFlashBag()->add('error', 'Erreur lors de la suppression du post !'); 
            $this->application->redirection('manager/posts-' . $this->datas['type'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
        }

        $documents = $this->post_repository->getAllDocument($post->id);
        $images = $this->post_repository->getAllImage($post->id);

        if ($documents) {
            foreach ($documents as $document) {
                unlink(__DIR__ . "/../../web/downloads/posts/" . $document->fichier);
            }
        }
        if($images) {
            foreach ($images as $image) {
                unlink(__DIR__ . "/../../web/downloads/posts/" . $image->fichier);
                unlink(__DIR__ . "/../../web/downloads/posts/thumb-" . $image->fichier);
                unlink(__DIR__ . "/../../web/downloads/posts/crop-" . $image->fichier);
            }
        }

        $this->post_repository->delete($post);

        // Insert trace in logs
        $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Suppression d\'un post (' . $this->datas['type'] . ')')));

        $session->getFlashBag()->add('report', 'Post correctement supprimé.'); 
        $this->application->redirection('manager/posts-' . $this->datas['type'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
    }

    public function dupliquerPostAction()
    {
        if (!$this->manager) {
            $this->application->redirection('manager/connexion');
        }            

        // Get the request
        $request = $this->application->getRequest();

        $session = $this->application->getSession();

        $page      = (!empty($request->query->get('page'))) ? $request->query->get('page') : 1;
        $tri_ordre = (!empty($request->query->get('ordre')) && ($request->query->get('ordre') == 'desc' || $request->query->get('ordre') == 'asc')) ? $request->query->get('ordre') : 'asc';
        $tri       = (!empty($request->query->get('tri'))) ? $request->query->get('tri') : 'id';

        $post = $this->post_repository->get('id', $this->datas['id_post']);
        if (!$post) {
            $session->getFlashBag()->add('error', 'Le post à dupliquer n\'existe pas !'); 
            $this->application->redirection('manager/posts-' . $this->datas['type'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
        }        

        $documents = $this->post_repository->getAllDocument($post->id);
        $images = $this->post_repository->getAllImage($post->id);

        $post->id    = null;
        $post->date  = time();
        $post->ordre = 1;
        $post->actif = 0;

        $this->post_repository->insert($post);

        $post->id = Application::$bdd->lastInsertId();

        if ($documents) {
            foreach ($documents as $document) {
                $fichier = time() . "-" . $document->fichier;
                copy(__DIR__ . "/../../web/downloads/posts/" . $document->fichier, __DIR__ . "/../../web/downloads/posts/" . $fichier);
                $document->fichier = $fichier;
                $document->id_post = $post->id;
                $this->post_repository->insertDocument($document);
            }    
        }
        if($images) {
            foreach ($images as $image) {
                $fichier = time() . "-" . $image->fichier;
                copy(__DIR__ . "/../../web/downloads/posts/" . $image->fichier, __DIR__ . "/../../web/downloads/posts/" . $fichier);
                copy(__DIR__ . "/../../web/downloads/posts/thumb-" . $image->fichier, __DIR__ . "/../../web/downloads/posts/thumb-" . $fichier);
                copy(__DIR__ . "/../../web/downloads/posts/crop-" . $image->fichier, __DIR__ . "/../../web/downloads/posts/crop-" . $fichier);
                $image->fichier = $fichier;
                $image->id_post = $post->id;
                $this->post_repository->insertImage($image);
            }
        }

        $options = $this->post_repository->getAllOption($this->datas['id_post']);
        foreach ($options as $key => $value) {
            $this->post_repository->setOption($post->id, $key, $value);     
        }

        $this->post_repository->ordreRemise($post, $this->application->language, $this->datas['type']);

        // Insert trace in logs
        $this->log_repository->insert(new Log_manager(array('date' => time(), 'qui' => $this->manager->email, 'action' => 'Duplication d\'un post (' . $this->datas['type'] . ')')));
        
        $session->getFlashBag()->add('report', 'Post correctement dupliqué.'); 
        $this->application->redirection('manager/posts-' . $this->datas['type'] . '?page=' . $page . '&tri=' . $tri . '&ordre=' . $tri_ordre);  
    }
}

<?php

namespace App\Models;

use \PDO;
use Core\Repository;

class TicketRepository extends Repository
{
    public function __construct($application)
    {
        parent::__construct($application, 'ticket', 'Ticket');  
    }
}

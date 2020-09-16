<?php
declare(strict_types=1);
namespace App\Entity;
ini_set('display_errors', "1");
ini_set('display_startup_errors', "1");
error_reporting(E_ALL);

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Manager extends User
{
    /**
     * @var Ticket[]
     */
    private array $tickets;

    /**
     * Customer constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setRoles(["ROLE_MANAGER"]);
    }
    
    /**
     * @return Ticket[]
     */
    public function getTickets(): array
    {
        return $this->tickets;
    }

    /**
     * @param Ticket[] $tickets
     */
    public function setTickets(array $tickets): void
    {
        $this->tickets = $tickets;
    }
}
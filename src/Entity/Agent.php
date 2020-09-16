<?php
declare(strict_types=1);
namespace App\Entity;
ini_set('display_errors', "1");
ini_set('display_startup_errors', "1");
error_reporting(E_ALL);
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Agent extends User
{
    private int $openTickets = 0;
    private int $closedTickets = 0;
    private int $reopen = 0;
    private bool $isSecondLine = false;
    /**
     * @ORM\OneToMany(targetEntity=Ticket::class, mappedBy="agent")
     */
    protected Collection $tickets;

    /**
     * Agent constructor.
     */
    public function __construct()
    {
        parent::__construct();
        if($this->isSecondLine) {
            $this->setRoles(["ROLE_AGENT_SECOND_LINE"]);
        } else {
            $this->setRoles(["ROLE_AGENT"]);
        }
        $this->tickets = new ArrayCollection();

    }

    /**
     * @return Collection|Ticket[]
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    /**
     * @return bool
     */
    public function getIsSecondLine(): bool
    {
        return $this->isSecondLine;
    }

    /**
     * @param bool $isSecondLine
     */
    public function setIsSecondLine(bool $isSecondLine): void
    {
        $this->isSecondLine = $isSecondLine;
    }

    /**
     * @return int
     */
    public function getReopen(): int
    {
        return $this->reopen;
    }

    /**
     * @param int $reopen
     */
    public function setReopen(int $reopen): void
    {
        $this->reopen = $reopen;
    }
    /**
     * @param int $openTickets
     */
    public function setOpenTickets(int $openTickets): void
    {
        $this->openTickets = $openTickets;
    }
    /**
     * @return int
     */
    public function getOpenTickets(): int
    {
        return $this->openTickets;
    }

    /**
     * @return int
     */
    public function getClosedTickets(): int
    {
        return $this->closedTickets;
    }

    /**
     * @param int $closedTickets
     */
    public function setClosedTickets(int $closedTickets): void
    {
        $this->closedTickets = $closedTickets;
    }



    public function addTicket(Ticket $ticket): self
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets[] = $ticket;
            $ticket->setAgent($this);
            $this->openTickets = count($this->tickets);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): self
    {
        if ($this->tickets->contains($ticket)) {
            $this->tickets->removeElement($ticket);
            $this->closedTickets++;
            // set the owning side to null (unless already changed)
            if ($ticket->getAgent() === $this) {
                $ticket->setAgent(null);
            }
        }

        return $this;
    }

    public function returnOpenTicket() {
        $count = 0;
        foreach ($this->tickets as $ticket) {
            /** @var Ticket $ticket */
            if($ticket->getStatus() === Ticket::IN_PROGRESS) {
                $count++;
            }
        }
        return $count;
    }

    public function returnCloseTicket() {
        $count = 0;
        foreach ($this->tickets as $ticket) {
            /** @var Ticket $ticket */
            if($ticket->getStatus() === Ticket::CLOSE) {
                $count++;
            }
        }
        return $count;
    }

    public function returnReopenTicket() {
        $count = 0;
        foreach ($this->tickets as $ticket) {
            /** @var Ticket $ticket */
            if($ticket->getReopen()) {
                $count++;
            }
        }
        return $count;
    }
}
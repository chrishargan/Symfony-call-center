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
class Customer extends User
{
    /**
     * @ORM\OneToMany(targetEntity=Ticket::class, mappedBy="customer", orphanRemoval=true)
     */
    private Collection $tickets;

    /**
     * Customer constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setRoles(["ROLE_CUSTOMER"]);
        $this->tickets = new ArrayCollection();
    }

    /**
     * @return Collection|Ticket[]
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTickets(Ticket $tickets): self
    {
        if (!$this->tickets->contains($tickets)) {
            $this->tickets[] = $tickets;
            $tickets->setCustomer($this);
        }

        return $this;
    }

    public function removeTickets(Ticket $tickets): self
    {
        if ($this->tickets->contains($tickets)) {
            $this->tickets->removeElement($tickets);
            // set the owning side to null (unless already changed)
            if ($tickets->getCustomer() === $this) {
                $tickets->setCustomer(null);
            }
        }

        return $this;
    }

}
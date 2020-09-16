<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\Customer;
use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/ticket")
 */
class TicketController extends AbstractController
{
    public const ROLE_AGENT_SECOND_LINE = 'ROLE_AGENT_SECOND_LINE';
    public const ROLE_AGENT = 'ROLE_AGENT';
    /**
     * @Route("/", name="ticket_index", methods={"GET"})
     */
    public function index(TicketRepository $ticketRepository): Response
    {
        return $this->render('ticket/index.html.twig', [
            'tickets' => $ticketRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="ticket_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set the customer of this ticket to the current login user
            $customer = $this->getUser();
            $ticket->setCustomer($customer);
            // Set the createdDate to current datetime
            $ticket->setCreatedDate(new \DateTimeImmutable());
            // Save this ticket to database
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($ticket);
            $entityManager->flush();

            return $this->redirectToRoute('customer_home');
        }

        return $this->render('ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_show", methods={"GET"})
     */
    public function show(Ticket $ticket): Response
    {
        $agents = $this->getDoctrine()
            ->getRepository(Agent::class)
            ->findAll();
        $customers = $this->getDoctrine()
            ->getRepository(Customer::class)
            ->findAll();
        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'agents' => $agents,
            'customers' => $customers,
            'high_priority' => Ticket::HIGH_PRIORITY,
            'medium_priority' => Ticket::MEDIUM_PRIORITY,
            'low_priority' => Ticket::LOW_PRIORITY,
            'role_agent_second_line' => self::ROLE_AGENT_SECOND_LINE,
            'open' => Ticket::OPEN,
            'in_progress' => Ticket::IN_PROGRESS,
            'waiting_feedback' => Ticket::WAITING_FOR_CUSTOMER_FEEDBACK,
            'close' => Ticket::CLOSE,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="ticket_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Ticket $ticket): Response
    {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('ticket_index');
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Ticket $ticket): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ticket->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($ticket);
            $entityManager->flush();
        }

        return $this->redirectToRoute('ticket_index');
    }
}

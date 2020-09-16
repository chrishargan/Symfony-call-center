<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\Customer;
use App\Entity\Ticket;
use App\Form\AgentType;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ManagerDashController extends AbstractController
{
    public const ROLE_AGENT_SECOND_LINE = 'ROLE_AGENT_SECOND_LINE';
    public const ROLE_AGENT = 'ROLE_AGENT';

    /**
     * @Route("/manager/dash", name="manager_dash", methods={"GET"})
     */
    public function index(Request $request)
    {
        $tickets = $this->getDoctrine()
            ->getRepository(Ticket::class)
            ->findAll();
        $agents = $this->getDoctrine()
            ->getRepository(Agent::class)
            ->findAll();
        $customers = $this->getDoctrine()
            ->getRepository(Customer::class)
            ->findAll();
        return $this->render('manager_dash/index.html.twig', [
            'controller_name' => 'Manager',
            'agents' => $agents,
            'tickets' => $tickets,
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

//    /**
//     * @Route("/manager/new-agent", name="agent_new", methods={"GET","POST"})
//     * @param Request $request
//     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
//     */
//    public function newAgent(Request $request, UserPasswordEncoderInterface $passwordEncoder)
//    {
//        $agent = new Agent();
//        $form = $this->createForm(AgentType::class, $agent);
//        $form->handleRequest($request);
//        $valid = $form->isValid();
//        $submit = $form->isSubmitted();
//        if ($form->isSubmitted() && $form->isValid()) {
//            // encode the plain password
//            $agent->setPassword(
//                $passwordEncoder->encodePassword(
//                    $agent,
//                    $form->get('plainPassword')->getData()
//                )
//            );
//            // Check if agent is second line
//            if($form->get('isSecondLine')){
//                $agent->setIsSecondLine(true);
//                $agent->setRoles([self::ROLE_AGENT_SECOND_LINE]);
//            }
//            // Save this ticket to database
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->persist($agent);
//            $entityManager->flush();
//
//            return $this->redirectToRoute('manager_dash');
//        }
//
//        return $this->render('agent/register.html.twig', [
//            'agent' => $agent,
//            'agentForm' => $form->createView(),
//        ]);
//    }

    /**
     * @Route("/manager/makeAgent", name="make_agent", methods={"POST"})
     */
    public function convertCustomerToAgent(Request $request, MailerInterface $mailer)
    {
        // If convert a customer to agent
        /** @var Customer $customer */
        $customer = $this->getDoctrine()->getRepository(Customer::class)->findOneBy(['id' => $request->request->get('convertToAgent')]);
        $agent = new Agent();
        $agent->setEmail($customer->getEmail())
            ->setRoles([self::ROLE_AGENT])
            ->setPassword($customer->getPassword());
        // If convert to a second line agent
        if ($request->request->get('isSecondLine')) {
            $agent->setRoles([self::ROLE_AGENT_SECOND_LINE])
                ->setIsSecondLine(true);
        }
        // First remove the customer in database
        $this->getDoctrine()->getManager()->remove($customer);
        $this->getDoctrine()->getManager()->flush();
        // Then add new agent with all data from the removed customer
        $this->getDoctrine()->getManager()->persist($agent);
        $this->getDoctrine()->getManager()->flush();
        // Send email to ask them to change password
        $email = (new Email())
            ->from('becode@test.com')
            ->to($agent->getEmail())
            ->subject('Please change your password')
            ->text('You has been changed from customer to agent. Please change your password.');
        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->addFlash('error', 'Your email has not been sent.');
        }

        return $this->redirectToRoute('manager_dash');
    }

    /**
     * @Route("/manager/reassignAgent", name="reassign_agent", methods={"POST"})
     */
    public function reassignAgent(Request $request)
    {
        $ticket = $this->getDoctrine()->getRepository(Ticket::class)->findOneBy(['id' => $request->request->get('ticketId')]);
        $agent = $this->getDoctrine()->getRepository(Agent::class)->findOneBy(['id' => $request->request->get('reassignAgent')]);
        $ticket->setAgent($agent)
            ->setUpdatedDate(new \DateTimeImmutable())
            ->setStatus(Ticket::IN_PROGRESS);
        $this->getDoctrine()->getManager()->persist($ticket);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    /**
     * @Route("/manager/setPriority", name="set_priority", methods={"POST"})
     */
    public function setPriority(Request $request)
    {
        $ticket = $this->getDoctrine()->getRepository(Ticket::class)->findOneBy(['id' => $request->request->get('ticketId')]);
        $ticket->setPriority($request->request->get('setPriority'));
        $this->getDoctrine()->getManager()->persist($ticket);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    /**
     * @Route("/manager/wont-fix", name="set_wont_fix", methods={"POST"})
     */
    public function setWontFix(Request $request)
    {
        $ticket = $this->getDoctrine()->getRepository(Ticket::class)->findOneBy(['id' => $request->request->get('ticketId')]);
        $ticket->setCloseReason($request->request->get('closeReason'))
            ->setStatus(Ticket::CLOSE)
            ->setUpdatedDate(new \DateTimeImmutable());
        $this->getDoctrine()->getManager()->persist($ticket);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
    }

    /**
     * @Route("/manager/ticket_reset", name="ticket_reset", methods={"POST"})
     */
    public function endOfDayReset()
    {
        $tickets = $this->getDoctrine()
            ->getRepository(Ticket::class)
            ->findAll();

        foreach ($tickets as $ticket) {
            if ($ticket->getStatus() !== Ticket::CLOSE) {
                $ticket->setStatus(Ticket::OPEN);
                $ticket->removeAgent();
                $this->getDoctrine()->getManager()->persist($ticket);
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return $this->redirectToRoute('manager_dash');
    }
}

<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\Comment;
use App\Entity\Ticket;
use App\Form\AgentCommentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AgentController extends AbstractController
{
    const ROLE_AGENT_SECOND_LINE = 'ROLE_AGENT_SECOND_LINE';

    /**
     * @Route("/agent/home", name="agent_home", methods={"GET"})
     */
    public function index()
    {
        /**
         * @var Agent $agent
         */
        $agent = $this->getUser();
        if ($agent->getRoles()[0] === self::ROLE_AGENT_SECOND_LINE) {
            $agent->setIsSecondLine(true);
        }

        // Get the tickets that match the agent (normal agent: not-escalated ticket, second-line agent: escalated ticket)
        $tickets = $this->getDoctrine()->getRepository(Ticket::class)->findBy(['isEscalated' => $agent->getIsSecondLine()]);

        return $this->render('agent/index.html.twig', [
            'tickets' => $tickets,
            'agent' => $agent,
            'open' => Ticket::OPEN,
            'in_progress' => Ticket::IN_PROGRESS,
            'waiting_feedback' => Ticket::WAITING_FOR_CUSTOMER_FEEDBACK,
            'close' => Ticket::CLOSE,
            'high_priority' => Ticket::HIGH_PRIORITY,
            'medium_priority' => Ticket::MEDIUM_PRIORITY,
            'low_priority' => Ticket::LOW_PRIORITY,
        ]);
    }

    /**
     * @Route("/agent/addTodo", name="agent_addTodo", methods={"POST"})
     */
    public function addTodo(Request $request, MailerInterface $mailer)
    {
        /**
         * @var Agent $agent
         */
        $agent = $this->getUser();
        if ($agent->getRoles()[0] === self::ROLE_AGENT_SECOND_LINE) {
            $agent->setIsSecondLine(true);
        }

        $ticket = $this->getDoctrine()->getRepository(Ticket::class)->findOneBy(['id' => $request->request->get('addToDo')]);
        $ticket->setAgent($agent)
            ->setStatus(Ticket::IN_PROGRESS)
            ->setUpdatedDate(new \DateTimeImmutable());
        $this->getDoctrine()->getManager()->persist($ticket);
        $this->getDoctrine()->getManager()->flush();

        $email = (new Email())
            ->from('becode@test.com')
            ->to('samihuyen059@gmail.com')
            ->subject('Your ticket is being handled by us.')
            ->text('Your ticket is now being handled by one of our agent.');
        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->addFlash('error', 'Your email has not been sent.');
        }

        return $this->redirectToRoute('agent_home');
    }

    /**
     * @Route("/agent/ticket/{id}", name="agent_ticket", methods={"GET"})
     */
    public function agentTicketDetail(Ticket $ticket)
    {
        /**
         * @var Agent $agent
         */
        $agent = $this->getUser();
        if ($agent->getRoles()[0] === self::ROLE_AGENT_SECOND_LINE) {
            $agent->setIsSecondLine(true);
        }
        $comment = new Comment();
        $form = $this->createForm(AgentCommentType::class, $comment, ['action' => $this->generateUrl('agent_add_comment', ['id' => $ticket->getId()])]);

        return $this->render('agent/detail.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
            'agent' => $agent,
            'open' => Ticket::OPEN,
            'in_progress' => Ticket::IN_PROGRESS,
            'waiting_feedback' => Ticket::WAITING_FOR_CUSTOMER_FEEDBACK,
            'close' => Ticket::CLOSE,
            'high_priority' => Ticket::HIGH_PRIORITY,
            'medium_priority' => Ticket::MEDIUM_PRIORITY,
            'low_priority' => Ticket::LOW_PRIORITY,
        ]);
    }

    /**
     * @Route("/agent/ticket/addComment/{id}", name="agent_add_comment", methods={"POST"})
     */
    public function addComment(Ticket $ticket, Request $request, MailerInterface $mailer)
    {
        /**
         * @var Agent $agent
         */
        $agent = $this->getUser();
        if ($agent->getRoles()[0] === self::ROLE_AGENT_SECOND_LINE) {
            $agent->setIsSecondLine(true);
        }

        $form = $request->request->get('agent_comment');
        $comment = new Comment();
        // Assign user and ticket to this comment
        $comment->setUser($agent)
            ->setTicket($ticket)
            ->setTitle($form['title'])
            ->setContent($form['content']);
        // Check if this comment is private, if not, change status of ticket
        $isPrivate = $form['isPrivate'] ? true : false;
        $comment->setIsPrivate($isPrivate);
        if (!$isPrivate) {
            $ticket->setStatus(Ticket::WAITING_FOR_CUSTOMER_FEEDBACK)
                ->setUpdatedDate(new \DateTimeImmutable());
            $this->getDoctrine()->getManager()->persist($ticket);

            $email = (new Email())
                ->from('becode@test.com')
                ->to('samihuyen059@gmail.com')
                ->subject('Please give us your feedback')
                ->text('Your ticket is handled by one of our agent. Please give us your feedback.');
            try {
                $mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('error', 'Your email has not been sent.');
            }
        }

        $this->getDoctrine()->getManager()->persist($comment);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('agent_ticket', ['id' => $ticket->getId()]);
    }

    /**
     * @Route("/agent/ticket/close/{id}", name="agent_ticket_close", methods={"POST"})
     */
    public function closeTicket(Ticket $ticket, MailerInterface $mailer)
    {
        foreach ($ticket->getComments() as $comment) {
            if ($comment->getUser() instanceof Agent && !$comment->getIsPrivate()) {
                $ticket->setStatus(Ticket::CLOSE)
                    ->setUpdatedDate(new \DateTimeImmutable());
                $this->getDoctrine()->getManager()->persist($ticket);
                $this->getDoctrine()->getManager()->flush();

                $email = (new Email())
                    ->from('becode@test.com')
                    ->to('samihuyen059@gmail.com')
                    ->subject('Your ticket has been closed')
                    ->text('Your ticket is handled by one of our agent. The ticket is now closed.');
                try {
                    $mailer->send($email);
                } catch (TransportExceptionInterface $e) {
                    $this->addFlash('error', 'Your email has not been sent.');
                }

                return $this->redirectToRoute('agent_ticket', ['id' => $ticket->getId()]);
            }
        }
        $this->addFlash('error', 'A ticket can only be closed when it has at least one public comment from an agent.');

        return $this->redirectToRoute('agent_ticket', ['id' => $ticket->getId()]);
    }

    /**
     * @Route("/agent/ticket/escalate/{id}", name="agent_ticket_escalate", methods={"POST"})
     */
    public function escalate(Ticket $ticket, MailerInterface $mailer)
    {
        $ticket->setIsEscalated(true)
            ->setAgent(null)
            ->setStatus(Ticket::OPEN)
            ->setUpdatedDate(new \DateTimeImmutable());
        $this->getDoctrine()->getManager()->persist($ticket);
        $this->getDoctrine()->getManager()->flush();

        $email = (new Email())
            ->from('becode@test.com')
            ->to('samihuyen059@gmail.com')
            ->subject('Your ticket is escalated')
            ->text('Your ticket will be handled by one of our second line agent.');
        try {
            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->addFlash('error', 'Your email has not been sent.');
        }

        return $this->redirectToRoute('agent_home');
    }
}

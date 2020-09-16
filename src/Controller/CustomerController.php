<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Ticket;
use App\Form\CommentType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class CustomerController extends AbstractController
{
    /**
     * @Route("/customer", name="customer_home", methods={"GET"})
     */
    public function index()
    {
        $tickets = $this->getDoctrine()->getRepository(Ticket::class)->findBy(['customer' => $this->getUser()]);


        return $this->render('customer/index.html.twig', [
            'tickets' => $tickets,
            'open' => Ticket::OPEN,
            'in_progress' => Ticket::IN_PROGRESS,
            'waiting_feedback' => Ticket::WAITING_FOR_CUSTOMER_FEEDBACK,
            'close' => Ticket::CLOSE,
        ]);

    }

    /**
     * @Route("/customer/tickets/{id}", name="customer_tickets", methods={"GET"})
     */
    public function customerTickets(Ticket $ticket)
    {
        $style = __DIR__ . "/../../public/assets/customerStyle.css";
        $comment = new Comment();
        $comment->setUser($this->getUser());
        $comment->setTicket($ticket);

        $form = $this->createForm(CommentType::class, $comment,
            ['action' => $this->generateUrl('customer_ticket_add_comment', ['id' => $ticket->getId()])]
        );

        return $this->render('customer/customerTickets.html.twig', [
            'form' => $form->createView(),
            'ticket' => $ticket,
            'close' => Ticket::CLOSE,
            'style' => $style,
            'open' => Ticket::OPEN,
            'in_progress' => Ticket::IN_PROGRESS,
            'waiting_feedback' => Ticket::WAITING_FOR_CUSTOMER_FEEDBACK,
        ]);
    }

    /**
     * @Route("/customer/tickets/addComment/{id}", name="customer_ticket_add_comment", methods={"POST"})
     */
    public function addComment(Ticket $ticket, Request $request, MailerInterface $mailer)
    {
        $form = $request->request->get('comment');
        $comment = new Comment();
        $comment->setTitle($form['title'])
            ->setTicket($ticket)
            ->setUser($this->getUser())
            ->setContent($form['content']);
        $ticket->setUpdatedDate(new \DateTimeImmutable());
        if ($ticket->getStatus() === Ticket::WAITING_FOR_CUSTOMER_FEEDBACK) {
            $ticket->setStatus(Ticket::IN_PROGRESS);

            $email = (new Email())
                ->from('becode@test.com')
                ->to('samihuyen059@gmail.com')
                ->subject('Customer has posted their feedback.')
                ->text('Customer has posted their feedback. Please review/answer this new feedback.');
            try {
                $mailer->send($email);
            } catch (TransportExceptionInterface $e) {
                $this->addFlash('error', 'Your email has not been sent.');
            }

        }

        $this->getDoctrine()->getManager()->persist($comment);
        $this->getDoctrine()->getManager()->persist($ticket);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('customer_tickets', ['id' => $ticket->getId()]);
    }

    /**
     * @Route("/customer/tickets/reopen/{id}", name="customer_ticket_reopen", methods={"POST"})
     */
    public function reOpen(Ticket $ticket)
    {
        /** @var \DateTimeImmutable $update */
        $update = $ticket->getUpdatedDate();
        if ($update->add(new \DateInterval("PT1H")) >= new \DateTimeImmutable() && $ticket->getCloseReason() === null) {
            $ticket->setStatus(Ticket::IN_PROGRESS);
            $ticket->setReopen($ticket->getReopen() + 1);
            $this->getDoctrine()->getManager()->persist($ticket);
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('customer_tickets', ['id' => $ticket->getId()]);
        }
        $this->addFlash('error', 'You can no longer Re-open a case after it has expired or is permanently closed by manager.');
        return $this->redirectToRoute('customer_tickets', ['id' => $ticket->getId()]);
    }

}


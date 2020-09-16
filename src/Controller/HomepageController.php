<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\Customer;
use App\Entity\Manager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index()
    {
        $user = $this->getUser();
        $isManager = $isAgent = $isCustomer = false;
        if ($user instanceof Manager) {
            $isManager = true;
        }
        if ($user instanceof Agent) {
            $isAgent = true;
        }
        if ($user instanceof Customer) {
            $isCustomer = true;
        }
        return $this->render('homepage/index.html.twig', [
            'isManager' => $isManager,
            'isAgent' => $isAgent,
            'isCustomer' => $isCustomer,
        ]);
    }
}

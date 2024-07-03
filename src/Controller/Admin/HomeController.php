<?php

namespace App\Controller\Admin;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route(path: '/admin', name: 'admin_home_index')]
    public function index(ManagerRegistry $em): Response
    {
        $em = $em->getManager();
        $players = $em->getRepository(\App\Entity\UserPlay::class)->findAll();

        return $this->render('admin/home/index.html.twig', [
            'players' => $players
        ]);
    }
}

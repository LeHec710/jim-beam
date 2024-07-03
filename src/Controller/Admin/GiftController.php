<?php

namespace App\Controller\Admin;

use App\Entity\Gift;
use App\Form\GiftType;
use App\Repository\GiftRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/gift')]
class GiftController extends AbstractController
{
    #[Route(path: '/', name: 'gift_index', methods: ['GET'])]
    public function index(GiftRepository $giftRepository): Response
    {
        return $this->render('gift/index.html.twig', [
            'gifts' => $giftRepository->findAll(),
        ]);
    }

    #[Route(path: '/new', name: 'gift_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $gift = new Gift();
        $form = $this->createForm(GiftType::class, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($gift);
            $entityManager->flush();

            return $this->redirectToRoute('gift_index');
        }

        return $this->render('gift/new.html.twig', [
            'gift' => $gift,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'gift_show', methods: ['GET'])]
    public function show(Gift $gift): Response
    {
        return $this->render('gift/show.html.twig', [
            'gift' => $gift,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'gift_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Gift $gift): Response
    {
        $form = $this->createForm(GiftType::class, $gift);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('gift_index');
        }

        return $this->render('gift/edit.html.twig', [
            'gift' => $gift,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}', name: 'gift_delete', methods: ['POST'])]
    public function delete(Request $request, Gift $gift): Response
    {
        if ($this->isCsrfTokenValid('delete'.$gift->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($gift);
            $entityManager->flush();
        }

        return $this->redirectToRoute('gift_index');
    }
}

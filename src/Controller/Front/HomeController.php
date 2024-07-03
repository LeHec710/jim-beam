<?php

namespace App\Controller\Front;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Gift;
use App\Entity\UserPlay;
use App\Form\UserPlayType;
use App\Service\App;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class HomeController extends AbstractController
{

  private $session;
  public function __construct(private App $app, private EntityManagerInterface $em, private RequestStack $requestStack)
  {
    $this->app = $app;
    $this->em = $em;
    $this->session = $this->requestStack->getSession();
  }

  #[Route(path: '/', name: 'front_home')]
  public function index(): Response
  {
    $opening = $this->app->opening();
    if ($opening !== true) {
      return $this->render('front/' . $opening . '.html.twig', [
        'startAt' => $_ENV['START_AT'],
        'endAt' => $_ENV['END_AT'],
      ]);
    }
    return $this->render('front/index.html.twig');
  }

  #[Route(path: '/age', name: 'front_legals')]
  public function age(): Response
  {
    if ($this->app->opening() !== true) {
      return $this->redirectToRoute('front_home');
    }
    
    return $this->render('front/legals.html.twig');
  }

  #[Route(path: '/questionnaire/{step?}', name: 'front_quiz')]
  public function quiz(?int $step): Response
  {
    if ($this->app->opening() !== true) {
      return $this->redirectToRoute('front_home');
    }

    if(!$step) $step = 1;

    $quiz = [
      [
        "question" => "En quelle annee a ete cree jim beam ?",
        "answers" => [
          "en 1795",
          "en 1867",
          "en 1995"
        ],
        "right_answer" => "en 1795",
        "message" => "En 1795, Jacob Beam vendit son premier fut d'Old Jake Beam Sour Mash. Apres sept generations de maitres distillateurs, la tradition familiale de Jim Beam perdure encore aujourd'hui.",
        ],
      [
        "question" => "Quel est le bourbon N°1 mondial ?",
        "answers" => [
          "Jack Daniel's",
          "Jim Beam",
          "Maker's Mark"
        ],
        "right_answer" => "Jim Beam",
        "message" => "Qu'est-ce qu'un Bourbon ? C'est un whisky fabrique aux Etats-Unis, d'une composition a 51% de mais pour une maturation de deux ans minimums dans de nouveaux futs de chene americain brules.",
        ],
      [
        "question" => "Quelle est l'origine des produits Jim Beam ?",
        "answers" => [
          "Kentucky",
          "Texas",
          "Tenessee"
        ],
        "right_answer" => "Kentucky",
        "message" => "Jim Beam est distille a Clarmont au Kentucky, la ou est ne le Bourbon. Vous y serez toujours les bienvenus !"
        ],
    ];

    if(!isset($quiz[$step - 1])) return $this->redirectToRoute("front_quiz");

    return $this->render('front/quiz/quiz.html.twig', [
      'step' => $step ?? 1,
      'maxStep' => count($quiz),
      'quiz' => $quiz[$step - 1] 
    ]);
  }

  #[Route(path: '/inscription', name: 'front_register')]
  public function register(Request $request): Response
  {
    if ($this->app->opening() !== true) {
      return $this->redirectToRoute('front_home');
    }

    return $this->render('front/register.html.twig', [
      'm' => $request->query->get('m')
    ]);
  }

  #[Route(path: '/submit', name: 'front_submit')]
  public function submit(Request $request): Response
  {
    if ($this->app->opening() !== true) {
      return $this->redirectToRoute('front_home');
    }

    $em = $this->em;

    $lottery = $this->app->getLottery();
    
    $checkUser = $em->getRepository(UserPlay::class)->findOneBy([
      'email' => $request->get('email'),
      'lottery' => $lottery
    ]);

    if ($checkUser != null) {
      return new JsonResponse(['error' => 'Vous avez deja participe !']);
    }

    $item = new UserPlay();
    $item->setFirstname($request->get('firstname'));
    $item->setLastname($request->get('lastname'));
    $item->setAddress($request->get('address'));
    $item->setAddressComplement($request->get('address_complement'));
    $item->setCity($request->get('city'));
    $item->setZip($request->get('zip'));
    $item->setEmail($request->get('email'));
    $item->setPhone($request->get('phone'));
    $item->setCreatedAt(new \DateTime('now'));
    $item->setLottery($lottery);
    $em->persist($item);
    $em->flush();

    $product = $this->app->play($item);

    $this->session->set('confirm', true);
    $this->session->set('userPlayId', $item->getId());

    return new JsonResponse([
      'success' => true
    ]);
  }

  #[Route(path: '/confirmation', name: 'front_result')]
  public function result(): Response
  {
    if ($this->app->opening() !== true) {
      return $this->redirectToRoute('front_home');
    }

    $userPlayId = $this->session->get("userPlayId") ?? -1;
    $userPlay = $this->em->getRepository(UserPlay::class)->find($userPlayId);

    if ($userPlayId === null || $userPlay === null) {
      return $this->redirectToRoute('front_home');
    }

    $gift = $userPlay->getGift();
    $product = $gift->getProduct();

    return $this->render('front/result.html.twig', [
      'product' => $product,
    ]);
  }
}

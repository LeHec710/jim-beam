<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\LotteryPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Service\FileUploader;
use App\Service\Tool;
use App\Service\Generator;

use App\Entity\Lottery;
use App\Entity\Gift;
use App\Entity\DataRole;
use App\Entity\UserPlay;
use App\Form\LotteryType;
use Doctrine\ORM\EntityManagerInterface;
use Proxies\__CG__\App\Entity\UserPlay as EntityUserPlay;
use Swift_Mailer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route(path: '/admin/lottery')]
#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class LotteryController extends AbstractController
{
    private SessionInterface $session;
    public function __construct(private EntityManagerInterface $em, private RequestStack $requestStack,)
    {
        $this->session = $this->requestStack->getSession();
    }

    #[Route(path: '/liste', name: 'lottery_index')]
    public function index()
    {
        return $this->render('admin/lottery/index.html.twig', []);
    }

    #[Route(path: '/launch/{id}', name: 'lottery_launch')]
    public function launch($id)
    {
        $em = $this->em;
        $lottery = $em->getRepository(Lottery::class)->find($id);

        $players = $em->getRepository(UserPlay::class)->findBy([
        ]);
        $result = mt_rand(0, count($players) - 1);
        $index = $players[$result]->getId();
        $winner = $em->getRepository(UserPlay::class)->find($index);
                    

        $gift = $lottery->getGifts()[0];
        $gift->setUser($winner);
        $winner->setGift($gift);
        $em->persist($gift);
        $em->persist($winner);
        $em->flush();

        return $this->render('admin/lottery/index.html.twig', []);
    }

    #[Route(path: '/gestion-gains', name: 'lottery_manage')]
    public function manage()
    {
        $em = $this->em;
        $sql = '
        SELECT g.*, p.picture, p.name, u.lastname, u.firstname, u.email, l.name as lotteryName
        FROM gift as g
        LEFT JOIN user_play as u on g.user_id = u.id
        LEFT JOIN product as p on g.product_id = p.id
        LEFT JOIN lottery as l on g.lottery_id = l.id
        WHERE g.user_id IS NOT NULL
        ';
        
        $qb = $em->getConnection()->executeQuery($sql);
        $results = $qb->fetchAllAssociative();
        
        return $this->render('admin/lottery/manage.html.twig', [
            'gifts' => $results
        ]);
    }

    #[Route(path: '/validate-event/{id}', name: 'lottery_validate_event')]
    public function validate(Request $request, $id = null,  Swift_Mailer $mailer)
    {
        $em = $this->em;
        $gift = $em->getRepository(Gift::class)->find($id);
        $gift->setIsDelivered(1);
        $em->persist($gift);
        $em->flush();

        $winner = $gift->getUser();

        $message = (new \Swift_Message())
            ->setSubject('Vous avez gagné !')
            ->setFrom(array($_ENV['MAILER_ADDRESS'] => $_ENV['MAILER_NAME']))
            ->setTo($winner->getEmail())
            ->setContentType('text/html')
            ->setBody($this->render('admin/templates/emails/winner.html.twig', array('user' => $winner, 'gain' => $gift->getProduct()->getName())));

        $mailer->send($message);

        return $this->redirectToRoute('lottery_manage');
    }

    #[Route(path: '/email/{id}', name: 'lottery_preview_email')]
    public function preview(Request $request, $id = null)
    {
        $em = $this->em;
        $gift = $em->getRepository(Gift::class)->find($id);
        $winner = $gift->getUser();

        return $this->render('admin/templates/emails/winner.html.twig', array('user' => $winner, 'gain' => $gift->getProduct()->getName()));
    }

    #[Route(path: '/udapte-winner/', name: 'lottery_update_winner')]
    public function updateWinner(Request $request)
    {
        $em = $this->em;
        $gift = $em->getRepository(Gift::class)->find($request->get('id'));
        $winner = $em->getRepository(EntityUserPlay::class)->findOneBy([
            'email' => $request->get('email')
        ]);
        if ($winner == null) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }
        $gift->setUser($winner);
        $em->persist($gift);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/update-event', name: 'lottery_update_event')]
    public function update(Request $request)
    {
        $em = $this->em;
        $lottery = $em->getRepository(Lottery::class)->find($request->get('lotteryId'));
        $gift = $em->getRepository(Gift::class)->find($request->get('eventId'));
        $gift->setInstantAt(new \DateTime($request->get('start')));
        $em->persist($gift);
        $em->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/generate/{id}', name: 'lottery_generate')]
    public function generate($id, Request $request)
    {
        $em = $this->em;
        $lottery = $em->getRepository(Lottery::class)->find($id);

        $hours = 10;    // nombre d'heures d'ouverture du centre
        $startHour = 10;    // heure du 1er IG de la journée

        $diff = $lottery->getStartAt()->diff($lottery->getEndAt());
        $days = $diff->format('%a');    // durée de l'opération en jour
        
        //  on calcule le nombre de semaines dans la période donnée
        $p = new \DatePeriod(
            $lottery->getStartAt(),
            new \DateInterval('P1W'),
            $lottery->getEndAt()
        );
        $weeks = -1;
        foreach ($p as $w) {
            $weeks++;
        }
        // on supprime tous les dimanches de la durée en jour
        $days = $days - $weeks;

        // on supprime tous les IG existants pour cette opération
        $gifts = $lottery->getGifts();
        foreach ($gifts as $gift ) {
            $em->remove($gift);
        }
        $em->flush();

        // répartition des produits
        foreach ($lottery->getProducts() as $key => $product ) {

            $start = $lottery->getStartAt();
            // on calcule les intervales en jours et en heures entre chaque IG
            $step = $days / $product->getQuantity();
            $interval = floor($step);
            $stepHours = 1;

            $start->add(new \DateInterval("PT". rand(0, 59)."M"));
            $count = 0;
            if ( $interval == 0) {
                // Si l'intervale est  = 0, c'est qu'il faut mettre plusieurs produits par jour avec la variable stepHours, tous les jours
                $stepHours = floor($hours * $step);
                $h = ceil($hours / $stepHours);
                for ($i = 0; $i < $days; $i++) {
                    for ($j = 0; $j < $h; $j++) {

                        $date = clone $start;
                        $code = bin2hex(random_bytes(3));
                        if ($count < $product->getQuantity()) {
                            $gift = new \App\Entity\Gift();
                            $gift->setInstantAt($date);
                            $gift->setIsDelivered(0);
                            $gift->setLottery($lottery);
                            $gift->setProduct($product);
                            $gift->setCode($code);
                            $em->persist($gift);
                            $lottery->addGift($gift);
                        }
                        $start = clone $date->add(new \DateInterval("PT{$stepHours}H"));
                        $count++;
                    }
                    $start = $date
                    ->setTime($startHour, rand(0, 59),0)
                    ->modify('+1 day');
                    if ($start->format('D') == 'Sun') {
                        // on passe au lundi si la date est un dimanche
                        $start->modify('+1 day');
                    }
                }
            } else {
                //  Sinon, on applique l'interval entre chaque jours
                for ($i = 0; $i < $product->getQuantity(); $i++) {

                    $date = clone $start;

                    $code = bin2hex(random_bytes(3));
                    $gift = new \App\Entity\Gift();
                    $gift->setInstantAt($date);
                    $gift->setIsDelivered(0);
                    $gift->setLottery($lottery);
                    $gift->setProduct($product);
                    $gift->setCode($code);

                    $lottery->addGift($gift);
                    $em->persist($gift);

                    $start = $date->modify('+' . $interval . ' day');
                    if ($start->format('D') == 'Sun') {
                        // on passe au lundi si la date est un dimanche
                        $start->modify('+1 day');
                    }
                }
            }
            
        }
        $em->flush();

        return new JsonResponse([ 'success' => count($lottery->getGifts() )]);
    }

    #[Route(path: '/planning/{id}', name: 'lottery_planning')]
    public function planning($id, Request $request)
    {
        $em = $this->em;
        $lottery = $em->getRepository(Lottery::class)->find($id);

        if ( $request->query->get('events')) {

            $events = [];

            $gifts = $lottery->getGifts();
            foreach ($gifts as $gift ) {

                $color = '#607d8b';
                if ( $gift->getUser() != null ) {
                    $color = '#f39a15';
                }
                if ($gift->getUser() != null && $gift->getIsDelivered()) {
                    $color = '#c20011';
                }

                $events[] = [
                    'id' => $gift->getId(),
                    'start' => $gift->getInstantAt()->format('Y-m-d H:i:s'),
                    'end' => $gift->getInstantAt()->format('Y-m-d H:i:s'),
                    'title' => $gift->getProduct()->getName(),
                    'color' => $color,
                ];
            }

            return new Response(json_encode([
                'events' => $events
            ]));
            
        } else {
            return $this->render('admin/lottery/planning.html.twig', [
                'lottery' => $lottery
            ]);
        }
    }

    #[Route(path: '/export-datas', name: 'export_igs')]
    public function export(Request $request, Generator $generator)
    {

        ini_set('memory_limit', -1);
        ini_set('max_execution_time', -1);

        $em = $this->em;


        $sql = "
        SELECT g.*, 
        
        p.picture, 
        p.name, 
        u.lastname, 
        u.firstname, 
        u.email, 
        u.zip, 
        u.phone, 
        u.optin1

        FROM gift as g
        LEFT JOIN user_play as u on g.user_id = u.id
        LEFT JOIN product as p on g.product_id = p.id
        WHERE g.user_id IS NOT NULL
        ";

        $qb = $em->getConnection()->executeQuery($sql);
        $results = $qb->fetchAllAssociative();
        
        $titles = [
            'Nom',
            'Prénom', 
            'E-mail', 
            'Téléphone', 
            'Code postal', 
            'optin', 
            'Gain', 
            'Code', 
            'Instant gagnant à', 
            'Gain obtenu à'
        ];
        $columns = [
            'lastname', 
            'firstname', 
            'email', 
            'phone', 
            'zipcode', 
            'optin1', 
            'name', 
            'code', 
            'instant_at', 
            'win_at'
            ];

        $writer = $generator->generateSheetXLS($titles, $columns, $results);

        $response =  new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="export.xls"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }

    #[Route(path: '/donnees/', name: 'lottery_datas')]
    public function datasAction(Request $request, Tool $tool)
    {
        $em = $this->em;

        $sorts = array(
            'u.name',
            'u.start_at',
            'u.end_at',
        );

        $sql = "SELECT u.*
        FROM lottery as u
        WHERE u.id > 0 ";


        $records = $tool->getScriptTable($sql, $sorts, $em); // SCRIPT PHP NON MODIFIABLE  DE REQUETES POUR RESULTATS TABLEAU DATATABLE JSON

        foreach ($records['results'] as $result) :

            $startAt = new \DateTime($result['start_at']);
            $endAt = new \DateTime($result['end_at']);

            $sql = "
            SELECT * FROM user_play
            WHERE created_at > '" . $startAt->format('Y-m-d H:i:s') . "'
            AND created_at < '" . $endAt->format('Y-m-d H:i:s') . "'
            ";

            $qb = $em->getConnection()->executeQuery($sql);
            $players = $qb->fetchAllAssociative();


            $row = array(
                '<b>'. $result['name'].'</b>',
                $startAt->format('d/m/Y à H:i'),
                $endAt->format('d/m/Y à H:i'),
                count($players),
            );

            $actions = '<div class="md-btn-group">';

            $actions .= '<a data-id="' . $result['id'] . '" class="editItem btn btn-secondary btn-sm"><i class="fas fa-edit mr-2"></i>Modifier</a>';

            $url = $this->generateUrl('lottery_launch', ['id' => $result['id']]);
            if ($_ENV['PROJECT_TYPE'] == 'lottery') {
                $actions .= '<a href="' . $url . '" class="btn btn-sm btn-icon-only btn-success" data-toggle="tooltip" data-original-title="Démarrer"><i class="fas fa-play"></i></a>';
            }

            $url = $this->generateUrl('lottery_planning', [ 'id' => $result['id'] ]);

            if ($_ENV['PROJECT_TYPE'] == 'instant_win') {
                $actions .= '<a href="'.$url.'" class="btn btn-icon-only btn-success btn-sm" data-toggle="tooltip" data-original-title="Calendrier des IGs"><i class="fas fa-calendar"></i></a>';
            }

            $tokenProvider = $this->container->get('security.csrf.token_manager');
            $token = $tokenProvider->getToken('delete' . $result['id'])->getValue();

            $actions .= '<a href="javascript:void(0);" class="btn btn-sm btn-icon-only btn-danger confirmDeleteBox" data-message="Souhaitez-vous supprimer définitivement ce tirage ?<br/> Toutes les données rattachées à ce tirage seront automatiquement supprimées."data-token="' . $token . '" data-url="' . $this->generateUrl('lottery_delete', array('id' => $result['id'])) . '" data-toggle="tooltip" data-original-title="Suppression"><i class="fas fa-trash"></i></a>';
            $actions .= '</div>';

            $row[] = $actions;

            $records["aaData"][] = $row;

        endforeach;

        return new response(json_encode($records));
    }

    #[Route(path: '/formulaire/{id?}', requirements: [], name: 'lottery_form')]
    public function form(Request $request, $id = NULL, FileUploader $fileUploader, UrlGeneratorInterface $router)
    {
        $em = $this->em;
        $session = $this->session;

        if ($id) {
            $item = $em->getRepository(Lottery::class)->find($id);
            $token = $item->getToken();
        } else {
            $item = new Lottery();
            $token = bin2hex(random_bytes(16));
        }

        $form = $this->createForm(LotteryType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $item = $form->getData();
            $item->setToken($token);

            if ($form['startAt']->getData()) {
                $dateFormated = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $form['startAt']->getData())));
                $item->setStartAt(new \DateTime($dateFormated));
            } else {
                $item->setStartAt(NULL);
            }
            if ($form['endAt']->getData()) {
                $dateFormated = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $form['endAt']->getData())));
                $item->setEndAt(new \DateTime($dateFormated));
            } else {
                $item->setEndAt(NULL);
            }

            // $file = $form['picture']->getData();
            // $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/';

            // if ($file) {
            //     $fileName = $fileUploader->upload($file, $dir);
            //     $item->setIcon($fileName);
            // }
            $em->persist($item);
            $em->flush();

            return new Response(json_encode([
                'success' => true,
                'id' => $item->getId()
            ]));
        }
        // Réponse AJAX
        if ($form->isSubmitted() && !$form->isValid()) {
            return new Response(json_encode(['error' => $form->getErrors()]));
        }

        return $this->render('admin/lottery/form.html.twig', array(
            'item' => $item,
            'form' => $form->createView()
        ));
    }

    #[Route(path: '/suppression/{id}', name: 'lottery_delete', methods: 'DELETE')]
    public function delete(Request $request, Lottery $lottery)
    {
        $session = $this->session;
        if ($this->isCsrfTokenValid('delete' . $lottery->getId(), $request->request->get('_token'))) {
            $em = $this->em;
            $em->remove($lottery);
            $em->flush();
            $session->getFlashBag()->add('success', "La suppression a bien été effectuée");
        } else {
            $session->getFlashBag()->add('error', "Erreur !");
        }

        return $this->redirectToRoute('lottery_index', []);
    }
}

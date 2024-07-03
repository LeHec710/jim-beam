<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\RequestStack;

use App\Service\FileUploader;
use App\Service\Tool;
use App\Service\Generator;

use App\Form\UserType;
use App\Entity\User;
use App\Entity\DataRole;
use App\Entity\CalendarEvent;
use App\Entity\Lottery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;

#[Route(path: '/admin/utilisateurs')]
class UserController extends AbstractController
{
    public function __construct(private ManagerRegistry $doctrine, RequestStack $requestStack, UserPasswordHasherInterface $hasher, MailerInterface $mailer, UrlGeneratorInterface $router)
    {
        $this->requestStack = $requestStack;
        $this->hasher = $hasher;
        $this->mailer = $mailer;
        $this->router = $router;
        $this->dir = '/public//uploads/users/';
    }

    #[Route(path: '/liste/{archive}', name: 'user_index')]
    #[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
    public function index($archive = NULL)
    {
        $em = $this->doctrine->getManager();

        return $this->render('admin/user/index.html.twig', ['archive' => $archive, 'roles' => []]);
    }

    #[Route(path: '/donnees/{archive}', name: 'user_datas')]
    #[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
    public function datasAction(Request $request, Tool $tool, $archive = NULL)
    {
        $em = $this->doctrine->getManager();

        $sorts = ['u.lastname', 'u.firstname', 'u.email'];

        $sql = "SELECT u.*
        FROM user AS u
        WHERE u.id > 0 ";

        if ($archive) {
            $sql .= " AND u.is_active = 0 ";
        } else {
            $sql .= " AND u.is_active = 1 ";
        }

        $records = $tool->getScriptTable($sql, $sorts, $em, "GROUP BY u.id"); // SCRIPT PHP NON MODIFIABLE  DE REQUETES POUR RESULTATS TABLEAU DATATABLE JSON

        foreach ($records['results'] as $result) :
            $row = [$result['lastname'], $result['firstname'], $result['email']];
           $actions = '<div class="md-btn-group">';
            
            $actions .= '<a data-id="'.$result['id'].'" class="editItem btn btn-icon-only btn-primary btn-sm" data-toggle="tooltip" data-original-title="Modifier"><i class="fas fa-edit"></i></a>';

            $tokenProvider = $this->container->get('security.csrf.token_manager');
            $token = $tokenProvider->getToken('delete'.$result['id'])->getValue();
            $actions .= '<a href="javascript:void(0);" class="btn btn-sm btn-icon-only btn-danger confirmDeleteBox" data-message="Souhaitez-vous supprimer définitivement cet utilisateur ?<br/> Toutes les données rattachées à cet utilisateur seront automatiquement supprimées."
                            data-token="'.$token.'" data-url="'.$this->generateUrl('user_delete', ['id'=>$result['id']]).'" data-toggle="tooltip" data-original-title="Suppression"><i class="fas fa-trash"></i></a>';
            $actions .= '</div>';

            $row[] = $actions;

            $records["aaData"][] = $row;

        endforeach;

        return new response(json_encode($records));
    }

    #[Route(path: '/formulaire/{id}', requirements: [], name: 'user_form')]
    public function form(Request $request, $id = NULL)
    {
        $em = $this->doctrine;
        $session = $this->requestStack->getSession();

        if ($id) {
            $item = $em->getRepository(User::class)->find($id);
            $originalPassword = $item->getPassword();
        } else {
            $item = new User();
            $originalPassword = "";
        }
        $form = $this->createForm(UserType::class, $item, [
            
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $original_document = null;

            $checkUser = $em->getRepository(User::class)->findOneBy([
                'email' => $form['email']->getData()
            ]);

            if ($id) {
                $message =  "L'utilisateur a bien été modifié";
            } else {
                $message =  "L'utilisateur a bien été créé";
                $item->setToken(uniqid());

                if ($checkUser != null) {
                    return new Response(json_encode(['error' => 'Cette adresse e-mail est déjà utilisée pour un autre compte.']));
                }
            }

            $item = $form->getData();
            $entityManager = $this->doctrine->getManager();

            if ($form['password']->getData() && $form['password']->getData()  != null) {
                $password = $this->hasher->hashPassword($item, $form['password']->getData());
                $item->setPassword($password);
            } else {
                $item->setPassword($originalPassword);
            }

            $userRoles = $request->get('roles');
            if (in_array("ROLE_LIMEO", $item->getRoles())) {
                $userRoles[] = "ROLE_LIMEO";
            }
            if (in_array("ROLE_SUPER_ADMIN", $item->getRoles())) {
                $userRoles[] = "ROLE_SUPER_ADMIN";
            }
            if (in_array("ROLE_ALLOWED_TO_SWITCH", $item->getRoles())) {
                $userRoles[] = "ROLE_ALLOWED_TO_SWITCH";
            }

            if ($userRoles) {
                $item->setRoles($userRoles);
            } else {
                $item->setRoles([]);
            }

            $entityManager->persist($item);
            $entityManager->flush();

            if ($form['sendEmail']->getData()) {

                $item->setToken(uniqid());
                $entityManager->persist($item);
                $entityManager->flush();

                $url = $this->generateUrl('security_update_password', ['email' => $item->getEmail(), 'token' => $item->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);
                $mail = (new Email())
                    ->subject('Récupération de votre mot de passe')
                    ->from(new Address($_ENV['MAILER_ADDRESS'], $_ENV['MAILER_NAME']))
                    ->to($item->getEmail())
                    ->html($this->renderView('templates/emails/reset-pwd.html.twig', ['user' => $item, 'url' => $url]));

                $this->mailer->send($mail);
            }

            return new Response(json_encode([
                'success' =>  $message,
                'id' => $item->getId(),
                'name' => $item->getLastname() . " " . $item->getFirstname()
            ]));
        }
        // Réponse AJAX
        if ($form->isSubmitted() && !$form->isValid()) {
            return new Response(json_encode(['error' => $form->getErrors()]));
        }

        $roles = $em->getRepository(DataRole::class)->findBy(["is_exception" => 0], ['id' => 'ASC']);

        return $this->render('admin/user/form2.html.twig', ['item' => $item, 'roles' => $roles, 'form' => $form->createView()]);
    }
    #[Route(path: '/suppression/{id}', name: 'user_delete', methods: 'DELETE')]
    public function delete(Request $request, User $user)
    {
        $session = new Session();
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em = $this->em;
            $em->remove($user);
            $em->flush();
            $session->getFlashBag()->add('success', "La suppression a bien été effectuée");
        } else {
            $session->getFlashBag()->add('error', "Erreur !");
        }

        return $this->redirectToRoute('user_index', []);
    }

    #[Route(path: '/export-datas/{type}', name: 'export_user')]
    public function export(Request $request, Generator $generator, ManagerRegistry $em, $type = 'lottery')
    {
      ini_set('memory_limit', -1);
      ini_set('max_execution_time', -1);


      
      if ($type == 'lottery') {
        $sql = "
            SELECT u.*, g.instant_at, g.win_at, p.name
            FROM user_play as u
            LEFT JOIN gift as g on g.user_id = u.id
            LEFT JOIN product as p on p.id = g.product_id
            WHERE u.type != 'vote'";
      } 
      
      if ($type == 'vote') {
        $sql = "
            SELECT u.*, p.name
            FROM user_play as u
            LEFT JOIN user_vote as g on g.user_id = u.id
            LEFT JOIN candidate as p on p.id = g.candidate_id
            WHERE u.type = 'vote'";
      }

      $qb = $em->getConnection()->prepare($sql);
      $res = $qb->execute();
      $results = $res->fetchAll();

    if ($type == 'lottery') {
            $titles = [
                'Nom',
                'Prénom',
                'E-mail',
                'Téléphone',
                'Code postal',
                'optin1',
                'optin2',
                'Gain',
                'Magasin',
                'Instant gagnant à',
                'Date inscription'
            ];
            $columns = [
                'lastname',
                'firstname',
                'email',
                'phone',
                'zip',
                'optin1',
                'optin2',
                'name',
                'meta_data',
                'instant_at',
                'created_at'
            ];
    } else {
        $titles = [
            'Nom',
            'Prénom',
            'E-mail',
            'Téléphone',
            'Code postal',
            'optin1',
            'optin2',
            'Vote',
            'Campagne',
            'Date inscription'
        ];
        $columns = [
            'lastname',
            'firstname',
            'email',
            'phone',
            'zip',
            'optin1',
            'optin2',
            'name',
            'meta_data',
            'created_at'
        ];
    }

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

}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Service\Tool;
use App\Entity\User;

class SecurityController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $doctrine, private readonly RequestStack $requestStack)
    {
    }

    #[Route(path: '/admin/connexion', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
         return $this->redirectToRoute('admin_home_index');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/mot-de-passe-perdu', name: 'security_reset_password')]
    public function resetPassword(Request $request, Tool $tool, UrlGeneratorInterface $router, MailerInterface $mailer)
    {
        if ($this->getUser()) {
             return $this->redirectToRoute('admin_home_index');
        }

        if ($request->isMethod('post')) {
            $em = $this->doctrine->getManager();
            $session = $this->requestStack->getSession();


            if(!$tool->captchaverify($request->get('recaptcha_response'))) {
                $session->getFlashBag()->add('error', "Erreur de sécurité avec le captcha code.");
                return $this->redirectToRoute('security_reset_password');
            }

            $email = $request->request->get('email');
            $checkEmail = $em->getRepository(User::class)->findOneBy(["email" => $email]);

            if(!$checkEmail) {
                $session->getFlashBag()->add('error', "L'adresse e-mail renseignée n'existe pas.");
                return $this->redirectToRoute('security_reset_password');
            } else {

                $token = $tool->generateToken();
                $checkEmail->setToken($token);
                $em->persist($checkEmail);
                $em->flush();

                $url = $router->generate('security_update_password', ['email' =>$checkEmail->getEmail(), 'token' => $checkEmail->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);
                $message = (new Email())
                    ->subject('Récupération de votre mot de passe')
                    ->from(new Address($_ENV['MAILER_ADDRESS'], $_ENV['MAILER_NAME']))
                    ->to($checkEmail->getEmail())
                    ->html($this->renderView('templates/emails/reset-pwd.html.twig', ['user' => $checkEmail, 'url' => $url]));

                $mailer->send($message);
                $session->getFlashBag()->add('success', "Un e-mail de récupération de mot de passe a été envoyé.");
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', []);
    }

    #[Route(path: '/mise-a-jour-mot-de-passe/{email}/{token}', name: 'security_update_password')]
    public function updatePassword(Request $request, $email, $token, UserPasswordHasherInterface $hasher)
    {
        $em = $this->doctrine->getManager();
        $session = $this->requestStack->getSession();
        $checkUser = $em->getRepository(User::class)->findOneBy(["email" => $email, "token" => $token]);

        if(!$checkUser) {
            $session->getFlashBag()->add('error', "La page demandée n'existe pas.");
            return $this->redirectToRoute('security_reset_password');
        }

        if($request->isMethod('post')) {
            $password = $request->request->get('password');
            if (!hash_equals($request->request->get('password'), $request->request->get('confirm_password'))) {
               $session->getFlashBag()->add('error', "Les deux mots de passe sont différents");
                return $this->redirectToRoute('security_update_password', ['email' =>$checkUser->getEmail(), 'token' => $checkUser->getToken()]);
            }
            else if(!$password) {
                $session->getFlashBag()->add('error', "Le mot de passe est obligatoire");
                return $this->redirectToRoute('security_update_password', ['email' =>$checkUser->getEmail(), 'token' => $checkUser->getToken()]);
            } else {

                $password = $hasher->hashPassword($checkUser, $request->request->get('password'));
                $checkUser->setPassword($password);
                $checkUser->setToken(uniqid());
                $em->persist($checkUser);
                $em->flush();
                $session->getFlashBag()->add('success', "Votre mot de passe a été mis à jour !");
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/update_password.html.twig', []);
    }

    #[Route(path: '/changement-agence/{id}/{role_id}', name: 'switch_agency')]
    public function switchCustomer(AuthenticationUtils $authenticationUtils, $id, $role_id)
    {
      $em = $this->doctrine->getManager();
      $session = $this->requestStack->getSession();
      $user = $this->container->get('security.token_storage')->getToken()->getUser();

      $agency = $em->getRepository(Agency::class)->find($id);
      $role = $em->getRepository(DataRole::class)->find($role_id);

      $checkUserRole = $em->getRepository(UserAgency::class)->findOneBy(["agency" => $agency, "role" => $role]);



      if($checkUserRole) {
        $user->setAgency($agency);
        $user->setRole($role);
        $em->persist($user);
        $em->flush();
      } else if($id == 0 && $role_id == 0 && $this->isGranted('ROLE_ADMIN')) {
        $user->setAgency(NULL);
        $user->setRole(NULL);
        $em->persist($user);
        $em->flush();
      }
      //echo $user->getAgency()->getId();die;

      return $this->redirectToRoute('admin_home_index');
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout()
    {
        $session = $this->requestStack->getSession();
        $session->getFlashBag()->add('success', "Déconnexion !");

        return $this->redirectToRoute('app_login');
    }
}

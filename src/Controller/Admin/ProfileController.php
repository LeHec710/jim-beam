<?php

namespace App\Controller\Admin;

use App\Entity\DataRole;
use App\Entity\User;
use App\Form\ProfileType;

use App\Form\UserAvatarType;
use App\Service\FileUploader;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;



#[Route(path: '/admin/mon-profil')]
class ProfileController extends AbstractController
{
    public function __construct(ManagerRegistry $em)
    {
        $this->em = $em->getManager();
    }
    #[Route(path: '/', name: 'profile')]
    public function index(Request $request, UserPasswordHasherInterface $hasher, ManagerRegistry $em)
    {
        $item = $this->getUser();
        $session = new Session();

        $originalPassword = $item->getPassword();

        $form = $this->createForm(ProfileType::class, $item);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            if($form['password']->getData()) {
                $password = $hasher->hashPassword($item,  $form['password']->getData());
                $item->setPassword($password);

            } else {
                $item->setPassword($originalPassword);
            }

            $em->getManager()->persist($user);
            $em->getManager()->flush();
            $session->getFlashBag()->add('success', "Les informations ont bien été modifiées");
        }


        $roles = $em->getRepository(DataRole::class)->findBy([], ['id' => 'ASC']);

        return $this->render('admin/profile/index.html.twig', ['item' => $item, 'roles' => $roles, 'form' => $form->createView()]);
    }

    #[Route(path: '/formulaire-avatar/', requirements: [], name: 'profile_form_avatar')]
    public function formAvatar(Request $request, FileUploader $fileUploader)
    {
        $em = $this->em->getManager();
        $user = $this->getUser();

        $item = $em->getRepository(User::class)->find($user->getId());

        $form = $this->createForm(UserAvatarType::class, $item);

        $form->handleRequest($request);
        $dir = $this->getParameter('kernel.project_dir').'/public/uploads/users/avatars/';

        if ($form->isSubmitted() || $request->getMethod() == "POST") {
            $extension = "png";
            $filename = uniqid();


            if($request->get('reset') == 1) {
                if($item->getAvatar()) {
                    if($item->getAvatar() && file_exists($dir."original_".$item->getAvatar())) {
                        unlink($dir."original_".$item->getAvatar());
                    }
                }
            }

            if($_FILES && $_FILES['picture']) {
                if($item->getAvatar()) {
                    if($item->getAvatar() && file_exists($dir.$item->getAvatar())) {
                        unlink($dir.$item->getAvatar());
                    }
                }

                $extension = substr(strrchr((string) $_FILES['picture']['name'], "."), 1);
                $filename_original = "original_".$filename.".".$extension;

                if (move_uploaded_file($_FILES['picture']['tmp_name'], $dir.$filename_original)) {
                    $item->setAvatar($filename.".".$extension);
                }
            }

            $filename_thumb = $filename.".".$extension;

            if($request->get('thumb')) {
                if($item->getAvatar() && file_exists($dir.$item->getAvatar())) {
                    unlink($dir.$item->getAvatar());
                }

                $this->base64_to_jpeg($request->get('thumb'), $dir.$filename_thumb);
                $item->setAvatar($filename_thumb);
            }


            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($item);
            $entityManager->flush();

            $session = new Session();
            $session->getFlashBag()->add('success', "La photo a bien été mise à jour");

            $url = $this->generateUrl('profile', [], UrlGeneratorInterface::ABSOLUTE_URL);
            return new response($url);
        }

        if($item->getAvatar() && file_exists($dir."original_".$item->getAvatar())) {
            $original = true;
        } else {
            $original = false;
        }

        return $this->render('admin/profile/form_avatar.html.twig', ['item' => $item, 'form' => $form->createView(), 'original' => $original]);

    }

    private  function base64_to_jpeg($base64_string, $output_file) {
        $ifp = fopen( $output_file, 'wb' );
        $data = explode( ',', (string) $base64_string );
        fwrite( $ifp, base64_decode( $data[ 1 ] ) );
        fclose($ifp);
        return $output_file;
    }
}

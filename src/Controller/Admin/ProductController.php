<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\ProductPasswordEncoderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Service\FileUploader;
use App\Service\Tool;

use App\Entity\Product;
use App\Entity\DataRole;
use App\Form\ProductType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[Route(path: '/admin/product')]
#[Security("is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN')")]
class ProductController extends AbstractController
{

    private $session;
    public function __construct(private EntityManagerInterface $em, private RequestStack $requestStack)
    {
        $this->session = $this->requestStack->getSession();
    }

    #[Route(path: '/liste', name: 'product_index')]
    public function index()
    {
        return $this->render('admin/product/index.html.twig', []);
    }

    #[Route(path: '/donnees/', name: 'product_datas')]
    public function datasAction(Request $request, Tool $tool)
    {
        $em = $this->em;

        $sorts = array(
            'u.name',
            'u.city',
            'u.zip',
        );

        $sql = "SELECT u.*
        FROM product as u
        WHERE u.id > 0 ";


        $records = $tool->getScriptTable($sql, $sorts, $em); // SCRIPT PHP NON MODIFIABLE  DE REQUETES POUR RESULTATS TABLEAU DATATABLE JSON

        foreach ($records['results'] as $result) :

            $startAt = (new \DateTime($result['start_at']))->format('d/m/Y');
            $endAt = (new \DateTime($result['end_at']))->format('d/m/Y');

            $row = array(
                '<img src="../../uploads/' . $result['icon'] . '" class="card p-1 m-0" height="60" >',
                $result['name'],
                $result['token'],
                $startAt,
                $endAt,
                0,
            );

            $actions = '<div class="md-btn-group">';

            $actions .= '<a data-id="' . $result['id'] . '" class="editItem btn btn-icon-only btn-primary btn-sm" data-toggle="tooltip" data-original-title="Modifier"><i class="fas fa-edit"></i></a>';


            $tokenProvider = $this->container->get('security.csrf.token_manager');
            $token = $tokenProvider->getToken('delete' . $result['id'])->getValue();

            $actions .= '<a href="javascript:void(0);" class="btn btn-sm btn-icon-only btn-danger confirmDeleteBox" data-message="Souhaitez-vous supprimer définitivement cette résidence ?<br/> Toutes les données rattachées à cette résidence seront automatiquement supprimées."data-token="' . $token . '" data-url="' . $this->generateUrl('product_delete', array('id' => $result['id'])) . '" data-toggle="tooltip" data-original-title="Suppression"><i class="fas fa-trash"></i></a>';
            $actions .= '</div>';

            $row[] = $actions;

            $records["aaData"][] = $row;

        endforeach;

        return new response(json_encode($records));
    }

    #[Route(path: '/formulaire/{lotteryId}/{id?}', requirements: [], name: 'product_form')]
    public function form(Request $request, $id = NULL, FileUploader $fileUploader, UrlGeneratorInterface $router, $lotteryId)
    {
        $em = $this->em;
        $session = $this->session;

        if ($id) {
            $item = $em->getRepository(Product::class)->find($id);
        } else {
            $item = new Product();
        }

        $form = $this->createForm(ProductType::class, $item);
        $form->handleRequest($request);
        $lottery = $em->getRepository(\App\Entity\Lottery::class)->find($lotteryId);

        if ($form->isSubmitted() && $form->isValid()) {


            $item = $form->getData();
            $item->setLottery($lottery);
            $em = $this->em;


            $file = $form['picture']->getData();
            $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/';

            if ($file) {
                $fileName = $fileUploader->upload($file, $dir);
                $item->setPicture($fileName);
            }
            $em->persist($item);
            $lottery->addProduct($item);
            $em->persist($lottery);
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

        return $this->render('admin/product/form.html.twig', array(
            'item' => $item,
            'lottery' => $lottery,
            'form' => $form->createView()
        ));
    }

    #[Route(path: '/suppression/{id}', name: 'product_delete', methods: 'DELETE')]
    public function delete(Request $request, Product $product)
    {
        $session = $this->session;
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $em = $this->em;
            $em->remove($product);
            $em->flush();
            $session->getFlashBag()->add('success', "La suppression a bien été effectuée");
        } else {
            $session->getFlashBag()->add('error', "Erreur !");
        }

        return $this->redirectToRoute('product_index', []);
    }
}

<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use App\Service\Tool;

use App\Entity\DataRole;
use Doctrine\Persistence\ManagerRegistry;

#[Route(path: '/admin/switch')]
#[Security("is_granted('ROLE_ALLOWED_TO_SWITCH')")]
class SwitchController extends AbstractController
{
    public function __construct(private readonly ManagerRegistry $em)
    {
    }
    #[Route(path: '/', name: 'switch_home')]
    public function index()
    {
        $em = $this->em->getManager();
        $roles = $em->getRepository(DataRole::class)->findBy([], ['name' => 'ASC']);

        return $this->render('admin/switch/index.html.twig', ["roles" => $roles]);
    }

    #[Route(path: '/donnees', name: 'switch_datas')]
    public function switchDatasAction(Request $request, Tool $tool)
    {
        $em = $this->em->getManager();

        $sorts = ['u.lastname', 'u.firstname', 'u.roles'];

        $sql = "SELECT u.*
            FROM user as u
            WHERE u.id > 0 ";

        $records = $tool->getScriptTable($sql, $sorts, $em); // SCRIPT PHP NON MODIFIABLE  DE REQUETES POUR RESULTATS TABLEAU DATATABLE JSON


        $dataRoles = $em->getRepository(DataRole::class)->findBy(["is_exception" => 0], ['name' => 'ASC']);

        $trans = [];
        foreach($dataRoles as $dataRole) {
            $trans[$dataRole->getSlug()] = $dataRole->getName();
        }

        foreach ($records['results'] as $result):

            $roles = str_replace('"', "", (string) $result['roles']);
            $roles = str_replace('[', "", $roles);
            $roles = str_replace(']', "", $roles);
            $roles = explode(",", $roles);
            $displayRoles = NULL;

            foreach($roles as $role) {
                if(isset($trans[$role])) {
                    $displayRoles .= $trans[$role].",";
                }
            }

            $row = [$result['lastname'], $result['firstname'], $displayRoles];

            $actions = '<div class="md-btn-group">';
            $actions .= '<a href="'.$this->generateUrl('admin_home_index', ['_switch_user'=>$result['email']]).'" class="btn btn-icon-only btn-primary btn-sm" data-toggle="tooltip" data-original-title="Switcher"><i class="fas fa-user-friends"></i></a>';
            $actions .= '</div>';
            $row[] = $actions;

            $records["aaData"][] = $row;

        endforeach;

        return new response(json_encode($records));
    }

}

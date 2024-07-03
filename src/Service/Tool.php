<?php

namespace App\Service;
#use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Persistence\ManagerRegistry;

class Tool
{
    protected $em;

    public function __construct(ManagerRegistry $em)
    {
        $this->em       = $em;
    }

    public function getName() {
        return 'Tool';
    }

    public function getScriptTable($sql,$sorts, $em, $groupBy = NULL) {

        $records = [];

        foreach($_REQUEST as $key => $value) {
            if(preg_match('#sSearch_([0-9]+)#is', $key, $out)) {
                if(isset($sorts[$out[1]]) && strlen((string) $_REQUEST[$out[0]]) > 0) {
                    if($_REQUEST[$out[0]] == "NOT_NULL") {
                        $sql .= " AND " . $sorts[$out[1]]. " IS NOT NULL ";
                    } elseif($_REQUEST[$out[0]] == "IS_NULL") {
                        $sql .= " AND " . $sorts[$out[1]]. " IS NULL ";
                    } elseif($_REQUEST[$out[0]] == "NO_NEW") {
                        $sql .= " AND " . $sorts[$out[1]]. " = 0 ";
                    } elseif($_REQUEST[$out[0]] == "HAS_NEW") {
                        $sql .= " AND " . $sorts[$out[1]]. " > 0 ";
                    } else  {
                        $sql .= " AND " . $sorts[$out[1]]. ' LIKE  "%'. $_REQUEST[$out[0]] .'%" ';
                    }
                }
            }
        }

        $qb = $em->getConnection()->prepare($sql);
        $res = $qb->execute();
        $counts = $res->fetchAll();
        $iTotalRecords = count($counts);
        $totalDisplayed = intval($_REQUEST['iDisplayLength']);
        $iDisplayLength = $totalDisplayed < 0 ? $iTotalRecords : $totalDisplayed;
        $records["iTotalDisplayRecords"]  = $records["iTotalRecords"] = $iTotalRecords;

        if($groupBy) {
            $sql.= $groupBy;
        }

        $sql .= ' ORDER BY ' . $sorts[$_REQUEST['iSortCol_0']] . '  ' . $_REQUEST['sSortDir_0']  . ' LIMIT ' . intval($_REQUEST['iDisplayStart']) . ', ' . $iDisplayLength;

        //echo $sql;die;
        $qb = $em->getConnection()->prepare($sql);
        $res = $qb->execute();
        $records['results'] = $res->fetchAll();
        $records["aaData"] = [];

        return $records;
    }

    public function generateToken($table = "User") {
        $length = '30';
        $chaine = 'azertyuiopqsdfghjklmwxcvbn123456789';
        $nb_lettres = strlen($chaine) - 1;
        $generation = '';

        for($i=0; $i < $length; $i++) {
            $pos = mt_rand(0, $nb_lettres);
            $car = $chaine[$pos];
            $generation .= $car;
        }

        $tokenCheck = $this->em->getRepository('App:'.$table)->findOneBy(["token" => $generation]);

        if($tokenCheck != NULL) {
            $this->makeToken($table);
        }
        else {
            return $generation;
        }
    }

    public function captchaverify($recaptcha){

            $url = "https://www.google.com/recaptcha/api/siteverify";
            try {
              $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL, $url);
              curl_setopt($ch, CURLOPT_HEADER, 0);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
              curl_setopt($ch, CURLOPT_POST, true);
              curl_setopt($ch, CURLOPT_POSTFIELDS, ["secret"=>$_ENV['RECAPTCHA_SECRET'], "response"=>$recaptcha]);
              $response = curl_exec($ch);

              // Check the return value of curl_exec(), too
              if ($response === false) {
                  throw new Exception(curl_error($ch), curl_errno($ch));
              }

              curl_close($ch);
            } catch(Exception $e) {

              trigger_error(sprintf(
                  'Curl failed with error #%d: %s',
                  $e->getCode(), $e->getMessage()),
                  E_USER_ERROR);

            }
            $data = json_decode($response);

        return $data->success;
    }
}

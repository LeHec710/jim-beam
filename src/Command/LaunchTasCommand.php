<?php

namespace App\Command;

use App\Entity\Gift;
use App\Entity\Lottery;
use App\Entity\UserPlay;
use Doctrine\ORM\EntityManagerInterface;
use Swift_Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

class LaunchTasCommand extends Command
{
    protected static $defaultName = 'app:launchTas';
    protected static $defaultDescription = 'Execution des TAS disponibles';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, Environment $twig)
    {
        $this->em = $entityManager;
        $this->twig = $twig;
        $this->mailer = $mailer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $lotteries = $this->em->getRepository(Lottery::class)->findAll();
        $count = 0;
        foreach ($lotteries as $lottery ) {

            $now = new \DateTime('now');
            $end = $lottery->getEndAt()->format("d/m/Y H:i:s");

            $check = date('Y-m-d H:i:s', strtotime($end . ' +1 day'));
            //$stop_date->modify('+1 day');
            echo $check;die;
            die;
            if ($lottery->getEndAt() <= $now) {

                $gifts = $lottery->getGifts();
                if (count($gifts) == 0) {

                    $start = $lottery->getStartAt();
                    $end = $lottery->getEndAt();

                    $sql = "SELECT * FROM user_play
                    WHERE theme = ".$lottery->getTheme()."
                    AND created_at > '". $start->format('Y-m-d H:i:s'). "'
                    AND created_at < '" . $end->format('Y-m-d H:i:s') . "'
                    ";
                    $qb = $this->em->getConnection()->prepare($sql);
                    $qb->execute();
                    $players = $qb->fetchAll();

                    $result = mt_rand(0, count($players) - 1);
                    $index = $players[$result]['id'];
                    $winner = $this->em->getRepository(UserPlay::class)->find($index);
                    
                    $gift = new Gift();
                    $gift->setUser($winner);
                    $gift->setLottery($lottery);
                    $gift->setProduct($lottery->getProducts()[0]);
                    $gift->setInstantAt($now);
                    $gift->setIsDelivered(0);
                    $gift->setWinAt($now);
                    $gift->setCode('');

                    $winner->setGift($gift);
                    $this->em->persist($gift);
                    $this->em->persist($winner);
                    $this->em->flush();


                    // $message = (new \Swift_Message())
                    // ->setSubject('Vous avez gagné !')
                    // ->setFrom(array($_ENV['MAILER_ADDRESS'] => $_ENV['MAILER_NAME']))
                    // ->setTo($winner->getEmail())
                    // ->setContentType('text/html')
                    // ->setBody($this->twig->render('admin/templates/emails/winner.html.twig', array('user' => $winner, 'gain' => $gift->getProduct()->getName())));

                    // $this->mailer->send($message);

                    $count++;
                }
            }
        }
        $io->success($count.' TAS executé(s)');

        return Command::SUCCESS;
    }
}

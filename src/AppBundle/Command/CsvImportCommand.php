<?php

namespace AppBundle\Command;

use AppBundle\Entity\Athlete;
use AppBundle\Entity\Competitor;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class CsvImportCommand extends Command
{
    // the name of the command (the part after "bin/console")
    //protected static $defaultName = 'app:create-user';
    
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }   
    protected function configure()
    {
        $this
            ->setName('csv:import')
            ->setDescription('Imports a mock csv file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Attempting to import the feed ...');

        $reader = Reader::createFromPath('%kernel.root_dir%/../src/AppBundle/Data/MOCK_DATA.csv');

        $result = $reader->fetchAssoc();

        $io->progressStart(iterator_count($result));
        
        foreach ($result as $row) {

            $athlete = (new Athlete())
            ->setFirstName($row['first_name'])
            ->setLastName($row['last_name'])
            ->setDateOfBirth(new \DateTime($row['date_of_birth']))
            ->setWeight($row['weight'])
        ;

        $this->em->persist($athlete);
       
        $competitor = $this->em->getRepository('AppBundle:Competitor')
            ->findOneBy([
                'category' => $row['category'],
                'competition' => $row['competition']
            ]);

        if ($competitor === null) {
            $competitor = (new Competitor())
            ->setCategory($row['category'])
            ->setCompetition($row['competition'])
            ;
            $this->em->persist($competitor);

            $this->em->flush();
            }

        $athlete->setCompetitor($competitor);

        $io->progressAdvance();
        }


        
        $this->em->flush();
        $io->progressFinish();
        $io->success('Everything went well!');
    }
}
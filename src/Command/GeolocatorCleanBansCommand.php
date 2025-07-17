<?php

namespace GeolocatorBundle\Command;

use GeolocatorBundle\Service\BanManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeolocatorCleanBansCommand extends Command
{
    protected static $defaultName = 'geolocator:clean-bans';
    protected static $defaultDescription = 'Nettoie les bans expirés';

    private BanManager $banManager;

    public function __construct(BanManager $banManager)
    {
        parent::__construct();
        $this->banManager = $banManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Nettoyage des bans expirés');

        $count = $this->banManager->cleanExpiredBans();

        if ($count > 0) {
            $io->success("$count bans expirés ont été supprimés");
        } else {
            $io->info("Aucun ban expiré à supprimer");
        }

        return Command::SUCCESS;
    }
}

<?php

namespace GeolocatorBundle\Command;

use GeolocatorBundle\Service\BanManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeolocatorListBansCommand extends Command
{
    protected static $defaultName = 'geolocator:list-bans';
    protected static $defaultDescription = 'Liste toutes les IPs bannies';

    private BanManager $banManager;

    public function __construct(BanManager $banManager)
    {
        parent::__construct();
        $this->banManager = $banManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Liste des IPs bannies');

        $bans = $this->banManager->getAllBans();

        if (empty($bans)) {
            $io->info('Aucune IP bannie');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($bans as $ip => $banInfo) {
            $expiration = $banInfo['expiration'] ? (new \DateTime($banInfo['expiration']))->format('Y-m-d H:i:s') : 'Permanent';
            $timestamp = date('Y-m-d H:i:s', $banInfo['timestamp']);

            $rows[] = [
                $ip,
                $banInfo['reason'],
                $expiration,
                $timestamp
            ];
        }

        $io->table(['IP', 'Raison', 'Expiration', 'Date de ban'], $rows);

        return Command::SUCCESS;
    }
}

<?php

namespace GeolocatorBundle\Command;

use GeolocatorBundle\Service\BanManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeolocatorUnbanCommand extends Command
{
    protected static $defaultName = 'geolocator:unban';
    protected static $defaultDescription = 'Débannit une adresse IP';

    private BanManager $banManager;

    public function __construct(BanManager $banManager)
    {
        parent::__construct();
        $this->banManager = $banManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('ip', InputArgument::REQUIRED, 'Adresse IP à débannir')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ip = $input->getArgument('ip');

        if (!$this->banManager->isBanned($ip)) {
            $io->warning("L'adresse IP $ip n'est pas bannie");
            return Command::INVALID;
        }

        $this->banManager->unbanIp($ip);

        $io->success("L'adresse IP $ip a été débannie avec succès");

        return Command::SUCCESS;
    }
}

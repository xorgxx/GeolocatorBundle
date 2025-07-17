<?php

namespace GeolocatorBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GeolocatorBundle\Service\BanManager;
use GeolocatorBundle\Service\IpResolver;

#[AsCommand(
    name: 'geolocator:ban:remove',
    description: 'Supprime une adresse IP de la liste des bannis'
)]
class BanRemoveCommand extends Command
{
    private $banManager;
    private $ipResolver;
    private $enabled;

    public function __construct(BanManager $banManager, IpResolver $ipResolver, bool $enabled)
    {
        $this->banManager = $banManager;
        $this->ipResolver = $ipResolver;
        $this->enabled = $enabled;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('ip', InputArgument::REQUIRED, 'L\'adresse IP à débannir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->enabled) {
            $io->error('Le service de géolocalisation est désactivé.');
            return Command::FAILURE;
        }

        $ipAddress = $input->getArgument('ip');

        // Valider l'adresse IP
        if (!$this->ipResolver->isValidIp($ipAddress)) {
            $io->error(sprintf('L\'adresse IP "%s" n\'est pas valide.', $ipAddress));
            return Command::FAILURE;
        }

        // Vérifier si l'IP est bannie
        if (!$this->banManager->isBanned($ipAddress)) {
            $io->warning(sprintf('L\'adresse IP "%s" n\'est pas bannie.', $ipAddress));
            return Command::SUCCESS;
        }

        // Débannir l'IP
        $this->banManager->unbanIp($ipAddress);

        $io->success(sprintf('L\'adresse IP "%s" a été débannie avec succès.', $ipAddress));

        return Command::SUCCESS;
    }
}

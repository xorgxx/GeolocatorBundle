<?php

namespace GeolocatorBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GeolocatorBundle\Service\GeolocatorService;

#[AsCommand(
    name: 'geolocator:test-ip',
    description: 'Teste la géolocalisation d\'une adresse IP'
)]
class TestIpCommand extends Command
{
    private $geolocator;
    private $enabled;

    public function __construct(GeolocatorService $geolocator, bool $enabled)
    {
        $this->geolocator = $geolocator;
        $this->enabled = $enabled;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('ip', InputArgument::REQUIRED, 'L\'adresse IP à tester');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->enabled) {
            $io->error('Le service de géolocalisation est désactivé.');
            return Command::FAILURE;
        }

        $ipAddress = $input->getArgument('ip');

        try {
            $result = $this->geolocator->getGeoLocation($ipAddress);

            if (!$result) {
                $io->warning(sprintf('Impossible de géolocaliser l\'adresse IP "%s".', $ipAddress));
                return Command::FAILURE;
            }

            $io->success(sprintf('Géolocalisation de l\'adresse IP "%s" réussie.', $ipAddress));

            $io->table(
                ['Propriété', 'Valeur'],
                [
                    ['IP', $result->getIp()],
                    ['Pays', $result->getCountry()],
                    ['Code pays', $result->getCountryCode()],
                    ['Région', $result->getRegion()],
                    ['Ville', $result->getCity()],
                    ['Latitude', $result->getLatitude()],
                    ['Longitude', $result->getLongitude()],
                    ['Fournisseur', $result->getProvider()],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de la géolocalisation de l\'adresse IP "%s" : %s', $ipAddress, $e->getMessage()));
            return Command::FAILURE;
        }
    }
}

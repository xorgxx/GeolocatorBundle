<?php

namespace GeolocatorBundle\Command;

use GeolocatorBundle\Service\GeolocatorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeolocatorTestCommand extends Command
{
    protected static $defaultName = 'geolocator:test';
    protected static $defaultDescription = 'Teste la géolocalisation d\'une adresse IP';

    private GeolocatorService $geolocator;

    public function __construct(GeolocatorService $geolocator)
    {
        parent::__construct();
        $this->geolocator = $geolocator;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('ip', InputArgument::OPTIONAL, 'Adresse IP à tester', '8.8.8.8')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ip = $input->getArgument('ip');

        $io->title('Test de géolocalisation');
        $io->text("Vérification de l'adresse IP: $ip");

        try {
            $geoLocation = $this->geolocator->getGeoLocation($ip);

            $io->success("Géolocalisation réussie");

            $data = [
                'IP' => $geoLocation->getIp(),
                'Pays' => $geoLocation->getCountryCode() . ' (' . $geoLocation->getCountryName() . ')',
                'Région' => $geoLocation->getRegionName(),
                'Ville' => $geoLocation->getCity(),
                'Latitude' => $geoLocation->getLatitude(),
                'Longitude' => $geoLocation->getLongitude(),
                'VPN' => $geoLocation->isVpn() ? 'Oui' : 'Non',
                'Proxy' => $geoLocation->isProxy() ? 'Oui' : 'Non',
                'Tor' => $geoLocation->isTor() ? 'Oui' : 'Non',
                'FAI' => $geoLocation->getIsp(),
                'Organisation' => $geoLocation->getOrg(),
                'ASN' => $geoLocation->getAsn(),
                'Fuseau horaire' => $geoLocation->getTimezone(),
            ];

            $rows = [];
            foreach ($data as $key => $value) {
                if ($value !== null) {
                    $rows[] = [$key, $value];
                }
            }

            $io->table(['Propriété', 'Valeur'], $rows);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Erreur lors de la géolocalisation: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

<?php

namespace GeolocatorBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GeolocatorBundle\Service\Geolocator;
use GeolocatorBundle\Service\Storage;

#[AsCommand(
    name: 'geolocator:check-config',
    description: 'Vérifie la configuration du service de géolocalisation'
)]
class CheckConfigCommand extends Command
{
    private $geolocator;
    private $storage;
    private $config;

    public function __construct(Geolocator $geolocator, Storage $storage, array $config)
    {
        $this->geolocator = $geolocator;
        $this->storage = $storage;
        $this->config = $config;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification de la configuration du service de géolocalisation');

        // Vérifier si le service est activé
        $enabled = $this->config['enabled'] ?? false;
        $io->section('État du service');
        if ($enabled) {
            $io->success('Le service de géolocalisation est activé.');
        } else {
            $io->warning('Le service de géolocalisation est désactivé.');
        }

        // Vérifier les fournisseurs de géolocalisation
        $io->section('Fournisseurs de géolocalisation');
        $providers = $this->config['providers'] ?? [];

        if (empty($providers)) {
            $io->warning('Aucun fournisseur de géolocalisation n\'est configuré.');
        } else {
            $tableData = [];
            foreach ($providers as $name => $providerConfig) {
                $tableData[] = [
                    $name,
                    $providerConfig['enabled'] ?? false ? 'Oui' : 'Non',
                    $providerConfig['priority'] ?? 0
                ];
            }

            $io->table(['Fournisseur', 'Activé', 'Priorité'], $tableData);
        }

        // Vérifier la base de données/stockage
        $io->section('Stockage');
        $storageStatus = $this->storage->checkConnection();

        if ($storageStatus) {
            $io->success('La connexion au stockage est fonctionnelle.');
            $io->info(sprintf('Nombre d\'adresses IP bannies : %d', count($this->storage->getBannedIps())));
        } else {
            $io->error('La connexion au stockage a échoué.');
        }

        return Command::SUCCESS;
    }
}

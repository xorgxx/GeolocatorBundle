<?php

namespace GeolocatorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GeolocatorBundle\Service\BanManager;
use GeolocatorBundle\Export\FirewallExporter;

class ExportFirewallCommand extends Command
{
    protected static $defaultName = 'xorg:geolocator:export-firewall';

    private BanManager $banManager;
    private FirewallExporter $exporter;

    public function __construct(BanManager $banManager, FirewallExporter $exporter)
    {
        parent::__construct();
        $this->banManager = $banManager;
        $this->exporter = $exporter;
    }

    protected function configure()
    {
        $this->addArgument('format', InputArgument::REQUIRED, 'Format: iptables, nginx, csv');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getArgument('format');
        $ips = array_keys($this->banManager->listBans());
        $result = $this->exporter->export($ips, $format);
        $output->writeln($result);
        return Command::SUCCESS;
    }
}

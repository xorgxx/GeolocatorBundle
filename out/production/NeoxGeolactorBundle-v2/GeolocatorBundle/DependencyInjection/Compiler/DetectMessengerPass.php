<?php

namespace GeolocatorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DetectMessengerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Vérifier si Messenger est disponible
        $messengerAvailable = class_exists('Symfony\\Component\\Messenger\\MessageBusInterface');
        $container->setParameter('geolocator.messenger_available', $messengerAvailable);

        // Vérifier si RabbitMQ est disponible
        $rabbitAvailable = class_exists('Symfony\\Component\\Messenger\\Bridge\\Amqp\\Transport\\AmqpTransportFactory');
        $container->setParameter('geolocator.rabbit_available', $rabbitAvailable);

        // Vérifier si Redis Messenger est disponible
        $redisMessengerAvailable = class_exists('Symfony\\Component\\Messenger\\Bridge\\Redis\\Transport\\RedisTransportFactory');
        $container->setParameter('geolocator.redis_messenger_available', $redisMessengerAvailable);

        // Vérifier si Mercure est disponible
        $mercureAvailable = class_exists('Symfony\\Component\\Mercure\\HubInterface');
        $container->setParameter('geolocator.mercure_available', $mercureAvailable);

        // Vérifier si Predis est disponible
        $predisAvailable = class_exists('Predis\\Client');
        $container->setParameter('geolocator.predis_available', $predisAvailable);
    }
}

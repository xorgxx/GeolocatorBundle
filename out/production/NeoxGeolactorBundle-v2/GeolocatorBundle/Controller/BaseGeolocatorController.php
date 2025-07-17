<?php

namespace GeolocatorBundle\Controller;

use GeolocatorBundle\Attribute\GeoConfig;
use GeolocatorBundle\Service\GeolocatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Contrôleur de base pour les contrôleurs qui ont besoin d'accéder à la configuration
 */
abstract class BaseGeolocatorController extends AbstractController
{
    /**
     * @var GeolocatorService Service de géolocalisation
     */
    protected GeolocatorService $geolocator;

    /**
     * @var bool État d'activation du bundle
     */
    protected bool $enabled;

    /**
     * @var bool Mode simulation
     */
    protected bool $simulate;

    /**
     * @var string URL de redirection en cas de bannissement
     */
    protected string $redirectOnBan;

    public function __construct(
        GeolocatorService $geolocator,
        bool $enabled = true,
        bool $simulate = false,
        string $redirectOnBan = '/banned'
    ) {
        $this->geolocator = $geolocator;
        $this->enabled = $enabled;
        $this->simulate = $simulate;
        $this->redirectOnBan = $redirectOnBan;
    }

    /**
     * Méthode utilitaire pour obtenir une valeur de configuration spécifique
     * 
     * @param string $key Clé de configuration (notation à points)
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        // Récupérer la configuration du service global
        $config = $this->container->get('geolocator.config_object');

        // Utiliser la réflexion pour accéder aux propriétés à partir de la notation à points
        $parts = explode('.', $key);
        $value = $config;

        foreach ($parts as $part) {
            // Convertir la notation à points en camelCase pour les propriétés
            $part = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $part))));

            $reflection = new \ReflectionObject($value);
            if (!$reflection->hasProperty($part)) {
                return $default;
            }

            $property = $reflection->getProperty($part);
            $value = $property->getValue($value);

            if (is_array($value) && count($parts) > 1) {
                // Si c'est un tableau et qu'il reste des parties, rechercher dans le tableau
                return $value[array_shift($parts)] ?? $default;
            }
        }

        return $value ?? $default;
    }
}

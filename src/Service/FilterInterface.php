<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Définit un filtre IP/Géo qui décide si une requête doit être bloquée.
 */
interface FilterInterface
{
    /**
     * Applique le filtre à une requête donnée.
     *
     * @param Request $request  La requête HTTP en cours.
     * @param array   $geoData  Données de géolocalisation (country, continent, is_vpn, asn, isp…).
     *
     * @return bool  true pour bloquer la requête, false pour la laisser passer.
     */
    public function apply(Request $request, array $geoData): bool;
}

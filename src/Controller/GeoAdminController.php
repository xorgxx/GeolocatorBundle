<?php

namespace GeolocatorBundle\Controller;

use GeolocatorBundle\Service\BanManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/geo', name: 'geolocator_admin_')]
class GeoAdminController extends AbstractController
{
    private BanManager $banManager;

    public function __construct(BanManager $banManager)
    {
        $this->banManager = $banManager;
    }

    /**
     * Tableau de bord d'administration de la géolocalisation
     */
    #[Route('', name: 'dashboard')]
    public function dashboard(): Response
    {
        $activeBans = $this->banManager->getAllBans();

        return $this->render('@Geolocator/admin/dashboard.html.twig', [
            'active_bans' => $activeBans,
            'total_bans' => count($activeBans),
        ]);
    }

    /**
     * Liste des IP bannies
     */
    #[Route('/bans', name: 'bans')]
    public function listBans(): Response
    {
        $bans = $this->banManager->getAllBans();

        return $this->render('@Geolocator/admin/bans.html.twig', [
            'bans' => $bans,
        ]);
    }

    /**
     * Débannir une adresse IP
     */
    #[Route('/unban/{ip}', name: 'unban')]
    public function unbanIp(string $ip, Request $request): Response
    {
        if ($this->banManager->isBanned($ip)) {
            $this->banManager->unbanIp($ip);
            $this->addFlash('success', "L'adresse IP {$ip} a été débannie avec succès.");
        } else {
            $this->addFlash('warning', "L'adresse IP {$ip} n'est pas bannie.");
        }

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('geolocator_admin_bans'));
    }

    /**
     * Bannir manuellement une adresse IP
     */
    #[Route('/ban', name: 'ban', methods: ['POST'])]
    public function banIp(Request $request): Response
    {
        $ip = $request->request->get('ip');
        $reason = $request->request->get('reason', 'Bannissement manuel');
        $permanent = $request->request->getBoolean('permanent', false);

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->addFlash('error', "L'adresse IP {$ip} n'est pas valide.");
        } else {
            if ($permanent) {
                $this->banManager->banIpPermanently($ip, $reason);
                $this->addFlash('success', "L'adresse IP {$ip} a été bannie définitivement.");
            } else {
                $expiration = $this->banManager->banIp($ip, $reason);
                $expirationStr = $expiration ? $expiration->format('d/m/Y à H:i') : 'indéterminé';
                $this->addFlash('success', "L'adresse IP {$ip} a été bannie jusqu'au {$expirationStr}.");
            }
        }

        return $this->redirectToRoute('geolocator_admin_bans');
    }

    /**
     * Nettoyer les bans expirés
     */
    #[Route('/clean-expired', name: 'clean_expired')]
    public function cleanExpiredBans(): Response
    {
        $count = $this->banManager->cleanExpiredBans();

        if ($count > 0) {
            $this->addFlash('success', "{$count} bans expirés ont été supprimés.");
        } else {
            $this->addFlash('info', "Aucun ban expiré à supprimer.");
        }

        return $this->redirectToRoute('geolocator_admin_bans');
    }
}

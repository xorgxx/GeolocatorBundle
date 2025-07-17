            // 4. Vérification des crawlers
            if (isset($attributeConfig['crawler_filter']) && is_array($attributeConfig['crawler_filter'])) {
                $crawlerConfig = $attributeConfig['crawler_filter'];

                if (isset($crawlerConfig['enabled']) && $crawlerConfig['enabled']) {
                    // Utiliser la méthode améliorée de détection des crawlers qui fournit plus d'informations
                    $detectionResult = $this->geolocator->getCrawlerFilter()->detectCrawler($request, $geoLocation);

                    if ($detectionResult['isCrawler']) {
                        $shouldBlock = $detectionResult['isKnown'] 
                            ? (!isset($crawlerConfig['allow_known']) || !$crawlerConfig['allow_known']) 
                            : true; // Par défaut, bloquer les crawlers non connus

                        if ($shouldBlock) {
                            $reason = 'Crawler détecté' . ($detectionResult['name'] ? ' (' . $detectionResult['name'] . ')' : '');
                            return new BanResult(true, $reason, $ip, $geoLocation);
                        }
                    }
                }
            }

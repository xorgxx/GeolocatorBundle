<?php

namespace GeolocatorBundle\Model;

class GeoLocation
{
    private string $ip;
    private ?string $countryCode = null;
    private ?string $countryName = null;
    private ?string $regionName = null;
    private ?string $city = null;
    private ?float $latitude = null;
    private ?float $longitude = null;
    private ?bool $isVpn = null;
    private ?bool $isProxy = null;
    private ?bool $isTor = null;
    private ?string $isp = null;
    private ?string $org = null;
    private ?string $asn = null;
    private ?string $timezone = null;
    private array $rawData = [];

    public function __construct(string $ip, array $data = [])
    {
        $this->ip = $ip;
        $this->rawData = $data;

        // Remplir les propriétés à partir des données brutes si disponibles
        if (!empty($data)) {
            $this->populateFromRawData($data);
        }
    }

    private function populateFromRawData(array $data): void
    {
        // Détection automatique du format et normalisation
        if (isset($data['country']['iso_code'])) {
            // Format GeoIP2/MaxMind - conversion vers format simple
            $data = $this->normalizeGeoIP2Format($data) + $data;
        }

        // Mappings de noms de champs communs dans différentes API
        $countryCodeFields = ['country_code', 'countryCode', 'country'];
        $countryNameFields = ['country_name', 'countryName', 'country'];
        $regionFields = ['region', 'regionName', 'region_name', 'state'];
        $cityFields = ['city'];
        $latitudeFields = ['latitude', 'lat'];
        $longitudeFields = ['longitude', 'lon', 'long'];
        $vpnFields = ['vpn', 'is_vpn', 'isVpn'];
        $proxyFields = ['proxy', 'is_proxy', 'isProxy'];
        $torFields = ['tor', 'is_tor', 'isTor'];
        $ispFields = ['isp'];
        $orgFields = ['org', 'organization'];
        $asnFields = ['asn', 'as', 'autonomous_system_number'];
        $timezoneFields = ['timezone', 'time_zone'];

        // Fonction utilitaire pour trouver la première clé existante
        $findValue = function(array $fields) use ($data) {
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    return $data[$field];
                }
            }
            return null;
        };

        // Remplir les propriétés
        $this->countryCode = $findValue($countryCodeFields);
        $this->countryName = $findValue($countryNameFields);
        $this->regionName = $findValue($regionFields);
        $this->city = $findValue($cityFields);
        $this->latitude = $findValue($latitudeFields);
        $this->longitude = $findValue($longitudeFields);
        $this->isVpn = $findValue($vpnFields);
        $this->isProxy = $findValue($proxyFields);
        $this->isTor = $findValue($torFields);
        $this->isp = $findValue($ispFields);
        $this->org = $findValue($orgFields);
        $this->asn = $findValue($asnFields);
        $this->timezone = $findValue($timezoneFields);
    }

    /**
     * Convertit le format GeoIP2 complexe en format simple compatible
     */
    private function normalizeGeoIP2Format(array $data): array
    {
        $normalized = [];

        // Code pays
        if (isset($data['country']['iso_code'])) {
            $normalized['country_code'] = $data['country']['iso_code'];
        }

        // Nom du pays (priorité français puis anglais)
        if (isset($data['country']['names'])) {
            $normalized['country_name'] = $data['country']['names']['fr']
                ?? $data['country']['names']['en']
                ?? reset($data['country']['names']);
        }

        // Ville (priorité français puis anglais)
        if (isset($data['city']['names'])) {
            $normalized['city'] = $data['city']['names']['fr']
                ?? $data['city']['names']['en']
                ?? reset($data['city']['names']);
        }

        // Région/État (premier subdivision)
        if (isset($data['subdivisions'][0]['names'])) {
            $normalized['region_name'] = $data['subdivisions'][0]['names']['fr']
                ?? $data['subdivisions'][0]['names']['en']
                ?? reset($data['subdivisions'][0]['names']);
        }

        // Coordonnées géographiques
        if (isset($data['location']['latitude'])) {
            $normalized['latitude'] = $data['location']['latitude'];
        }
        if (isset($data['location']['longitude'])) {
            $normalized['longitude'] = $data['location']['longitude'];
        }

        // Fuseau horaire
        if (isset($data['location']['time_zone'])) {
            $normalized['timezone'] = $data['location']['time_zone'];
        }

        // Informations réseau
        if (isset($data['traits']['isp'])) {
            $normalized['isp'] = $data['traits']['isp'];
        }
        if (isset($data['traits']['organization'])) {
            $normalized['org'] = $data['traits']['organization'];
        }
        if (isset($data['traits']['autonomous_system_number'])) {
            $normalized['asn'] = (string) $data['traits']['autonomous_system_number'];
        }

        // Flags de sécurité
        if (isset($data['is_vpn'])) {
            $normalized['is_vpn'] = $data['is_vpn'];
        }
        if (isset($data['is_proxy'])) {
            $normalized['is_proxy'] = $data['is_proxy'];
        }
        if (isset($data['is_tor'])) {
            $normalized['is_tor'] = $data['is_tor'];
        }

        return $normalized;
    }

    // Tous les getters et setters restent identiques...
    public function getIp(): string
    {
        return $this->ip;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): self
    {
        $this->countryCode = $countryCode;
        return $this;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function setCountryName(?string $countryName): self
    {
        $this->countryName = $countryName;
        return $this;
    }

    public function getRegionName(): ?string
    {
        return $this->regionName;
    }

    public function setRegionName(?string $regionName): self
    {
        $this->regionName = $regionName;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function isVpn(): ?bool
    {
        return $this->isVpn;
    }

    public function setIsVpn(?bool $isVpn): self
    {
        $this->isVpn = $isVpn;
        return $this;
    }

    public function isProxy(): ?bool
    {
        return $this->isProxy;
    }

    public function setIsProxy(?bool $isProxy): self
    {
        $this->isProxy = $isProxy;
        return $this;
    }

    public function isTor(): ?bool
    {
        return $this->isTor;
    }

    public function setIsTor(?bool $isTor): self
    {
        $this->isTor = $isTor;
        return $this;
    }

    public function getIsp(): ?string
    {
        return $this->isp;
    }

    public function setIsp(?string $isp): self
    {
        $this->isp = $isp;
        return $this;
    }

    public function getOrg(): ?string
    {
        return $this->org;
    }

    public function setOrg(?string $org): self
    {
        $this->org = $org;
        return $this;
    }

    public function getAsn(): ?string
    {
        return $this->asn;
    }

    public function setAsn(?string $asn): self
    {
        $this->asn = $asn;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(array $rawData): self
    {
        $this->rawData = $rawData;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'country_code' => $this->countryCode,
            'country_name' => $this->countryName,
            'region_name' => $this->regionName,
            'city' => $this->city,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_vpn' => $this->isVpn,
            'is_proxy' => $this->isProxy,
            'is_tor' => $this->isTor,
            'isp' => $this->isp,
            'org' => $this->org,
            'asn' => $this->asn,
            'timezone' => $this->timezone,
        ];
    }
}

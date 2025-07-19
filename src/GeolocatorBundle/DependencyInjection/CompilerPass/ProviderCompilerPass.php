            $providerReferences[$alias] = new Reference($id);
            $vpnProviders[$alias] = $supportsVpn;
        }

        // S'assurer que le provider local est disponible
        if (!isset($providerReferences['local'])) {
            $container->register('geolocator.provider.local', 'GeolocatorBundle\\Provider\\LocalProvider')
                ->addArgument(new Reference('http_client'))
                ->addArgument([])
                ->addTag('geolocator.provider', ['alias' => 'local']);

            $providerReferences['local'] = new Reference('geolocator.provider.local');
            $vpnProviders['local'] = false;
        }

        // Définir les références dans les services qui ont besoin de providers

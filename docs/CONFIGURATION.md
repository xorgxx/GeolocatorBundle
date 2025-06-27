# Configuration

## 1. Variables d'environnement

Ajoutez dans `.env.local` :

```dotenv
# Providers DSN pour la géoloc IP
GEOLOCATOR_PROVIDERS_DSN="ipapi://@http://ip-api.com/json/{ip};ipwhois://@https://ipwhois.app/json/{ip}"
# TTL du cache (en secondes)
GEOLOCATOR_CACHE_TTL=300
# Proxies de confiance (pour IpResolver)
TRUSTED_PROXIES="127.0.0.1,10.0.0.0/8"
# Patterns de bots
BOT_PATTERNS='["Googlebot","Bingbot"]'
BOT_CHALLENGE=false
# Webhooks
WEBHOOK_URLS='[]'
# Messenger (RabbitMQ)
MESSENGER_TRANSPORT_DSN="amqp://localhost/%2f/messages"

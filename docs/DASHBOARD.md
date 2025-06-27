# Dashboard Admin

- **Route** : `/admin/geolocator`  
- **Vue** : Affiche liste paginée des IP bannies, raison et expiration
- **Actions** :  
  - Débannir une IP  
  - Modifier la durée du ban  
  - Export CSV/XLSX

Exemple de template Twig :

```twig
{% extends 'base.html.twig' %}

{% block title %}Dashboard Geolocator{% endblock %}
{% block body %}
  <h1>IPs bannies</h1>
  <table>
    <thead><tr><th>IP</th><th>Raison</th><th>Expire</th><th>Actions</th></tr></thead>
    <tbody>
      {% for ip, ban in bans %}
        <tr>
          <td>{{ ip }}</td>
          <td>{{ ban.reason }}</td>
          <td>{{ ban.expires_at }}</td>
          <td>
            <a href="{{ path('admin_geolocator_unban', {'ip': ip}) }}">Unban</a>
          </td>
        </tr>
      {% endfor %}
    </tbody>
  </table>
{% endblock %}
```

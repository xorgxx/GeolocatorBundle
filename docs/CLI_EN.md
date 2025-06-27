# CLI Commands

Le bundle fournit plusieurs commandes pour gérer les bans et les providers :

- **Lister les bans**  
  Affiche toutes les adresses IP actuellement bannies et leur raison.  
  ```bash
  php bin/console xorg:geolocator:ban:list
  ```

- **Ajouter un ban manuel**  
  Bannie une IP pour une durée donnée (format strtotime).  
  ```bash
  php bin/console xorg:geolocator:ban:add 203.0.113.45 "2 hours"
  ```

- **Supprimer un ban**  
  Retire une IP de la liste des bans.  
  ```bash
  php bin/console xorg:geolocator:ban:remove 203.0.113.45
  ```

- **Tester les providers**  
  Vérifie la connectivité et la validité des DSN configurés.  
  ```bash
  php bin/console xorg:geolocator:check-dsn
  ```

- **Exporter les bans vers un firewall**  
  Génére un fichier ou script pour iptables, nginx ou CSV.  
  ```bash
  php bin/console xorg:geolocator:export-firewall iptables
  ```

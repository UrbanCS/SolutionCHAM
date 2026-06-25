# Solution CHAM - Composant Joomla de facturation instructeurs

Ce dépôt contient une première version MVP installable du composant Joomla `com_instructor_billing`.

Le composant permet aux instructeurs de se connecter avec leur compte Joomla, démarrer et terminer un cours pratique, ajouter un cours manuellement, consulter leur historique, et voir leurs factures. Les administrateurs peuvent gérer les profils instructeurs, approuver/refuser les cours, générer des factures hebdomadaires, créer des factures manuelles, exporter en CSV et imprimer une facture proprement.

## Stack

- Joomla 4 ou Joomla 5
- PHP 8+
- MySQL ou MariaDB
- cPanel compatible
- Aucun serveur permanent, Docker, Node.js ou build requis en production

## Structure

```text
com_instructor_billing/
  instructor_billing.xml          # manifeste d'installation Joomla
  script.php                      # création optionnelle du groupe Instructeur
  admin/                          # interface administrateur
    sql/install.mysql.utf8.sql
    sql/uninstall.mysql.utf8.sql
    src/Controller
    src/Model
    src/Service
    src/View
    tmpl
  site/                           # interface frontend instructeur
    src/Controller
    src/Model
    src/View
    tmpl
  media/
    css
    js
docs/
  installation-cpanel-joomla.md
```

## Installation rapide

1. Créer une archive ZIP avec le manifeste Joomla à la racine de l'archive.

   Depuis ce dossier de projet:

   ```bash
   cd com_instructor_billing
   zip -r ../com_instructor_billing.zip .
   ```

2. Dans Joomla: `Système` -> `Installer` -> `Extensions`, téléverser `com_instructor_billing.zip`.

3. Dans `Système` -> `Configuration globale` -> `Facturation instructeurs`, configurer:
   - préfixe de facture, par exemple `CHAM`
   - taux horaire par défaut
   - taxes si applicables
   - paramètres Sage si vous préparez une intégration future

4. Dans `Utilisateurs` -> `Groupes`, vérifier ou créer le groupe `Instructeur`.

5. Dans les permissions du composant:
   - groupe `Instructeur`: autoriser `Accès instructeur`
   - groupe admin: autoriser `Accès administration`, `Approuver les cours`, `Gérer les factures`

6. Dans le menu Joomla frontend, créer un élément de menu:
   - type: `Facturation instructeurs` -> `Tableau de bord instructeur`
   - accès: utilisateurs connectés ou groupe Instructeur

7. Dans l'administration du composant:
   - ouvrir `Composants` -> `Facturation instructeurs`
   - créer les profils instructeurs avec leur taux horaire
   - tester un cours depuis le frontend

## Flux MVP

1. L'instructeur se connecte au site Joomla.
2. Il ouvre le tableau de bord instructeur.
3. Il clique `Débuter un cours/trajet`.
4. Le navigateur peut demander l'autorisation GPS. Si elle est refusée, le cours est quand même enregistré.
5. Il clique `Terminer le cours/trajet`.
6. Le système calcule automatiquement la durée et met le cours au statut `soumis`.
7. L'administrateur approuve ou refuse le cours.
8. L'administrateur génère une facture hebdomadaire pour les cours approuvés.
9. La facture peut être exportée en CSV ou imprimée en PDF via le navigateur.

## Sécurité

- Les instructeurs ne peuvent consulter que leurs propres cours et factures.
- Les actions sensibles utilisent les tokens CSRF Joomla.
- Les requêtes passent par l'API base de données Joomla.
- Les permissions Joomla ACL contrôlent l'accès admin, l'approbation et la facturation.
- Aucune clé Sage ou API n'est codée dans les fichiers.

## Exports

- CSV: disponible pour chaque facture.
- PDF: le MVP fournit une vue HTML imprimable propre (`PDF / imprimer`). Cela fonctionne sans librairie PHP externe, donc c'est adapté à l'hébergement partagé. Si Dompdf ou mPDF est disponible plus tard, il pourra être ajouté derrière un service dédié.

## Sage

Le composant inclut `admin/src/Service/SageService.php` avec les méthodes préparées:

- `createInvoice($invoiceId)`
- `syncCustomer($instructorId)`
- `testConnection()`

L'intégration réelle Sage Business Cloud doit être ajoutée plus tard avec OAuth2, sans hardcoder de secret dans le code.

## Tests manuels recommandés

Voir [docs/installation-cpanel-joomla.md](docs/installation-cpanel-joomla.md) pour le détail cPanel/Joomla.

Checklist courte:

- installer le ZIP dans Joomla
- créer un profil instructeur
- créer un menu frontend vers le dashboard
- se connecter comme instructeur
- démarrer et terminer un cours
- vérifier que la durée est calculée
- vérifier que l'instructeur ne voit pas les données des autres
- approuver le cours côté admin
- générer une facture hebdomadaire
- exporter CSV
- ouvrir `PDF / imprimer`

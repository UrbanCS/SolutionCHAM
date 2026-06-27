# Installation cPanel / Joomla

## Préparation du ZIP

Le composant est livré comme dossier installable Joomla:

```text
com_instructor_billing/
```

Créer l'archive avec `instructor_billing.xml` à la racine du ZIP:

```bash
cd com_instructor_billing
zip -r ../com_instructor_billing.zip .
```

Sur Windows, ouvrez le dossier `com_instructor_billing`, sélectionnez son contenu, puis compressez la sélection. Le fichier ZIP doit contenir directement `instructor_billing.xml`, `admin/`, `site/` et `media/`.

## Installation par Joomla

1. Se connecter au panneau administrateur Joomla.
2. Aller à `Système` -> `Installer` -> `Extensions`.
3. Téléverser `com_instructor_billing.zip`.
4. Confirmer que le menu `Composants` -> `Facturation instructeurs` apparaît.

## Installation par cPanel si le téléversement Joomla échoue

1. Dans cPanel `File Manager`, téléverser le ZIP dans un dossier temporaire hors sauvegardes sensibles.
2. Extraire le dossier.
3. Dans Joomla, utiliser l'installation depuis un dossier si disponible.
4. Si nécessaire, copier les fichiers manuellement:

```text
com_instructor_billing/admin/*  -> /administrator/components/com_instructor_billing/
com_instructor_billing/site/*   -> /components/com_instructor_billing/
com_instructor_billing/media/*  -> /media/com_instructor_billing/
```

Puis importer le fichier SQL:

```text
com_instructor_billing/admin/sql/install.mysql.utf8.sql
```

en remplaçant `#__` par le préfixe réel des tables Joomla, par exemple `abcde_`.

## Permissions Joomla

Créer ou vérifier le groupe:

- `Instructeur`

Permissions du composant:

- `Instructeur`
  - `Accès instructeur`: Autorisé
  - ne pas autoriser `Accès administration`
  - ne pas autoriser `Gérer les factures`

- Administrateurs
  - `Accès administration`: Autorisé
  - `Approuver les cours`: Autorisé
  - `Gérer les factures`: Autorisé

Créer un profil dans `Composants` -> `Facturation instructeurs` -> `Instructeurs` pour chaque utilisateur instructeur.

## Menu frontend

Créer un élément de menu:

- Type: `Facturation instructeurs` -> `Tableau de bord instructeur`
- Accès: `Registered` ou un niveau d'accès réservé aux instructeurs
- Alias suggéré: `mes-cours`

Créer aussi un élément de menu administrateur frontend si le client veut éviter le backend Joomla pour les opérations quotidiennes:

- Type: `Facturation instructeurs` -> `Gestion frontend instructeurs`
- Accès: niveau réservé aux administrateurs
- Alias suggéré: `gestion-instructeurs`

Le site existant a déjà un lien `Login`, donc les instructeurs peuvent utiliser l'authentification Joomla standard.

## Configuration de facturation

Dans les options du composant:

- `Préfixe de facture`: `CHAM`
- `Taux horaire par défaut`: utilisé seulement si aucun taux profil n'est défini
- `Taxes`: activer seulement si nécessaire
- `Taux de taxe`: entrer `0.13` ou `13` pour 13 %

## Vérification fonctionnelle

1. Se connecter comme instructeur.
2. Ouvrir le tableau de bord frontend.
3. Démarrer un cours.
4. Refuser ou accepter la localisation GPS pour tester les deux chemins.
5. Terminer le cours.
6. Vérifier que le cours apparaît comme `Soumis`.
7. Cliquer `Générer / approuver ma facture`.
8. Vérifier que la facture apparaît dans l'espace instructeur.
9. Ouvrir la facture.
10. Tester `CSV`.
11. Tester `PDF / imprimer`.
12. Se connecter comme administrateur et vérifier que la facture est visible dans la gestion.

## Vérification d'isolation

Créer deux comptes instructeurs et deux profils:

- instructeur A
- instructeur B

Créer un cours avec chaque compte. Confirmer que:

- A ne voit pas les cours de B
- B ne voit pas les cours de A
- un administrateur voit les deux
- les URL de facture et d'historique refusent l'accès aux données d'un autre instructeur

## Notes MVP

- Le GPS enregistre la position de départ et de fin si le navigateur l'autorise.
- La table `#__gps_points` existe déjà pour ajouter plus tard des points intermédiaires pendant le trajet.
- L'export PDF est une vue imprimable compatible cPanel sans librairie externe.
- Sage est préparé par service PHP et configuration, mais l'API réelle n'est pas activée dans ce MVP.

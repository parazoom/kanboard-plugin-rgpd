Rgpd
====

*[English version](README.md)*

Boîte à outils RGPD pour [Kanboard](https://kanboard.org) : un pied de page et
une purge des comptes utilisateurs inactifs, tous deux configurés depuis les
pages de préférences.

Fonctionnalités
---------------

### Paramètres du pied de page

- Affiché sur toutes les pages (application, formulaire de connexion, tableaux
  et tâches publics), et uniquement si au moins un élément est défini.
- Se place en bas de l'écran lorsque la page est plus courte que la fenêtre, et
  juste après le contenu lorsque la page est plus longue.
- Deux types d'éléments, triés ensemble par poids, de 1 (en premier) à 10 (en
  dernier) :
  - **liens** : nom, URL et poids,
  - **documents PDF** : nom, fichier téléversé et poids.
- Chaque entrée s'ouvre dans un nouvel onglet et porte sa propre classe CSS
  dérivée de son nom, par exemple `link-mentions-legales` pour un élément
  nommé « Mentions légales », afin qu'un thème puisse styler chaque entrée
  individuellement.

### Purge des utilisateurs inactifs

- Les comptes sont sélectionnés sur leur date de dernière connexion. Le seuil
  est au choix une durée d'inactivité glissante (recalculée à chaque passage)
  ou une date fixe, qui ne peut pas être dans le futur.
- Les comptes ayant le rôle `app-admin` et le compte qui lance la purge ne sont
  jamais supprimés. Les comptes qui ne se sont jamais connectés sont conservés,
  sauf si l'option correspondante est activée.
- Kanboard n'enregistre pas la date de création des comptes : le plugin note
  donc la première fois qu'il voit chaque compte et utilise cette date pour les
  comptes qui ne se sont jamais connectés. Un tel compte n'est supprimé qu'une
  fois connu depuis plus longtemps que le seuil. Les comptes existant à
  l'installation du plugin sont datés de l'installation. Cette date n'est
  jamais antérieure à la création réelle : un compte peut être conservé plus
  longtemps que prévu, jamais supprimé trop tôt.
- **Purger maintenant** supprime immédiatement les comptes correspondants.
- La purge automatique est programmée par une seule date et heure, avancée
  d'une période (semaine, mois ou année) après chaque passage.
- La prévision de purge liste les comptes que le bouton supprimerait
  maintenant, et ceux que le prochain passage programmé supprimerait.
- Chaque passage est enregistré : date, manuel (avec son auteur) ou
  automatique, seuil utilisé et nombre de comptes supprimés. L'identité des
  comptes supprimés n'est pas conservée.

Installation
------------

1. Copier le dossier `Rgpd` dans le dossier `plugins` de Kanboard.
2. Les tables sont créées automatiquement à la requête suivante.
3. Les réglages se trouvent dans *Préférences > Paramètres du pied de page* et
   *Préférences > Purge des utilisateurs*.

Programmer la purge automatique
-------------------------------

La page de réglages contient une date et une heure : celles de la prochaine
purge automatique. Le planificateur système ne fait que donner le tempo, en
appelant une commande qui vérifie si cette date est dépassée :

```
*/15 * * * * cd /chemin/vers/kanboard && ./cli rgpd:purge >/dev/null 2>&1
```

Chaque appel fait l'une de ces trois choses :

- la date n'est pas atteinte : rien ne se passe,
- la date est dépassée et aucune purge automatique n'a eu lieu pour celle-ci :
  les comptes sont purgés, le passage est journalisé, et la date avance d'une
  période,
- la date est dépassée mais le journal contient déjà un passage pour celle-ci :
  rien n'est purgé, seule la date avance.

La commande peut donc être appelée aussi souvent que souhaité, et la purge
n'est jamais effectuée deux fois pour la même échéance. Un appel toutes les
15 minutes permet de respecter l'heure choisie ; un tempo plus espacé ne fait
que retarder la purge.

La date et l'heure sont lues dans le fuseau horaire de l'application, et non
dans celui de l'utilisateur connecté, afin que l'interface web et la ligne de
commande soient toujours d'accord.

Sans cette commande, la page de réglages fonctionne toujours mais la purge
automatique n'a jamais lieu : seul le bouton *Purger maintenant* supprime des
comptes.

Lancer la purge depuis un terminal
----------------------------------

Se connecter au serveur en SSH et se placer dans le dossier racine de Kanboard,
celui qui contient le fichier `cli` à côté de `index.php`, `app/` et `plugins/`.
Selon l'installation, c'est en général `/var/www/kanboard` ou `/var/www/html` :

```
cd /var/www/kanboard
```

Lancer les commandes sous l'utilisateur du serveur web, `www-data` sur Debian
et Ubuntu, pour que les fichiers écrits par Kanboard gardent le bon
propriétaire :

```
sudo -u www-data ./cli rgpd:purge --dry-run
```

| Commande | Effet |
| --- | --- |
| `./cli rgpd:purge --dry-run` | Liste les comptes qui seraient supprimés, ne supprime rien |
| `./cli rgpd:purge --force` | Purge tout de suite, quelle que soit la programmation, qui reste inchangée |
| `./cli rgpd:purge` | Ce qu'appelle le planificateur : n'agit que si la date programmée est dépassée |

Commencer par `--dry-run`, la seule des trois qui ne modifie rien :

```
$ sudo -u www-data ./cli rgpd:purge --dry-run
Accounts whose last login is older than 2025-09-17 13:29
  #36 user35 (2024-04-20 10:13)
  #45 user44 (2024-04-22 11:05)
  #40 user39 (2024-05-19 00:41)
3 user account(s) would be removed
```

Le `cd` est un confort : Kanboard résout ses propres chemins, un chemin absolu
fonctionne donc depuis n'importe quel dossier, ce qui est pratique dans un
crontab.

```
sudo -u www-data php /var/www/kanboard/cli rgpd:purge --dry-run
```

Une purge lancée depuis un terminal n'a pas d'utilisateur connecté : elle est
donc enregistrée dans le journal comme un passage automatique.

Remarques
---------

- Les documents PDF sont servis par le plugin à une URL publique, puisqu'ils
  doivent rester accessibles depuis la page de connexion et depuis les tableaux
  publics.
- Le placement en bas d'écran repose sur le sélecteur CSS `:has()`, afin que la
  mise en page des pages sans pied de page reste intacte. Sur un navigateur qui
  ne le prend pas en charge, le pied de page suit simplement le contenu.
- La page de connexion, les tableaux publics et les tâches publiques sont rendus
  sans la mise en page de l'application : le plugin remplace donc leurs modèles
  pour ajouter le pied de page sous le contenu, en enfant direct du corps de la
  page. Le remplacement est déclaré une fois tous les plugins initialisés et
  rend le modèle déjà en place, si bien qu'un autre plugin remplaçant les mêmes
  vues continue de fonctionner.

Limites au regard du RGPD
-------------------------

La purge s'appuie sur la suppression d'utilisateur de Kanboard, qui n'efface
pas toutes les traces de la personne :

- supprimés : le compte lui-même, son historique de connexions, ses sessions,
  son avatar, son propre flux d'activité et ses projets privés,
- conservés : les commentaires rédigés par la personne, détachés du compte
  mais dont le texte reste, et les tâches qu'elle a créées, dont le contenu
  reste,
- conservés : le nom ou l'identifiant de la personne partout où il apparaît en
  texte libre, comme les `@mentions` dans les commentaires et les descriptions,
  ou dans le flux d'activité des autres utilisateurs.

Une purge supprime donc le compte, elle n'anonymise pas les contenus produits
par la personne. Si votre politique de conservation l'exige, nettoyez ces
contenus séparément.

Le plugin ne prévient pas les personnes concernées avant la suppression de
leur compte. Les en informer à l'avance, par exemple par courriel quelques
semaines avant la purge, est recommandé ; la page *Prévision de purge* liste
les comptes que le prochain passage programmé supprimera.

Pré-requis
----------

- Kanboard >= 1.2.20

Auteur
------

Développé par Ludovic FRANCOIS, salarié de [LINAGORA](https://linagora.com),
pour le compte des Ministères économiques et financiers.

Licence
-------

Ce plugin est distribué sous [licence MIT](LICENSE).

# Typographe 2.0.7 — JED Checker

## Français

Contrôle du 23 octobre 2026 sur l'environnement de démonstration Joomla 6.2.0 / PHP 8.3.30. JED Checker a été construit depuis le dépôt officiel, référence `c6a6bce8205975923bf687155c4b45a1d7c025d6`, et installé sur la démo. Il s'annonce 3.0.0 dans sa configuration de construction, mais cette référence est une version de développement, pas une version stable publiée.

Le formulaire du contrôleur refuse les archives PHP avec le filtre de téléversement de Joomla 6. Les 14 règles du composant installé ont donc été exécutées en ligne de commande sur le ZIP extrait, avec les bibliothèques Joomla de la démo. Aucune règle n'a été désactivée ou modifiée. L'adaptateur de lancement fournit les traductions installées directement, car `is_readable()` renvoie faux sous le jeton Windows utilisé, malgré des lectures de fichiers réussies. Le site et ses protections n'ont pas été modifiés pour contourner le téléversement.

Le paquet a été corrigé : en-têtes GPL du code propre, contrôle `_JEXEC` du service et de l'autoloader distribué, mentions MIT explicites dans les fichiers PHP tiers, retrait du dossier de génération de dictionnaires Hyphenator non utilisé à l'exécution. Les copyrights et licences originaux sont conservés. Composer reste reproductible ; ces ajustements sont effectués par `tools/build.php` sur le ZIP seulement.

Résultat brut : **1 erreur, 88 avertissements, 3 notices**, aucune exception de règle. [Détail JSON](jed-checker-2.0.7.json).

- PH3 : faux positif du contrôleur, qui cherche `$xml->licence`. Le manifeste contient la balise Joomla correcte `<license>` avec GPL version 3 ou ultérieure. Ne pas remplacer le manifeste valide par une balise incorrecte pour satisfaire cette règle.
- 82 avertissements PH1 : bibliothèques sous licence MIT compatible GPL, dont les fichiers de licence et attributions restent inclus.
- 6 avertissements JAMSS : chemins d'autoload Composer (`strtr`), constantes UTF-8 JoliTypo/Hyphenator et opérations Unicode du polyfill Symfony. Les lignes signalées ont été examinées ; elles correspondent à ces fonctions légitimes, sans code exécuté via `eval`.
- 2 notices de traduction : clé `PLG_SYSTEM_TYPOGRAPHE_ERROR` présente dans les fichiers français et anglais. Le filtre des chemins de la règle Language ne reconnaît pas les séparateurs Windows retournés par le système de fichiers.
- 1 notice de manifeste : adresse électronique de l'auteur optionnelle non fournie. Le lien du dépôt est présent ; aucune adresse n'a été inventée.

Les contrôles d'accès direct, de structure/fichiers XML, du serveur de mise à jour, des API Joomla, de PHP, de sécurité, d'encodage et d'error_reporting ne signalent pas de problème. Tests du paquet : 39 moteur, 16 intégration Joomla, suite JavaScript de comparaison et 4 tests du flux de mise à jour.

Ce rapport prépare la revue du JED ; il ne constitue ni une approbation ni une publication sur extensions.joomla.org. Les avertissements tiers et les limites du contrôleur doivent accompagner la soumission.

## English

Audit dated 23 October 2026, demo environment Joomla 6.2.0 / PHP 8.3.30. JED Checker was built from the official repository at `c6a6bce8205975923bf687155c4b45a1d7c025d6` and installed on the demo. Its build configuration says 3.0.0, but this is a development revision, not a published stable release.

The checker's upload form rejects PHP archives through Joomla 6's upload filter. All 14 installed checker rules were therefore executed through a command-line adapter against the extracted release ZIP, using the demo's Joomla libraries. No rule was disabled or modified. The adapter supplies installed translations directly because `is_readable()` returns false under the Windows execution token despite successful file reads. No site security setting was weakened.

Package fixes: GPL headers on first-party PHP files, `_JEXEC` guards for the service and packaged autoloader, explicit MIT notices on bundled PHP dependencies, and removal of unused Hyphenator dictionary-generation tools. Original copyrights and license texts remain included. Composer sources remain reproducible; dependency packaging adjustments are performed by `tools/build.php` on ZIP contents only.

Raw result: **1 error, 88 warnings, 3 notices**, no rule exceptions. [Detailed JSON](jed-checker-2.0.7.json).

- PH3 is a checker false positive: its implementation expects `$xml->licence`, whereas Joomla uses `<license>`. The valid manifest declares GNU GPL version 3 or later.
- 82 PH1 warnings identify GPL-compatible MIT dependencies; license texts and original attributions are included.
- 6 JAMSS warnings concern Composer autoload paths (`strtr`), UTF-8 constants in JoliTypo/Hyphenator, and Symfony Unicode operations. The reported lines were reviewed and match those legitimate purposes, without `eval` execution.
- 2 language notices concern `PLG_SYSTEM_TYPOGRAPHE_ERROR`, present in both translations. The rule's path pattern does not recognize Windows filesystem separators.
- 1 manifest notice concerns the optional author email. The repository URL is supplied; no email address was invented.

Direct access, XML structure/files, update server, Joomla framework, PHP, security, encoding and error_reporting checks report no issue. Package tests: 39 engine tests, 16 Joomla integration tests, JavaScript comparison tests and 4 update-feed tests.

This report supports JED review; it is not JED approval or submission. Include third-party warnings and checker limitations when submitting the extension.

# Typographe 2.0.8 — Guide français

[Accueil](../README.md) · [English](README.en.md)

## Installation et mise à niveau

1. Sauvegarder le site avant toute mise à niveau.
2. Télécharger `plg_system_typographe-2.0.8.zip` depuis les [versions GitHub](https://github.com/YGomiero/typographe/releases/tag/2.0.8).
3. Installer le ZIP dans Joomla : **Système → Installation → Extensions**.
4. Activer **Système - Typographe** dans les plugins et vérifier ses paramètres.

Prérequis : Joomla 6, PHP 8.3 ou supérieur, extensions PHP DOM et mbstring. Les dépendances sont embarquées. Aucun accès réseau ni installation Composer n’est nécessaire au fonctionnement.

La mise à niveau conserve les noms des anciens paramètres et retire les fichiers du moteur 1.x devenus inutiles. L’option historique d’espacement français conserve son choix Oui/Non, mais pilote désormais l’espacement selon la langue. La normalisation Unicode est activée par défaut. Les contenus existants ne sont pas corrigés en masse. Cette version ne cible pas Joomla 3, 4 ou 5.

## Où intervient Typographe ?

- Articles : introduction, texte complet et, en option, titre.
- Catégories : description et, en option, titre.
- Modules HTML personnalisés : contenu et, en option, titre.

Le panneau de vérification est disponible dans l’administration. La correction automatique prend également en charge l’enregistrement des articles depuis l’éditeur frontal. L’affichage public n’ajoute aucun panneau, JavaScript ou CSS Typographe et ne lance aucune correction. Sur une requête GET publique, Joomla initialise seulement une enveloppe de plugin vide ; le moteur JoliTypo n’est pas chargé. Les imports API et opérations sans champ d’édition ne sont pas traités.

## Vérifier et choisir les corrections

1. Ouvrir un formulaire d’édition et cliquer sur **Vérifier la typographie**.
2. Comparer **Avant** et **Après** dans le **Rendu HTML**, ou basculer vers **Code source**.
3. Cliquer directement sur une modification dans **Après** pour la refuser ou l’accepter à nouveau. **Jaune : acceptée. Gris souligné : refusée.** Toutes sont acceptées initialement. Au clavier, utiliser Tab puis Entrée ou Espace.
4. Cliquer sur **Appliquer les corrections retenues** pour transférer la sélection dans l’éditeur.
5. Enregistrer le formulaire Joomla pour conserver le résultat.

L’aperçu et les clics seuls ne modifient pas l’éditeur ni la base. **Fermer l’aperçu** ne touche pas au texte. **Annuler l’application** restaure la version précédente tant que le texte n’a pas été modifié depuis l’application. Une proposition devenue obsolète ne peut pas écraser une nouvelle saisie.

Le rendu conserve la structure du texte, sans les styles du template, médias externes ni éléments exécutables. Les marques de comparaison ne sont jamais enregistrées. Des caractères modifiés contigus forment une correction ; de très grandes différences complexes peuvent être regroupées pour limiter le calcul.

## Mode automatique et décisions manuelles

**Corriger automatiquement à l’enregistrement** est activé par défaut. Pour un fonctionnement uniquement manuel, désactiver cette option.

Une sélection manuelle appliquée prime sur le mode automatique **pour la révision exacte soumise lors de cet enregistrement** : une correction refusée n’est pas réappliquée. La version 2.0.8 reconnaît aussi la sérialisation HTML de TinyMCE et les retours à la ligne du formulaire : ils ne déclenchent plus la réapplication des corrections refusées. Les refus ne sont pas des exclusions permanentes. Si le texte est modifié ensuite ou rouvert pour une autre édition, vérifier à nouveau ou désactiver le mode automatique. Annuler une application ne désactive pas l’automatisme.

## Langue et règles

La langue du contenu détermine les conventions typographiques, indépendamment de la langue de l’interface. Pour un contenu « Toutes », le plugin utilise sa langue par défaut (français de France initialement).

| Option | Effet |
| --- | --- |
| Normalisation des caractères Unicode | Compose les accents issus de copier-coller en forme NFC, sans changer leur apparence ; exécutée en premier. |
| Espacement selon la langue | Utilise les conventions JoliTypo : espaces insécables en français de France, suppression des espaces concernés en anglais et en français canadien. |
| Guillemets et apostrophes | Remplace les formes droites selon la langue. |
| Points de suspension | Remplace `...` par `…`. |
| Dimensions | Utilise le signe `×`. |
| Unités | Lie le nombre à son unité avec une espace insécable. |
| Tirets | Applique les tirets et espacements typographiques. |
| Espaces avant les virgules | Supprime les espaces inutiles. |
| Césure conditionnelle | Ajoute des possibilités de coupure ; désactivée par défaut. |
| Marques et copyright | Convertit les notations prises en charge en symboles. |

Chaque règle peut être désactivée. Les titres, catégories et modules ont aussi leurs propres options.

## Protection et limites

Les balises HTML et leurs attributs, commentaires, blocs `pre`, `code`, `script`, `style`, `textarea`, SVG, MathML, iframe, URL, adresses e-mail et balises Joomla `{...}` sont préservés. Utiliser `class="notypo"` ou `data-typographe-ignore` pour exclure un passage. Les entités des segments de texte corrigés peuvent être normalisées en UTF-8.

Les guillemets répartis sur plusieurs balises nécessitent parfois une correction manuelle. Les champs personnalisés, méta-descriptions et composants tiers ne sont pas intégrés dans cette version. Le point d’accès de vérification exige une session d’administration, POST, un jeton CSRF et les droits de création/modification ; il ne sauvegarde rien. Limites : 1 Mo de texte et 10 Ko de titre.

Depuis la version 2.0.6, Joomla détecte les versions GitHub stables via son gestionnaire de mises à jour. Installer la 2.0.8 une fois pour enregistrer le canal, puis utiliser Système → Mise à jour → Extensions. La détection est automatique ; l’installation est déclenchée par l’administrateur, sans télécharger le ZIP manuellement. Les historiques Joomla peuvent aider à revenir en arrière lorsqu’ils sont activés, mais ne remplacent pas une sauvegarde.

## Validation et dépendances

39 tests du moteur, 16 tests d’intégration Joomla et une suite JavaScript de comparaison/sélection. Vérifications navigateur sur Joomla 6.2.0 / PHP 8.3.30 : aperçu, choix par clic et clavier, application/annulation, sauvegarde avec refus conservé et normalisation Unicode avec zones protégées.

JoliTypo 1.8.0, Hyphenator 3.2.0 et le polyfill Symfony Intl Normalizer 1.43.0 sont verrouillés par Composer. Les instructions de construction et de test figurent dans le [README principal](../README.md).

## JED Checker

[Rapport français / English audit report](JED-CHECKER.md) — 2.0.8, Joomla 6.2.0.

Contact / Support: [extensions@gomiero.me](mailto:extensions@gomiero.me)

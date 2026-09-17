# FreshApp Links

Listes de liens configurables, affichables sur un ou plusieurs emplacements du thème.

- **Nom technique** : `freshapplinks`
- **Version** : 1.1.0
- **Auteur** : FreshApp.io
- **Compatibilité** : PrestaShop 1.7 à 9
- **Onglet BO** : `AdminFreshapplinks`, sous *Apparence*

## Fonctionnement

Une liste regroupe des liens vers un produit, une catégorie, une page CMS ou une URL libre.
Chaque liste choisit ses emplacements d'affichage (haut de page, barres de navigation,
bannière, pied de page…), sa disposition (colonne ou ligne) et l'affichage de son titre.
Chaque lien peut porter une icône et son propre style (couleur, police, graisse, casse).

## Icônes

Les icônes sont embarquées dans le module (`views/img/icons/icons.svg`, jeu Lucide sous
licence ISC) : elles s'affichent quel que soit le thème. Une image téléversée peut aussi
servir d'icône.

## Tables

| Table | Rôle |
|---|---|
| `PREFIX_freshapplinks_list` / `_lang` | Listes, emplacements, disposition |
| `PREFIX_freshapplinks_link` / `_lang` | Liens, cible, icône, style |

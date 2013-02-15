[Accueil](..)

# Fontionnalités

## Fait :

* licence GPL v3
* php 5.3, programmation objet, espaces de noms, closures, etc.
* Représentation du formulaire sous forme d'une collection d'objets
* API documentée
* doc utilisateur : on a un début et l'infrastructure (markdown, rendu automatique)
* API fluide (à la jquery)
* getter/setter simplifiés (exemple : label(x?) au lieu de getLabel() et setLabel())
* design patterns : factory, séparation vue/modèle, etc.
* Gestion des champs de base (input, select textarea, checklist)
* Gestion des containers (fieldset, table)
* Gestion des champs structurés : il suffit de donner un nom à un container
* Gestion des champs répétables, à la fois pour les champs simples et pour les containers
* Attribution automatique de noms de champs "corrects" pour php
* Gestion fine des assets
* Génération de code html valide
* Gestion des thèmes, héritage de thème, héritage de template, indépendance entre le formulaire  et son rendu
* Débogage : code optimisé ou lisible, nom des templates utilisés, etc.
* Sécurité : escaping automatique partout (sauf labels et descriptions)
* Attribution automatique d'ID uniques
* Choix de l'emplacement pour la description (avant ou après)
* Gestion automatique des attributs html booléens (selected, checked, multiple, disabled, etc.)
* Fonctions facilitatrices pour la gestion des classes (addClass, hasClass, etc.)
* sérialisation : toArray(). A faire : fromArray()
* programme de test plutpot bien chiadé (néanmoins, ça ne rempalce pas des tests unitaires)
* c'est facile de créer de nouveaux contrôles
* c'est facile de créer de nouveaux thèmes

## A faire ou à finir :
Conditions
* showIf(champ, value)
* hideIf(champ, value)
* disableIf(champ, value)
* readOnlyIf(champ, value)

Divers
* documentation
* détection de bouclage (utile ?)

Validation :
* contrôles de saisie (required, length, occurences, etc.)
* coté client
* coté serveur
* limiter le nombre de fois qu'on peut répéter un champ

Contrôles de base :
* radiolist
* les nouveaux widgets html5 (email, etc.), facile.
* upload de fichiers

Contrôles évolués :
* choosen ou select2
* rich editor
* date picker, time picker

Lookups :
* lokkup sur une taxonomie
* lookup sur une table texte

Thèmes :
* finaliser/nettoyer le thème default
* adapter le thème form-table
* faire un thème à partir de la css de fab

Optimisation :
* dans render, utiliser un cache pour ne pas faire des file_exists() systématiques pour chaque template

Serialisation :
* écrire fromArray()
* sérialisation json ?
* sérialisation xml ?

Tests unitaires
* voir comment

Publication
* migrer sur github
* encourager le fork, les issues, etc.
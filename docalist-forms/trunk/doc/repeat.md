[Accueil](..)

# Champs répétables

## Introduction

Pour qu'un champ soit répétable (autrement dit multivalué), il suffit de mettre
son attribut `repeatable` à `true`. 

Exemple :

```php
$form->input('tags')->label('Mots-clés')->repeatable(true)
```

## Boutons de répétition

Lorsqu'un champ répétable est généré, un bouton permettant de *cloner* le champ est ajouté par le thème.

Le bouton généré :

* peut être un bouton ou un lien
* doit avoir la classe `cloner`
* peut avoir un attribut `data-clone` qui désigne l'élément à cloner (prev par défaut)
* peut avoir un attribut `data-level` qui désigne le niveau de répétition (cf plus bas "Noms des champs"), 1 par défaut.

L'attribut `data-clone` permet d'indiquer au bouton où se trouve l'élément qu'il doit cloner. 

L'attribut `data-clone` contient un sélecteur jquery standard et un préfixe optionnel (non jquery) qui indique à partir
d'où commencer la recherche de l'élément cloné.

Le préfixe peut contenir les caractères suivants :

* `<` : aller à l'élément précédent
* `^` : aller au parent
* dès qu'on rencontre autre chose que ces deux caractères, on considère que c'est le début d'un sélecteur jquery standard qui sera ppliqué tel quel.

Exemple :
```html
<table>
    <tr><td>cell1</td>
    <tr><td><button type="button" class="cloner" data-clone="^^^tr:last-child">ajouter un tr</button></td></tr>
</table>
```

Lorsque le bouton est cliqué, on anlyse le contenu de l'attribut `data-clone` de la façon suivante :

* on commence avec le bouton
* on rencontre le caractère `^`. On sélectionne le parent du bouton : le `<td>`
* on rencontre le caractère `^`. On sélectionne le parent du `<td>` : le `<tr>`
* on rencontre le caractère `^`. On sélectionne le parent du `<tr>` : l'élément `<table>`
* on applique ensuite le sélecteur jquery sur le noeud obtenu : `var node = $('tr:first', '<table>');`
* on clone le noeud et on l'insère ce clone juste après le noeud : `node.after(node.clone());`

Remarque :
La valeur par défaut de l'attribut `data-clone` est `<` : par défaut c'est l'élément qui précède immédiatement le bouton qui sera cloné.

## Noms des champs

Lorsqu'un élément de formulaire  est cloné, il faut veiller à mettre à jour l'attribut `name` de tous les champs, 
garantir que les `id` utilisés, mettre à jour les attributs `for` dans les labels, etc.

Analyse :

Le nom d'un champ répétable est de la forme `nom[i]` où `i` représente le numéro d'occurence du champ.
Par exemple si on un champ `tags` qui contient trois occurences, on aura un code html du style :

```html
<div>
    <input type="text" name="tags[0]" value="tag1" />
    <input type="text" name="tags[1]" value="tag2" />
    <input type="text" name="tags[2]" value="tag3" />
    <button type="button" class="cloner">Ajouter un tag</button>
</div>
```

Si un champ répétable est un sous champ, le nom du champ comporte alors le nom du champ parent.
Par exemple si on a un champ "contact" qui contient les sous-champs nom et prénom et que prénom est répétable, on aura :

```html
<!-- contact -->
<div>
    <!-- nom -->
    <input type="text" name="contact[nom]" value="Victor" />

    <!-- prénom -->
    <input type="text" name="contact[prenom][0]" value="Paul" />
    <input type="text" name="contact[prenom][1]" value="Emile" />
    <button type="button" class="cloner">Ajouter un prénom</button>
</div>
```

Le parent du champ peut lui-même être un champ répétable (par exemple, on peut vouloir saisir plusieurs contacts). 
Dans ce cas, le code généré devient quelque chose du genre :

```html
<!-- contact -->
<div>
    <!-- nom -->
    <input type="text" name="contact[nom]" value="Victor" />

    <!-- prénom -->
    <input type="text" name="contact[0][prenom][0]" value="Paul" />
    <input type="text" name="contact[0][prenom][1]" value="Emile" />
    <button type="button" class="cloner">Ajouter un prénom</button>
</div>
<button type="button" class="cloner">Ajouter un contact</button>
```

Lorsqu'on clique sur un bouton "cloner", il faut mettre à jour le nom du champ en incrémentant le "bon" numéro du champ : pour les contacts, c'est le premier numéro entre crochet qu'il faut incrémenter, pour "prenom", c'est le second numéro.

Le numéro à incrémenter correspond au niveau de répétition auquel on est et qu'on peut calculer avec l'algorithme suivant :

- Partir du champ en cours 
- Remonter vers la racine en faisant +1 à chaque fois qu'on rencontre un champ répétable.

C'est ce que fait la méthode `Field::repeatLevel()`.

Ce niveau de répétition est stocké dans l'attribut `data-level` des boutons de clonage. Par exemple :

```html
<button type="button" class="cloner" data-level="2">Ajouter un prénom</button>
```

[Accueil](..)

# Mise en route

## Installation

* checkout svn
* autoloader
    - utiliser l'autoloader livré en standard
    - utiliser l'autoloader de votre appli

## Introduction

Exemple : Saisie d'une recette de cuisine

Modélisation classique (relationnelle) :

![](http://yuml.me/0c940b08)

<!--
Code du diagramme yuml :
[Recette|ID-Recette;Nom;ID-Type;ID-Difficulté;ID-Prix;Végétarien{bg:orange}]
[Type de plat|ID-Type;Libellé]->[note: Entrée/Plat principal/Dessert/etc.{bg:cornsilk}]
[Difficulté|ID-Difficulté;Libellé]->[note: Facile/Moyen/Difficile{bg:cornsilk}]
[Gamme de prix|ID-Prix;Libellé]->[note: Bon marché/Prix moyen/Cher{bg:cornsilk}]
[Temps de préparation|ID-TempsPrepa;ID-Recette;Ordre;LibelléEtape;Durée]->[note: Prépa-10min Cuisson-60min Glaçage-20min{bg:cornsilk}]
[Liste d'ingrédients|ID-Liste;ID-Ingrédient;Quantité;Remarque]1..n->[Ingrédients|ID-Ingrédient;Nom]
[Listes d'ingrédients]1..n->[Liste d'ingrédients]
[Listes d'ingrédients|ID-Liste;ID-Recette;Ordre]->[note: On peut avoir plusieurs listes d'ingrédients pour une même recette.{bg:cornsilk}]
[Listes d'étapes|ID-Liste;ID-Recette;Ordre;NomEtape;DescriptionEtape]->[note: -pour la génoise... - pour la garniture... - pour le glaçage...{bg:cornsilk}]
[Recette]1..n->[Listes d'étapes]
[Recette]1..n->[Listes d'ingrédients]
[Recette]1..n->[Temps de préparation]
[Recette]1->[Gamme de prix]
[Recette]1->[Difficulté|IdDifficulté;Libellé]
[Recette]1->[Type de plat]

-->

Dès le départ, c'est compliqué. La majorité des tables sont "artificielles". Le relationnel n'est pas très utile dans notre cas (recettes).

Et si on pensait la structure comme un tableau ?

```php
$lasagnes = array (
    'titre' => 'Lasagnes de Haute-Corse',
    'type' => 'Plat principal',
    'difficulte' => 'Moyenne',
    'cout' => 'Cher',
    'temps' => array (
        array('type' => 'Temps de préparation', 'duree' => 120),
        array('type' => 'Temps de cuisson', 'duree' => 75),
    ),
    'quantite'   => 'Pour 6 personnes',
    'ingredients'=> array (
        array('part' => 'Pour la garniture', 'liste' => array(
            array('nom' => 'Poulet', 'quantite' => 1),
            array('nom' => 'Boeuf','quantite' => '500 g',
            array('nom' => 'Veau', 'quantite' => '500 g'),
            array('nom' => 'Oignon', 'quantite' => 1),
            array('nom' => 'Ail', 'quantite' => '2 gousses'),
            array('nom' => 'Coulis','quantite' => '2 cuillères à soupe'),
            array('nom' => 'Vin rouge', 'quantite' => '20 cl'),
        )),
        array('part' => 'Pour les lasagnes', 'liste' => array (
            array('nom' => 'Farine', 'quantite' => '1 kg'),
            array('nom' => 'Oeufs', 'quantite' => '5'),
            array('nom' => 'Huile', 'quantite' => '1 cuillère à soupe'),
            array('nom' => 'Sel'),
        )),
    ),
   'preparation' => array(
        array('step' => 'Préparation des lasagnes', 'description' => 'Dans un grand saladier...'),
        array('step' => 'Préparation de la viande', 'description' => 'Couper la viande...'),
        array('step' => 'Cuisson des lasagnes', 'description' => 'Étaler la boule...'),
        array('step' => 'Présentation du plat', 'description' => 'Dans un saladier mettre...'),
    ),
);

```

C'est ce que font toutes les bases orientées "document" (mongodb, redis, cassandra, couchdb, hbase, etc.)

C'est également ce genre de structure qu'attendent les moteurs de recherche tels que solr, elasticsearch, lucid imagination, constellio, etc.

Mais si on veut manipuler des données dans ce format, il faut pouvoir les saisir. A quoi va ressembler le formulaire ?


## Nos contraintes

Les formulaires ont toujours eu un statut un peu hybride. Par exemple, en architecture MVC, ça "rentre pas bien" dans les boites car c'est à cheval sur les trois couches : sur le fond, c'est une vue, mais elle est liée au modèle car elle en gère les données et enfin, elle contient du code de validation qui relève plutôt de la logique métier...

Notre formulaire va devoir :

- gérer des champs simples (input text, select, textarea)
- gérer des champs évolués (wysiwyg, date-picker, color picker, lookup ajax, etc.)
- gérer des champ structurés (par exemple temps de préparation = type + durée)
- gérer des champs répétables. On peut avoir des champs simples répétables mais également des champs structurés répétables.
- on pourrait aussi avoir des champs structurés répétables contenant des champs qui soient eux-mêmes répétables. Exemple : enfants = (nom, prénom+)*
- gérer la validation des données coté client et coté serveur : champ obligatoire, format à respecter, taille mini ou maxi, nombre d'occurences, valeurs autorisées
- coté serveur pour la sécurité des données, coté client pour la réactivité (je change de champ, l'erreur s'affiche dans le champ que je viens de quitter).
- gérer des champs conditionnels : tel champ ou telle partie est cachée (ou disabled, ou readonly) lorsque telle ou telle condition est remplie.

On a aussi quelques contraintes techniques :

- Il faut que le code soit lisible et que ce soit facile à faire évoluer (html5, évolution de l'application, etc.).
- Il faut gérer la sécurité : escaper tout ce qui est saisi par l'utilisateur (xss), tokens de sécurité (nonces) pour éviter les csrf
- Il faut que ce soit ergonomique (et éventuellement esthétique)
- Le code html doit être correct (valide)
- etc.

Et quelques contraintes humaines :

- les formulaires ça enquiquinne tout le monde !
- c'est pénible à coder à la main
- on n'a pas le temps d'y passer beaucoup de temps (M. le client, on a fait le devis pour votre formulaire : cinq jours...)
- on a encore moins envie de mettre son nez dans le code écrit pas quelqu'un d'autre...

docalist-forms est une tentative de réponse à tout ça.


## Création du formulaire

Un formulaire est un objet :

```php
$form = new Form();

$form->input('title')
     ->label('Nom de la recette');

$form->select('type')
     ->label('Type de plat')
     ->options(array('Entrée froide', 'Plat', 'Dessert'));

$form->select('difficulty')
     ->label('Difficulté')
     ->options(array('Très facile', 'Facile', 'Moyenne', 'Difficile'));

$form->table('time')
     ->label('Temps de préparation')
     ->repeatable(true)
        ->select('step')
        ->label('Etape')
        ->options(array('Préparation', 'Repos', 'Cuisson'))
     ->parent()
        ->input('duration')
        ->label('Durée (en minutes)');

$form->fieldset("Liste d'ingrédients")
     ->name('ingredients')
     ->repeatable(true)
        ->input('part')
        ->label('Partie de la recette')
     ->parent()
        ->table('liste')->label('Ingrédients')->repeatable(true)
             ->input('name')
             ->label('Ingrédient')
         ->parent()
             ->input('quantity')
             ->label('Quantité ')
         ->parent()
             ->input('remark')
             ->label('Remarque');

$form->table('preparation')
     ->label('Préparation')
     ->repeatable(true)
         ->input('step')
         ->label('Etape')
     ->parent()
         ->textarea('description')
         ->label('Description');

$form->submit('Go !');

```

- Deux classes de base : 

    * `Field` = un champ simple (`Input`, `Select`, `Textarea`, `Datepicker`, etc.)

    * `Fields` = une collection de champs = un champ qui peut contenir d'autres champs (`Form`, `Fieldset`, `Table`, etc.)

- Pour faire un champ structuré : il suffit de donner un nom à une collection de champs (dans l'exemple ci-dessus c'est le cas pour `time`, `ingredients`, `preparation`).

- Tout peut être répétable, le nombre de niveau n'est pas limité : `$field->repeatable(true);`

- La classe Fields est essentiellement une Factory (design pattern) = des méthodes pour créer des champs et les ajouter à la collection.

- On suit de prêt la terminologie html (les noms des contrôles, des attributs, etc.)

- Mais on "harmonise" : par exemple pour les `Fieldset` ce n'est pas `legend()` mais `label()`.

- Séparation vue/modèle (MVC) : dans l'exemple ci-dessus, rien ne dit comment on va afficher le formulaire. Le rendu c'est le boulot des thèmes (SOC, separation of concerns). D'ailleurs on ne sait même pas ce qu'on va afficher : ça peut être du html, du html5, du XUL, etc.

- Les id sont générés automatiquement en cas de besoin (et sont uniques). On peut aussi spécifier ses propres id.

- L'API est extensible : on peut facilement créer un nouveau type de champ (par exemple les nouveaux types html5, des contrôles étendus comme [choosen](http://harvesthq.github.com/chosen/) ou [select2](http://ivaynberg.github.com/select2/)).

- Sérialisation. Un formulaire peut également être sérialisé (`$form->toArray()`; `$form = Form::fromArray()`; `$form->toJson()`; `$form = Form::fromJson()`, etc.)

  * le formulaire peut être stocké dans la config de l'appli
  * on peut envisager un "form builder" qui permet à l'utilisateur de le paramétrer

## Afficher le formulaire

Le rendu d'un formulaire est fait par un thème.

Pour afficher un formulaire (ou un bout de formulaire), il suffit d'appeller la méthode `render()` de l'objet en indiquant le nom du thème qu'on souhaite utiliser (par défaut, c'est le thème ... default) :

Exemple :
```php
$form->render('bootstrap');
```

Chaque thème est responsable :

1. De générer le code html qui va bien.

2. De fournir les assets dont il a besoin (css, js, etc.)

Un thème est composé de templates qui sont chacun en charge de la génération d'un bout du code du formulaire : le container, le label, le widget, la description, etc.
Le thème default contient des templates pour presque tout (il sait afficher tous les champs de base).

Un thème hérite d'un autre thème (et ainsi de suite).

La plupart du temps, un thème va se contenter de "décorer" le boulot fait par le thème parent. Par exemple dans le thème bootstrap, ça ressemble à ça (pseudo code) :

```html
<div class="control-group">
    <label class="control-label" for="$this->id">$this->label</label>
    <div class="controls">appeller le thème parent pour générer le champ input</div>
</div>
```
Du coup c'est très facile de créer un nouveau thème et d'adapter : on ne "surcharge" que ce qu'on veut modifier.

```php
Themes::register(
    'mon-thème',             // le nom de mon thème
    __DIR__ . '/theme/',     // path du répertoire qui contient les templates
    'default',               // le thème parent dont je veux hériter
    array(                   // les assets dont j'ai besoin (css, js)
        'css' => 'mon-theme.css'
    )
);

$form->render('mon-thème');
```

*Remarques :*

1. On peut avoir du code complètement différent d'un thème à l'autre.

   Par exemple les listes de cases à cocher sont générées sous forme de ul/li dans le thème `default` mais ont une structure html complètement différente dans le thème bootstrap.

2. Il n'y a pas de lien direct entre les objets du formulaire et leur rendu. 

   Par exemple un thème peut tout à fait décider de représenter un objet `Fieldset` avec un titre `h3` et une `div` plutôt que d'utiliser des tags `fieldset` et `legend`.

3. Par défaut, le code généré est compacté (suppression de tous les espaces inutiles), mais on peut aussi afficher du code lisible et indenté.

   Exemple : dans wordpress, compact par défaut, lisible si WP_DEBUG est à true.

4. Il y a aussi une option pour voir la pile des templates utilisés. Pratique pour le développement de thèmes.


## Injecter des données dans le formulaire

```php
$form->bind($data)
```


## Valider les données

```php
if ($form->isValid()) {...}
```

## Worflow standard

```php
function createForm() {} // crée le formulaire
function getDefaults() {} // retourne un tableau contenant les valeurs par défaut

// Crée le formulaire
$form = createForm();

// Affichage initial du formulaire, charge les valeurs par défaut
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $form->bind(getDefaults());
}

// Soumission de formulaire, charge et valide les données saisies
else {
    // Charge les données fournies
    $form->bind($_POST);

    // Si les données sont valides, on les enregistre et c'est fini
    if ($form->valid()) {
        $post->post_content = json_encode($form->data()); // par exemple... à creuser.
        echo "Success";
        return;
    }

    // Données invalides, les erreurs seront affichées render() ci-dessous
}

// Affiche le formulaire
$form->render('default');
```

- indépendant du formulaire
- automatisable. Dans wordpress on aura une classe Metabox qui fait ça une fois pour toute. Du coup, une metabox = juste un formulaire

## Design technique

- Open source, GPL V3 (produit d'appel)

- Zéro dépendance php. Pour le js : librairies standard (jquery, bootstrap...)

- Clean IP : 100% écrit içi.

- Que de l'objet : aucune fonction globale, pas de define, pas de variables globales, etc.

- Tout est namespacé (php 5.3 minimum)

- Fluent interface : API fluide à la jquery, on chaine les appels

- Inclut un `autoloader` (optionnel, compatible PSR-0) : seules les classes utilisées sont chargées (économie mémoire), pas besoin de s'embêter avec des `include`.

- Les classes sont petites : meilleures granularité dans APC en cas de stress.

- Toute l'api est documentée (en français). La doc peut être générée automatiquement (j'utilise apigen), completion auto dans les IDE.

- Abstraction pour les sources de données : la méthode `options()` des contrôles à choix 
  multiples (`Select`, `Checklist`, `Radiolist`...) est très souple. Elle accepte indifférement : 
  un tableau, un objet itérable (interface `Iterator`), un callback, etc. :
    
    ```php
    // Un Select avec toutes les catégories wordpress : 
    $form->select('category')->options(
        array_column( // php 5.5
            get_terms('category', array('hide_empty' => false),
            'name', 
            'slug'
        )
    );

    // Un select listant les fichiers du répertoire en cours
    $form->select('files')->options(new FileSystemIterator (__DIR__));

    // Exemple avec un callback
    $form->select('files')->options(function(){
        // Récupérer les données quelque part, les filtrer (if current_user_can() par exemple)
        // et retourner un tableau ou un Iterator
    });

    ```

## Not invented here

Expliquer qu'il existe plein d'autres librairies de gestion de formulaires et pourquoi elles ne convenaient pas tout à fait.

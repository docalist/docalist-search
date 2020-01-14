# Shortcode docalist_search_results #

Depuis la version 4.3.0, *docalist search* dispose d'un shortcode `docalist_search_results` qui permet d'intégrer une *liste de réponses docalist search* dans un autre contenu WordPress (article, page, widget, notice, etc.)

Le principe général est le suivant : 

1. On utilise le moteur de recherche pour faire une requête qui sélectionne les documents qu'on veut afficher. En utilisant les options du moteur de recherche, on choisit le nombre de notices qui seront affichées (paramètre `size` de la requête) et l'ordre de tri à appliquer (paramètre `sort` de la requête).
2. On copie l'url de recherche obtenue : elle contient tous les paramètres dont le shortcode a besoin.
3. On insère le shortcode dans une page WordPress et on colle l'url de recherche copiée.

Lorsque la page WordPress est affichée, docalist search va "rejouer" la requête et afficher les réponses obtenues. A la fin des résultats, un lien "voir tout »" permet de rediriger l'utilisateur vers le moteur de recherche. Le libellé "voir tout" est volontairement ambigü : il signifie à la fois "voir toutes les réponses obtenues" et "voir toutes les informations qu'on a sur la ou les notices affichées".

Le contenu généré par le shortcode est dynamique et il est mis à jour automatiquement lorsque des documents sont ajoutés, modifiés ou supprimés du moteur de recherche.


## Syntaxe ##

Le shortcode doit être tapé en respectant la syntaxe suivante :

<tt>[docalist_search_results ***paramètres***]***url***[/docalist_search_results]</tt>

Exemple : 
```markdown
[docalist_search_results]https://mon.site/recherche/[/docalist_search_results]
```

L'url de recherche est obligatoire : si elle n'est pas fournie, le shortcode affichera le message "Aucune url de recherche indiquée".

L'url de recherche doit être collée "telle quelle" entre la balise ouvrante et la balise fermante du shortcode, sans aucun espace ou retour à la ligne surperflu (sinon ça ne fera pas du tout ce qu'on veut : WordPress essaie de faide de l'auto-embed, il convertit les sauts de lignes, ce qui insère des tags html dans l'url, etc.)

Les autres paramètres, saisis à l'intérieur de la balise ouvrante, sont tous optionnels et sont décrits plus bas.

Remarques : 

- la syntaxe des shortcodes WordPress est très stricte : pas d'espaces avant ou après les crochets, le slash, etc.
- initalement, le shortcode devait s'appeller docalist-search-results (avec des tirets) mais ça génère des bugs pas possibles dans WordPress !
- ne pas chercher à taper ou à modifier l'url manuellement : pour qu'une url soit valide, il y a de nombreuses règles à respecter (il faut encoder certains caractères avec des séquences %xxx en garder certains intacts parce qu'ils servent de délimiteurs, il faut remplacer les espaces par des plus, etc.) donc il vaut mieux rester sur le principe proposé : recherche dans le moteur, copie de l'url, collage dans le shortcode. Pour modifier une recherche existante, faire l'inverse : copier l'url qui figure dans le shortcode, la coller dans le moteur de recherche, faire les modifs, copier la nouvelle url et la coller dans le shortcode.


## Paramètres optionnels ##

Le shortcode accepte quelques paramètres optionnels qui régissent l'affichage des résultats.
Ces paramètres doivent être saisis à l'intérieur de la balise ouvrante du shortcode : 

<tt>[docalist_search_results ***param1=valeur1 param2="valeur 2" etc.***]</tt>

Si la valeur d'un paramètre contient des espaces ou des caractères spéciaux, la valeur doit être encadrée avec des guillemets doubles (exemple param2 ci-dessus). Dans le cas contraire, la valeur peut être indiquée telle quelle (exemple param1). En cas de doutes, mettre des guillemets.

Attention : il faut que ce soit les guillemets doubles standards, pas le guillemets typographiques ou les guillemets à l'anglaise.


### Paramètre `template` : format d'affichage des résultats ###

Par défaut, le shortcode affiche uniquement le titre de chacune des réponses obtenues, et chaque titre affiché est un lien qui permet de voir la réponse en affichage détaillé. Ce format d'affichage par défaut s'appelle `title` et c'est lui qui est utilisé quand le paramètre `template` est absent, quand il est vide ou quand il contient une valeur incorrecte.

Ce format est bien adapté quand on veut un résultat compact (beaucoup de réponses à afficher, shortcode inséré dans un contenu déjà copieux, etc.)

Le shortcode supporte nativement deux autres formats d'affichage :

- `excerpt` : affiche le titre du document sous forme de lien, puis affiche le contenu du document en utilisant la fonction WordPress `the_excerpt()`. Pour un article ou une page WordPress, ça affiche un extrait du contenu du document (le début), pour une notice Docalist, ça affiche la notice en utilisant l'affichage défini dans la grille "format court" de ce type de notice.
  
  Exemple : <tt>[docalist_search_results ***template="excerpt"***]url[/docalist_search_results]</tt>
- `content` : affiche le titre du document sous forme de lien, puis affiche le contenu du document en utilisant la fonction WordPress `the_content()`. Pour un article ou une page WordPress, ça affiche le contenu intégral du document, pour une notice Docalist, ça affiche la notice en utilisant l'affichage défini dans la grille "format long" de ce type de notice.
  
  Exemple : <tt>[docalist_search_results ***template="content"***]url[/docalist_search_results]</tt>

Remarque : pour cri-adb, le format "content" ne donne pas de très bons résultats (ça "déborde") car les formats longs des notices ont été paramétrés spécifiquement pour le thème cri-adb.

**Usage avancé, formats d'affichage personnalisés :**

Outre les trois formats prédéfinis listés ci-dessus, le paramètre `template` permet également d'indiquer le nom d'un fichier du thème écrit spéciquement pour l'affichage des résultats.

Cet usage avancé (c'est le développeur du thème qui peut faire ça) permet d'avoir un contrôle total sur l'affichage des résultats.

Exemple : 

Dans le thème du site, le développeur crée un fichier `/shortcodes/list-results.php` qui contient le code suivant : 

```php
<?php
if (! have_posts()) {
    echo "pas de réponses";
    return;
}

echo '<ul>';
while (have_posts()) {
    the_post();
    echo '<li><h3>', get_the_title(), '</h3></li>';
    echo '<div>', get_the_excerpt(), '</div>';
}
echo '</ul>';
```

Pour afficher les résultats du shortcode en utilisant ce fichier, il suffit de l'indiquer dans le paramètre `template`: 

```
[docalist_search_results template="shortcodes/list-results.php"]url[/docalist_search_results]
```

Remarques : 

- lorsqu'il est exécuté, le template a accès aux variables suivantes : 
  ```php 
  /**
     @var string      $url            L'url de recherche indiquée dans le shortcode.
     @var string[]    $attributes     Les attributs du shortcode.
     @var string      $template       Le path du template utilisé (i.e. ce fichier).
  */
  ```
  
- si le template affiché n'est pas valide (nom ou path incorrect) ou n'est plus valide (par exemple, parce que le site a changé de thème), le shortcode repasse automatiquement sur le template par défaut "title". Dans ce cas, un message est affiché aux administrateurs du site pour leur signaler le problème : 
  
  > *Note aux admins : le template 'shortcodes/list-results.php' indiqué dans le shortcode n'existe pas, utilisation du template par défaut 'title'.*


### Paramètre `more` : lien "Voir tout" ###

Le paramètre `more` permet de paramétrer le lien *"Voir tout »"* qui est affiché à la fin des résultats et qui permet de rediriger l'utilisateur vers le moteur de recherche.

Exemple : 
```
[docalist_search_results more="Voir les résultats dans le moteur de recherche"]url[/docalist_search_results]
```

Vous pouvez également désactiver la génération de ce lien en indiquant la valeur spéciale "false" en paramètre :

```
[docalist_search_results more="false"]url[/docalist_search_results]
```

Remarque : le lien "voir tout" est toujours généré (même si on a une seule notice ou seulement une page de résultats). Utilisez more="false" si vous ne voulez pas du lien.


### Paramètre `no-results` : message affiché s'il n'y a pas de réponses ###

Si la requête indiquée dans l'url de recherche ne retourne aucun résultat, le shortcode affiche par défaut le texte "Rien à afficher...". 

Le paramètre `no-results` permet de changer le message affiché : 

```
[docalist_search_results no-results="pas de bol !"]url[/docalist_search_results]
```


## Styles CSS des résultats affichés ##

Si vous utilisez un template personnalisé, c'est vous qui générait le code html, donc vous savez quels sont les tags générés et comment les personnalisés.

Pour les templates prédéfinis, vous pouvez personnaliser les styles CSS via le customizer WordPress (option "CSS additionnel"). Pour vous aider, le shortcode génère, pour chaque tag, des classes que vous pouvez utiliser pour définir vos styles :

```html
<div class="docalist-search-results">
    <ul class="hfeed">
        <li class="hentry">
            <h3 class="entry-title">
                <a rel="bookmark" href="lien">titre</a>
            </h3>
            <div class="entry-content">
                extrait ou contenu
            </div>
        </li>
    </ul>
    <form class="more" action="url" method="post">
        <input type="submit" value="Voir tout »" />
    </form>
</div>
```

S'il n'y a aucune réponse à afficher, le code html généré est le suivant :

```html
<div class="docalist-search-results">
    <ul class="no-results">
    	<li><a href="url">Rien à afficher...</a></li>
    </ul>
</div>
```

Exemple de code CSS personnalisé :
 
```css
.docalist-search-results {
    background-color: lightgray;
}

.docalist-search-results .hfeed {
    list-style: none;
}

.docalist-search-results .entry-title > a {
    color: red
}
```

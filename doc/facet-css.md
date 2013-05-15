# Styles CSS des facettes #

Les facettes affichées dans le widget "dcalist-search-facets" ont la structure suivante :

```html

    <!-- 
        Début du widget. <before_widget>Contient du code html définit par le thème 
        lors de l'appel à la fonction WordPress register_sidebar().
        Par exemple : <li id="docalist-search-facets-3" class="widget docalist-search-facets">
    -->
    <before_widget> 

    <!--
        Titre du widget. Ce bloc n'est affiché que si un titre a été fournit dans les
        paramètres du widget. <before_title> et <after_title> sont des chaines contenant
        du code html qui est définit par le thème lors de l'appel à register_sidebar().
        Par exemple : <h2 class="widgettitle"> et </h2>
    -->
    <before_title>Titre indiqué dans les paramètres du widget <after_title>

    <!--
        Liste des facettes
    -->
    <dl>
        <!--
            Première facette
        -->
        <dt>Titre facette</dt>

        <dd>terme 1</dd>
        <dd>terme 2</dd>
        <dd>terme 3</dd>
        <dd>terme 4</dd>

        <!--
            Seconde facette
        -->
        <dt>Facette 2</dt>

        <dd>terme 1</dd>
        <dd>terme 2</dd>
        <dd>terme 3</dd>
        <dd>terme 4</dd>

        <!--
            etc.
        -->

    </dl>

    <!-- 
        Fin du widget. <after_widget>Contient du code html définit par le thème 
        lors de l'appel à la fonction WordPress register_sidebar().
        Par exemple : </li>
    -->
    <after_widget>

```html
    <h3>Affiner la recherche</h3>
    
    <ul>                        <!-- *start-facet-list : Début liste des facettes -->
        <li>                        <!-- *start-facet : Début facette -->
    
            <h3>Mots-clés</h3>          <!-- *facet-title : titre de la facette -->
    
            <ul>                        <!-- *start-term-list : Début liste des termes-->
    
                <li>                        <!-- start-term : Début terme -->
                    <a href="">terme</a>        <!-- format du terme (actif ou non) -->
                </li>                       <!-- end-term : Fin terme -->
    
            </ul>                       <!-- *end-term-list : Fin liste des termes -->
    
        </li>                       <!-- *end-facet : Fin facette -->
    
    </ul>                       <!-- *end-facet-list : Fin liste des facettes -->
```

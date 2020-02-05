# Syndication des recherches #

Depuis la version 4.3.0, *docalist search* permet de créer des flux de syndication pour les recherches. 

Les flux générés permettent aux utilisateurs de faire de la veille en utilisant leur lecteur de flux habituel et de découvrir les nouveaux documents qui répondent à leurs critères de recherche.

## Options ##

Par défaut les flux de syndication des recherches sont désactivés. Pour les activer, il faut aller dans les réglages de *docalist-search*, rubrique *paramètres du moteur de recherche*.

Le paramètre *syndication des recherches* propose trois options :
1. Ne pas générer de flux de syndication,
2. Générer un flux de syndication contenant l'extrait (format court),
3. Générer un flux de syndication contenant le contenu (format long).

Avec la première option, les flux de syndication sont désactivés. Si un utilisateur accède à l'url du flux de syndication, il obtiendra la page de recherche standard avec ses résultats et non pas un flux de syndication.

Avec les deux autres options, les flux de syndication sont activés pour les recherches. Les flux générés contiendront soit l'extrait, soit le contenu intégral des documents selon l'option que vous choisissez.

Pour les articles et les pages WordPress, l'extrait des documents est généré par WordPress et contient soit le contenu du champ `post_excerpt`, soit le début du contenu du champ `post_content`. Pour les notices docalist, l'extrait est généré en utilisant la grille "format court" définie pour chaque type de notice.

Si vous choisissez l'option "contenu", les flux de syndication contiendront le contenu intégral des articles et des pages WordPress (champ `post_content`). Pour les notices docalist, le contenu est généré par la grille "format long" définie pour chaque type de notice.

## Format des flux de syndication ##

Les flux de syndication des recherches sont [générés par WordPress](https://wordpress.org/support/article/wordpress-feeds/), *docalist search* se contentant de fournir à WordPress les documents à intégrer dans les flux.

Les flux de syndication docalist supportent donc tous les formats supportés par WordPress : 
- `atom` : flux au format [Atom](http://www.atomenabled.org/),
- `rdf` : flux au format [RDF/RSS 1.0](http://purl.org/rss/1.0/), 
- `rss` : flux au format [RSS 0.92](http://backend.userland.com/rss092),
- `rss2` : flux au format [RSS 2.0](http://www.rssboard.org/rss-specification) (le plus courant).

Une erreur sera générée si le format de syndication demandé n'est pas valide :

```xml
<error>
    <code>wp_die</code>
    <title>WordPress » Error</title>
    <message>Not a valid feed</message>
    <data>
        <status>404</status>
    </data>
</error>
```

## Url de syndication ##

Lorsque les flux de syndication sont activés, l'url permettant de générer un flux de syndication est obtenue en ajoutant un paramètre `feed=format` à l'url de recherche en cours.

Par exemple, si la recherche en cours a l'url suivante :
```markdown
/recherche/?q=enfance
```

Les flux de syndication générés auront les urls suivantes :
```markdown
/recherche/?q=enfance&feed=atom
/recherche/?q=enfance&feed=rdf
/recherche/?q=enfance&feed=rss
/recherche/?q=enfance&feed=rss2
```

## Intégration dans le thème du site ##

Pour permettre aux utilisateurs de découvrir l'url de syndication de leur recherche, le thème du site doit être modifié de manière à afficher un lien ou un bouton *"flux de syndication pour cette recherche"* dans la page qui affiche les résultats.

Pour cela, la classe `SearchEngine` de docalist dispose d'une méthode `getSearchFeedUrl(format=rss2)` qui retourne l'url à utiliser.

Cette fonction retourne une chaine vide : 
- si les flux ne sont pas activés, 
- si on n'est pas sur une page de résultats de recherche,
- si la recherche en cours ne retourne aucun résultat.

Dans le cas contraire, elle récupère l'url de la recherche en cours, ajoute un paramètre `feed` avec le format demandé, et supprime les paramètres inutiles tels que numéro de page et ordre de tri.

Par exemple, si la recherche en cours a l'url suivante :
```markdown
/recherche/page/2/?q=enfance&sort=posttitle
```

Un appel à la fonction `getSearchFeedUrl()` retournera l'url suivante :
```markdown
http://cri-adb/recherche/?q=enfance&feed=rss2
```

Le numéro de page est supprimé car, quelle que soit la page en cours, c'est la même recherche, et donc le même flux de syndication.

Le critère de tri est supprimé car pour un flux de syndication, l'objectif est d'obtenir les nouveaux documents publiés. C'est donc toujours un tri par date de création décroissante qui est appliqué.

Par contre, tous les autres paramètres de la recherche en cours sont conservés comme paramètres des flux de syndication.

C'est le cas notamment du paramètre `size` qui permet de choisir le nombre de réponses par page, et qui est repris pour déterminer le nombre de documents présents dans les flux de syndication générés.

Par exemple, pour la recherche suivante, les flux de syndication générés contiendront les 25 derniers documents publiés :
```markdown
http://cri-adb/recherche/?q=enfance&size=25
```

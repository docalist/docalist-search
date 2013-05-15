# Paramètres passés en query string #

Notes sur la façon dont sont gérés les paramètres de recherche en query string.

## Paramètres d'une recherche ##

Lorsqu'on exécute une recherche, on peut spécifier les paramètres suivantes :

- `s` : la chaîne de recherche saisie par l'utilisateur. On réutilise l'argument standard de WordPress. Remarque : c'est plutôt `q` (comme query) sur tous les moteurs de recherche que je connais.

- `filter` : la liste des filtres à appliquer. On peut avoir plusieurs filtres (par exemple un filtre sur le type de document, un autre sur la langue, un autre sur le pays, etc.) et chaque filtre peut contenir plusieurs valeurs qui seront alors combinées en `OU` (par exemple plusieurs types de document).

- `facet` : la liste des facettes à afficher, avec éventuellement, pour chaque facette, le nombre de termes à suggérer (par exemple : initialement on affiche 10 mots-clés et l'utilisateur demande à en avoir plus).

- `sort` : l'ordre de tri des réponses. On peut avoir plusieurs critères de tri (par exemple trier d'abord par type de document puis par date) et pour chaque critère, il faut pouvoir indiquer si on veut un tri ascendant ou un tri descendant.

- `explain` : c'est une option qui permet d'obtenir des informations sur la requête exécutées et sur les hits obtenus.

- `page` : le numéro de la page de résultats à afficher. On réutilise l'argument standard de WordPress.

- `postsperpage` : nombre de réponses par page. WordPress dispose en interne d'une variable nommée ainsi, mais ce n'est pas une query var qu'on peut passer en paramètre. todo: déclarer comme query var ? donner un nom différent ? (beaucoup de moteurs utilisent `pagesize`).

- `post_type` : le type de contenu sur lequel porte la recherche. On réutilise l'argument standard de WordPress.

Or dans notre cas, on doit pouvoir :
- passer plusieurs filtres à la requête (par exemple un filtre sur le type de document, 
un autre sur la langue, un autre sur le pays, etc.)
- passer plusieurs valeurs pour un même filtre (par exemple plusieurs types de document
croisés en "OU").



## Version initiale du code ##

Dans la version intiale de mon code, j'ai essayé de faire les choses "à la wordpress" :

- déclaration comme "query vars" de tous les paramètres paramètres dont on a besoin (filter, facet, sort, etc.)
    
```php

    add_filter('query_vars', function($vars) {
        $vars[] = 'filter';
        $vars[] = 'sort';
        $vars[] = 'facet';
        // ... etc.
        return $vars;
    }, 10, 1);
```

- utilisation de ces query vars pour construire la requête de recherche à exécuter :

```php

    $query = get_query_var('s')
    $filter = get_query_var('filter');
    // ... etc.
```
   
## Problèmes posés par WordPress ##

### query vars trop compliquées pour WordPress ###
WordPress ne permet pas d'avoir des "query vars" compliquées en paramètre :
si on déclare `filter` comme query var et qu'on passe en query string quelque
chose de la forme : 

`xxx?filter[a][]=b`

on obtient des warning de la forme :

    Notice: Array to string conversion in D:\web\Prisme\wordpress\wp-includes\class-wp.php on line 270
    
### backslashes qui trainent ###

WordPress fait des stripslashes sur tout. Si on fait une recherche de la forme `s=d'abord`, il fait d'abord un stripslashes puis un remove_slashes, ce qui fait qu'on obtient bien la chaîne recherchée. Mais en fait cela ne fonctionne qu'avec les query vars déclarées en interne par WordPress. 

Si on teste avec `facet=d'abord` (en ayant correctement déclaré facet comme query var), on obtient `d\'abord`. Pire, si l'url est ré-écrite sous forme de lien (login, etc.) on va avoir un antislash de rajouté à chaque fois : `d\\\\\\\'abord`.

Du coup c'est assez compliqué à gérer dans le code : si c'est une variable WordPress faire ceci, si c'est une des notres, faire cela, etc.

### Encodage et url compliquées ###

Du fait de l'encodage des caractères spéciaux, on obtient très vite des urls impossibles à lire. 
Exemple : 

    http://prisme/base/?s=violence&filter%5Btype.keyword%5D%5B0%5D=Article&filter%5Btype.keyword%5D%5B1%5D=Ouvrage


## Solutions envisagées ##

### Ne pas avoir de query vars compliquées  ###

Par exemple, écrire les filtres sous la forme :

`filter.type.keyword[]=value1` 

Cela suppose de déclarer dans WordPress tous les filtres possibles. Comme c'est dynamique, ça risque d'être compliqué et d'autre part, il y en a beaucoup.

Ne résoud pas le problème des caractères encodés.

### Encoder plusieurs valeurs dans la même query var ###

par exemple : 

`filter.type.keyword=value1,value2` 

Le problème, c'est qu'il faut trouver un séparateur dont on soit sur qu'il n'est jamais utilisé dans un filtre. Difficile, car en théorie, un filtre peut contenir n'importe quoi.
Par ailleurs, un caractère supplémentaire qui va se retrouver encodé.
Enfin, on se retrouve dans le code à faire du parsing des valeurs à chaque fois qu'on a besoin d'accéder aux valeurs. (par exemple, ce n'est pas simple de savoir si le filtre value2 est actif ou pas).

### Solution retenue ###
Gérer nous mêmes les paramètres.

### Est-ce que le serveur répond ? ###

- `$es->exists()`
- `$es->head() === 200`

### Liste des index (et paramètres) ###

- `$es->get('_settings');`

### Est-ce qu'un index existe ? ###

- `$es->exists($index)`

### Paramètres d'un index : ###

- `$es->get("$index/_settings");`

### Créer un index ###

- `$es->put($index);`

### Créer un index en indiquant les paramètres : ###

```php

    $es->put('daniel', array(
        'settings' => array(
            'number_of_shards' => 1,
            'number_of_replicas' => 0,
        )
    ));

```

### Supprimer un index ###

- `$es->delete($index);`

### Ajouter un enregistrement dans l'index (en spécifiant l'id du record) : ###

```php

        $es->put('twitter/tweet/1', array(
            "user" => "kimchy",
            "post_date" => "2009-11-15T14:12:12",
            "message" => "trying out Elastic Search",
        ));

```

(si l'index n'existe pas, il est créé. si le type n'existe pas il est créé.
si l'enreg existe déjà, il est mis à jour).


### Ajouter un enregistrement dans l'index (SANS l'id du record) : ###

```php

        $es->post('twitter/tweet/', array(
            "user" => "kimchy",
            "post_date" => "2009-11-15T14:12:12",
            "message" => "trying out Elastic Search",
        ));

```

:  On utilise un POST au lieu d'un PUT.


### Lookup / lister toutes les valeurs d'un champ ###
https://groups.google.com/forum/?hl=fr&fromgroups=#!topic/elasticsearch/0H-CVHORSzs
select DISTINCT(filed) from TABLE
{
    "query" : {
        "match_all" : {  }
    },
    "facets" : {
        "tag" : {
            "terms" : {
                "field" : "STRINGVALUE",
                "all_terms" : true
            }
        }
    }
}


- `reponse`

### Ne pas indexer un champ ###
https://groups.google.com/forum/?hl=fr&fromgroups=#!topic/elasticsearch/QJvzZG9ALjw
et plus particulièrement 
https://groups.google.com/d/msg/elasticsearch/QJvzZG9ALjw/Ly7exEK52AMJ

String fields accept: { index: no|not_analyzed|analyzed}: 

 - no:          don't index the string 
 - no_analyzed: index the string exactly as passed in 
 - analyzed:    first analyze the string, then index the resulting tokens 

Other scalar values, eg number, date etc accept: {index: no|analyzed} 
where "analyzed" really means "yes".  There is no analysis phase for 
non-string fields, so not_analyzed vs analyzed is meaningless.  We 
either index the value or we don't. 

Other scalar values, eg number, date etc accept: {index: no|analyzed} 
where "analyzed" really means "yes".  There is no analysis phase for 
non-string fields, so not_analyzed vs analyzed is meaningless.  We 
either index the value or we don't. 

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

Question

- `reponse`

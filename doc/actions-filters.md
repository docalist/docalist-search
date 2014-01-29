# Actions et filtres. #

``` 
    $types = apply_filters('docalist_search_types', array());
```

SettingsPage:194
Indexer:156

## Déclarer les contenus indexables ##

Le filtre `docalist_search_types` permet d'indiquer à Docalist Search qu'un contenu est indexable.

Les contenus qui serons fourni à ElasticSearch peuvent être de n'importe quel type : un article, une page, le profil d'un utilisateur, un commentaire, un custom post type, un enregistrement provenant d'une custom table, un media, etc.

Dans ce qui suit, on prend comme exemple l'indexation des articles de WordPress.

Ce filtre s'utilise de la façon suivante :

        add_filter('docalist_search_types', function ($types) {
            $types['wpposts'] = array(
                'label'     => 'Articles WordPress',
                'mappings'  => callback,
                'index'     => callback,
                'reindex'   => callback,
            );

            return $types;
        });

Chaque type de contenu doit avoir un identifiant unique (`wpposts` dans notre exemple). Par la suite, cet identifiant est simplement appelé le "type" du contenu.

Pour chaque type ainsi déclaré, on doit définir quatre choses : un libellé (c'est ce libellé que verra l'administrateur dans les pages de Docalist Search) et trois callbacks qui permettent de répondre aux questions suivantes :

### mappings ###

Le premier callback permet d'indiquer à ElasticSearch la configuration qu'on souhaite utiliser pour ce type de contenu.

La fonction de callback fournie doit avoir la signature suivante :

        /**
         * Retourne les mappings Docalist Search à utiliser pour cette base de
         * données.
         *
         * @param string $type Le type de contenu.
         * 
         * @return array
         */
        public function mappings($type) {
            return array(...);
        }

Votre fonction sera appelée lorsque l'index ElasticSearch est créé. Docalist Search vous repasse en paramètre le type indiqué dans add_filters (`wpposts` dans notre exemple).

Elle doit retourner un tableau qui décrit un mapping ElasticSearch valide.
cf. : [mapping ElasticSearch valide](http://www.elasticsearch.org/guide/reference/mapping/)

- quels sont les paramètres ElasticSearch à utiliser pour ce type de contenu ?


- comment convertir un contenu de ce type en document ElasticSearch ?
- comment indexer la totalité des documents de ce type ?





de savoir comment indexer les contenus choisis par l'administrateur.

Pour chaque type de contenur indexable, les plugins qui répondent à ce filtre doivent implémenter quatre fonctions :

- **`mappings`** : retourne les mappings ElasticSearch à utiliser pour ce type de contenu


- **`index`** : méthode à appeler pour générer le document ElasticSearch à partir du type de contenu passé en paramètre

        /**
         * Transforme un enregistrement en document Docalist Search.
         *
         * @return array
         */
        public function map($post, $type, $id) {
            return array();
        }

 
- **`reindex`** : méthode appelée pour réindexer la totalité des contenus de ce type.


























coté DS                                                     coté plugin
$settings = apply_filters(docalist_search_settings)         je crée mes analyseurs, etc

$mappings = apply_filters(docalist_search_mappings)         j'ajoute le mapping du type à indexer

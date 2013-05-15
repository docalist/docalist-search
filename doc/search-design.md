# Principe de la recherche #

Lorsque WordPress traite une requête, il examine l'url demandée et construit
une requête sql à partir des paramètres obtenus dans WP_Query. Il appelle
ensuite le filtre "posts_request" pour permettre aux plugins de filtrer la
requête sql avant que celle-ci ne soit exécutée.

C'est ce filtre qu'on intercepte pour remplacer la recherche standard de
WordPress par notre propre recherche (cf search).

Notre méthode commence par vérifier qu'il s'agit bien d'une recherche et va
ensuite :

- Eviter à WordPress de faire des requêtes inutiles. no_found_rows
- Construire une requête ElasticSearch à partir des paramètres qui figurent
  dans WP_Query, l'exécuter et récupérer les résultats.

- Si on n'a aucune réponse ou si une erreur survient, le filtre retourne
  sql=null, ce qui fait qu'aucune requête sql ne sera exécutée.
  Remarque : ce dernier point n'est pas documenté. Inférée à partir du code
  de WPDB::get_results() :

  if ($query) $this->query( $query );  else return null;

- Si on a des réponses, on récupère les POST_ID de chaque hit (c'est ce que
  nous retourne ElasticSearch) et on génère une requête SQL de la forme :

  SELECTFROM wp_posts
  WHERE id ID (<liste des ID>)
  ORDER BY FIELD(id, <liste des ID>)

  On demande ainsi à WordPress de charger les posts correspondants aux hits
  obtenus et de les trier selon l'ordre indiqué par ES.
  (cf http://stackoverflow.com/a/3799966).

- Cette approche est meilleure que ce que j'ai pu voir dans tous les autres
  plugins de recherche que j'ai regardé : on ne fait aucune requête SQL
  inutile (i.e. on ne laisse pas WordPress faire son traitement par défaut),
  on n'a pas à retrier les résultats après coup (beaucoup de plugins retrient
  "à la main"), c'est hyper efficace et ce qu'elle que soit la recherche
  exécutée : une seule requête ES pouvant contenir les facettes, une seule
  requête SQL qui va porter sur 10 posts qu'on charge directement via leur
  ID.

Une fois qu'on a fait la recherche, il faut donner quelques informations à
WordPress pour qu'il gère correctement la pagination des résultats : nombre
de réponses obtenues, nombre total de pages, etc.

On le fait en interceptant le filtre "posts_results" qui est exécuté juste
après une recherche (cf searchResults).

Lors d'une requête normale, WordPress gère ça lui-même en faisant une
requête MySql supplémentaire de la forme SELECT FOUND_ROWS().
cf http://wordpress.stackexchange.com/q/39827

Lorsqu'on fait nous même la recherche, on empêche WordPress de faire cette
requête inutile en query_vars['no_found_rows'] = true dans WP_Query.

On peut ensuite vérifier qu'on n'exécute qu'une seule requête SQL en tout et
pour tout en ajoutant define('SAVEQUERIES', true) dans le fichier
wp-config.php et en dumpant $wpdb->queries dans le footer du template.

DM, 05/04/13
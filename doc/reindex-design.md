# Réindexation #

## Principe : ##

- l'utilisateur choisit les types qu'ils veut réindexer
- DS génère l'action `docalist_search_reindex_{$type}` pour chacun des types choisit
- le plugin qui gère le type **doit** répondre à cette action
- il doit réindexer **la totalité** des documents de type
- pour réindexer un document, il appelle la méthode index() de l'indexeur. 

## Problématique ##
 
Pour réindexer complètement une collection, il faut :

- ajouter dans le moteur les documents qui ont été créés depuis la dernière indexation
- mettre à jour les documents qui existaient déjà mais qui ont été modifiés
- supprimer du moteur les documents qui n'existent plus dans la collection

L'ajout et la mise à jour des documents sont assez simples à gérer. On stocke un document dans le moteur en indiquant son type et son ID : si le document existe déjà, il est mis à jour ; s'il n'existe pas, il est créé.

Pour gérer les documents supprimés, une solution très simple consiste à vider complètement l'index avant de démarrer l'indexation. Une fois la réindexation terminée, on est sur d'avoir une collection de documents à jour.

Malheureusement, cette solution naive à un handicap majeur : entre le moment ou la réindexation commence et le moment où elle se termine, on ne peut plus faire de recherches (plus exactement, on peut lancer des recherches, mais on n'obtiendra pas les réponses attendues). Même si une partie des documents redevient disponibles au cours de la réindexation (*commités*), on n'a toujours qu'une partie des résultats. Dans tous les cas, on a interrompu le service de recherche. La durée d'interruption (ou de dysfonctionnement) va varier selon les données indexées, la volumétrie, les performances des serveurs, etc., mais sur des bases conséquentes (plusieurs centaines de milliers de documents), elle est loin d'être négligeable et selon le contexte, elle peut être rédhibitoire (imaginons par exemple un site de commerce électronique, ou bien une base documentaire, etc.)

Il faut donc trouver une solution pour réindexer la base sans avoir à vider l'index au préalable et dans ce contexte, la détection des documents supprimés devient un peu plus compliquée à gérer.  
  
En théorie, il faut créer un différentiel entre la liste des documents qui existent dans le moteur et la liste des documents qui existent dans la collection. C'est compliqué à faire parce que Docalist Search n'a pas accès aux documents qui composent la collection. Il faudrait donc un système d'actions ou de filtres qui permettrait à Docalist Search de demander à un plugin si le document qui avait tel ID existe toujours ou non, appliquer ce filtre à tous les documents du moteur, et supprimer un par un les documents qui n'existent plus. Sur une collection de plusieurs centaines de milliers de documents, un process comme ça va obligatoirement prendre longtemps...

## Fonctionnement ##
Pour résoudre le problème, Docalist Search implémente un principe assez simple :

- lorsque Docalist Search demande à un plugin de réindexer sa collection, celui-ci doit réindexer **la totalité** des documents qui composent la collection
- a l'issue de la réindexation, si certains documents n'ont pas été réindexés, Docalist Search considère qu'ils sont obsolètes et les supprime.

## Algorithme ##

Dans Elastic Search, les documents dispose d'un champ [`_timestamp`](http://www.elasticsearch.org/guide/reference/mapping/timestamp-field/) qui indique la date de dernière mise à jour du document. Ce champ est géré entièrement par Elastic Search, Docalist Search se contente de garantir que tous les documents disposent de ce champ en ajoutant la clause `"_timestamp" : { "enabled" : true }` dans les mappings.

Lorsque la réindexation d'une collection commence, Docalist Search stocke la date en cours : `start` (plus exactement, il demande à Elastic Search de retourner l'heure actuelle du serveur, ce qui permet de gérer un éventuel décalage entre la date du serveur qui héberge le site web et la date du serveur qui héberge le service Elastic Search).

Au cours de la réindexation, le plugin va demander la réindexation de tous les documents dont il dispose et Elastic Search va mettre à jour le champ `_timestamp`.

Une fois que la réindexation de la collection est terminée, Docalist Search supprime tous les documents qui n'ont pas été réindexés, c'est à dire tous les documents de la collection dont la date de dernière mise à jour est antérieure à la date/heure de début de la réindexation. Cette suppression est très efficace, puisqu'elle se contente d'adresser au serveur Elastic Search une unique requête [DeleteByQuery](http://www.elasticsearch.org/guide/reference/api/delete-by-query/) de la forme `{"range":{"_timestamp":{"lt":start}}}`.

## Divers ##

On ne peut pas réindexer une partie seulement de la collection

- si, dans ce cas, il suffit de désactiver la "purge" (un flag à passer en paramètre).

Qu'est-ce qui se passe si on a plusieurs réindexation lancées (par erreur) en même temps sur le même type ?

- en approche naive, la 2nde réindexation, quand elle commence, supprime les docs créés par la 1ere (l'index est revidé) mais sinon ça marche.
- avec notre approche : la 2nde réindexation va remettre à jour les docs déjà mis à jour par la 1ère. Les timestamp vont être incrémentés, pas de suppression, donc ça marche.

Décalage éventuel date du serveur web / date elastic search, risque de supprimer plus que ce qu'il faut ?
- évoqué plus haut dans algorithme, on ne travaille _que_ avec la date elastic search.

A l'utilisation ça donne quoi ?
- tant qu'on document n'a pas été réindexé, l'utilisateur a accès, via le moteur, à l'ancienne version du doc.
- éventuellement, le moteur va sortir dans les réponses un doc qui n'existe plus. comme ensuite on fait un get_posts wp avec les ids obtenus, de toute façon on ne le sortira pas.  

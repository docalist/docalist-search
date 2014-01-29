# Statistiques générées lors de la réindexation #

Au cours d'une réindexation, l'indexeur de Docalist Search maintient des statistiques sur le nombre de document qui ont été ajoutés, mis à jour ou supprimés du moteur de recherche. 
 
## Principe : ##
## Indexation ##

'nbindex' => 0,         // nombre de fois où la méthode index() a été appellée
'nbdelete' => 0,        // nombre de fois où la méthode delete() a été appellée
'removed' => 0,         // nombre de documents non réindexés, supprimés via deleteByQuery (purgés)


'indexed' => 0,     // Nombre de docs que ES a effectivement indexé, suite à une commande index() (= added + updated)
'deleted' => 0,    // Nombre de docs que ES a effectivement supprimé, suite à une commande delete()
'added' => 0,           // Nombre de documents indexés par ES (indexed) qui n'existaient pas encore (= indexed - updated)
'updated' => 0,        // Nombre de documents indexés par ES (indexed) qui existaient déjà (= indexed - added)

'nbflush' => 0,         // not implemented (comment connaitre le type ?)

## Taille des documents : ##

'totalsize' => 0,    // Taille totale des docs passés à la méthode index(), tels que stockés dans le buffer (tout compris)
'minsize' => 0,      // Taille du plus petit document indexé
'avgsize' => 0,      // Taille moyenne des docs passés à la méthode index(), tels que stockés dans le buffer (=totalsize / indexed)
'maxsize' => 0,      // Taille du plus grand document indexé

## Temps écoulé : ##

'start' => 0,           // Timestamp de début de la réindexation
'end' => 0,             // Timestamp de fin de la réindexation
'time' => 0,            // Durée de la réindexation (en secondes) (=end-start)
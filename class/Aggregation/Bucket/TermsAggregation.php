<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2019 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\MultiBucketsAggregation;
use stdClass;

/**
 * Une agrégation de type "buckets" qui regroupe les documents en fonction des termes trouvés dans un champ donné.
 *
 * @link
 * https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-terms-aggregation.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class TermsAggregation extends MultiBucketsAggregation
{
    /**
     * {@inheritDoc}
     */
    const TYPE = 'terms';

    /**
     * Valeur utilisée pour indiquer "non disponible" (Not Available)
     *
     * @var string
     */
    const MISSING = 'n-a';

    /*
     * Gestion de l'option "multiselect"
     * ---------------------------------
     * Par défaut, une agrégation de type "terms" est mono select : on fait une requête, on obtient toutes les
     * entrées possibles, on sélectionne une entrée (elle s'active comme filtre) et la facette n'affiche alors
     * que l'entrée sélectionnée puisque c'est la seule entrée qu'on a dans les réponses obtenues (éventuellement
     * on en a quelques autres si le champ est multivalué, mais ça ne change pas le principe).
     *
     * L'option "multiselect" permet de changer ce comportement : quand on sélectionne une entrée, elle est activée
     * comme filtre, mais toutes les autres valeurs possibles sont ré-affichées, ce qui permet à l'utilisateur de
     * choisir d'autres options.
     *
     * Remarque : l'option "multiselect" n'est pas lié à l'opérateur utilisé pour combiner les différentes entrées
     * activées. Par exemple, on peut avoir une facette "status" multiselect qui permettra de combiner les statuts
     * en "OU" alors que pour une facette "mots-clés" multiselect, on combinera en "ET".
     *
     * Pour gérer l'option "multiselect", on surcharge getDefinition() et setResult().
     *
     * Si l'option "multiselect" est activée et que la requête comporte des filtres qui portent sur le même champ
     * que celui de l'agrégation, l'agrégation de type "terms" est transformée en une hiérarchie d'agrégations
     * imbriquées de type : global -> filter -> terms.
     *
     * - L'agrégation global permet de calculer une facette qui porte sur la totalité de l'index, sans tenir compte
     *   de la requête en cours. C'est ça qui va nous permettre d'avoir toutes les entrées possibles (et pas
     *   uniquement celles sélectionnées) et donc de permettre à l'utilisateur de sélectionner d'autres valeurs.
     * - L'agrégration filter permet de réintroduire la requête en cours en supprimant de celle-ci tous les filtres
     *   qui portent sur le même champ que celui indiqué dans l'agrégation de type terms. Ca nous permet de n'avoir
     *   dans la facette que les entrées pertinentes par rapport à la recherche en cours et d'avoir des count
     *   corrects.
     * - L'agrégation terms est ajoutée, inchangée, comme agrégation enfant de l'agrégation filter obtenue.
     *
     * Pour obtenir la définition finale, on appelle simplement global->getDefinition(). Cependant, comme
     * l'agrégation "terms" a été ajoutée comme agrégation enfant, ça va rappeller notre méthode getDefinition().
     * Pour éviter une boucle infinie, on utilise un flag "recurse" qui nous permet de détecter ce cas.
     *
     * Remarque : si la requête ne comporte aucun filtre qui porte sur le champ de l'agrégation "terms", on fait
     * comme si l'option "multiselect" n'était pas activée.
     *
     * Un dernier traitement doit être réalisé quand elasticsearch nous retourne les résultats de l'agrégation.
     * Selon qu'on a modifié ou non l'agrégation, les résultats retournés seront différents (soit on a directement
     * les buckets, soit il faut aller les chercher dans la sous-agrégation filter). Pour gérer ça on donne un
     * nom particulier à l'agrégation filter ("multiselect") et setResults() a juste à tester la présence de cette
     * sous-agrégation pour savoir où aller chercher les résultats.
     */

    /**
     * Constructeur
     *
     * @param string    $field          Champ sur lequel porte l'agrégation.
     * @param array     $parameters     Autres paramètres de l'agrégation.
     * @param array     $options        Options d'affichage.
     */
    public function __construct($field, array $parameters = [], array $options = [])
    {
        $parameters['field'] = $field;
        parent::__construct($parameters, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultOptions(): array
    {
        $options = parent::getDefaultOptions();

        // Option "hierarchy" : génère ou non une liste hiérarchique
        $options['hierarchy']  = false;

        // Option "multiselect" : permet de sélectionner plusieurs valeurs
        $options['multiselect']  = false;

        return $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefinition(): array
    {
        // Un flag qui nous permet d'éviter une boucle infinie si on appelle global->getDefinition()
        static $recurse = false;

        // Récupère la définition d'origine
        $definition = parent::getDefinition();

        // Gère l'option "hierarchy"
        if ($this->options['hierarchy']) {
            $include = $this->getIncludeRegex();
            $include && $definition[$this->getType()]['include'] = $include;
        }

        // Gère l'option "multiselect"

        // Si le flag recurse est à true ou si l'option "multiselect" est à false, on ne fait rien
        if ($recurse || !$this->getOption('multiselect')) {
            return $definition;
        }

        // Détermine s'il faut filtrer l'agrégation, si ce n'est pas le cas, on ne fait rien
        if (empty($filter = $this->createFilterAggregation())) {
            return $definition;
        }

        // Construit l'agrégation "filter" et ajoute l'agrégation "terms" comme sous-agrégation
        $filter = new FilterAggregation($filter);
        $filter->setName('multiselect');
        $filter->addAggregation($this);

        // Construit l'agrégation "global" et ajoute l'agrégation "filter" comme sous-agrégation
        $global = new GlobalAggregation();
        $global->addAggregation($filter);

        // Récupère la définition de l'agrégation globale obtenue
        $recurse = true;
        $definition = $global->getDefinition();
        $recurse = false;

        // Ok
        return $definition;
    }

    /**
     * Retourne l'expression régulière à utiliser pour le paramètre 'include' de l'agrégation (option "hierarchy").
     *
     * @return string
     */
    protected function getIncludeRegex()
    {
        $field = $this->getParameter('field');
        $url = $this->getSearchRequest()->getSearchUrl();
        $parameters = $url->getParameters();

        // On veut tous les termes de niveau 1
        $include = '[^/]+';

        // Et on développe tous les termes sélectionnés
        $filters = isset($parameters[$field]) ? (array) $parameters[$field] : [];
        foreach ($filters as $filter) {
            $include .= '|' . ($filter) . '/' . '[^/]+';
        }

        return $include;
    }

    /**
     * Retourne le filtre à utiliser pour une agrégation "multiselect".
     *
     * @return array La définition du filtre ou un tableau vide s'il n'y a pas besoin de filtre.
     */
    private function createFilterAggregation(): array
    {
        // Récupère la requête en cours
        $request = $this->getSearchRequest(); /** @var SearchRequest $request */

        // Récupère les filtres qu'elle contient
        $filters = $request->getFilters();

        // Si la requête ne comporte aucun filtre qui porte sur notre champ, on ne fait rien
        if (empty($filters) || !$this->filterFilters($filters, $this->getParameter('field'))) {
            return [];
        }

        // Regénère la requête avec les filtres modifiés
        $request = clone $request;
        $request->setFilters($filters);
        $result = [];
        $request->buildQueryClause($result);

        // Ok
        return $result['query'];
    }

    /**
     * Filtre les filtres passés en paramètre en enlevant tout ceux qui portent sur le champ indiqué
     * (option "multiselect").
     *
     * @param array  $filters   ByRef, les filtres a filtrer / filtrés.
     * @param string $field     Le champ à tester.
     *
     * @return bool true si des filtres ont été enlevés, faux sinon.
     */
    private function filterFilters(array &$filters, string $field): bool
    {
        /* Chaque filtre est de la forme suivante
         * {
         *     "term|terms": {
         *         "<nom-du-champ>": definition
         *     }
         * }
         */

        // Un flag qui nous indique si on a supprimé des filtres
        $hasChanged = false;

        // Filtre tous les filtres
        foreach ($filters as $key => $filter) {
            $type = key($filter);
            if ($type === 'term' || $type === 'terms') {
                $filter = current($filter);
                if (key($filter) === $field) {
                    unset($filters[$key]);
                    $hasChanged = true;
                }
            }
        }

        // Indique si on a supprimé quelque chose
        return $hasChanged;
    }

    /**
     * {@inheritDoc}
     */
    public function setResult(stdClass $result): void
    {
        // Si on a une sous-agrégation "multiselect", il faut aller chercher les buckets dedans
        if (isset($result->multiselect)) {
            $result = $result->multiselect->{$this->getName()};
        }

        // Hiérarchise les buckets (option "hierarchy")
        if ($this->options['hierarchy']) {
            $result->buckets = $this->createBucketsHierarchy($result->buckets);
        }

        // Stocke le résultat
        parent::setResult($result);
    }

    /**
     * Hiérarchise la liste de buckets passée en paramètre.
     *
     * Chacun des buckets de la liste  doit avoir une clé de la forme "a/b/c" qui indiqué le path de ce bucket dans
     * la hiérarchie. La méthode parcourt la liste et déplace chaque bucket au bon endroit de la hiérarchie.
     *
     * Au final, on obtient une liste qui ne contient que les buckets racine et chaque bucket de cette liste contient
     * un tableau 'children' contenant les buckets enfants et ainsi de suite.
     *
     * Remarques :
     * - la méthode respecte le tri initial des buckets (on a le même tri en sortie que celui en entrée)
     * - si la liste contient un bucket pour lequel on n'a pas le bucket parent (i.e. on a 'a/b/c' mais pas 'a/b'),
     *   la méthode crée automatiquement les tags manquants avec un 'doc_count' à null.
     *
     * @param stdClass[] $buckets La liste (à plat) des buckets générés par elasticsearch.
     *
     * @return stdClass[] La liste hiérarchisée : chaque bucket contient un tableau 'children' qui liste les fils.
     */
    protected function createBucketsHierarchy(array $buckets)
    {
        // Indexe les buckets par clé
        foreach ($buckets as $i => $bucket) {
            $buckets[$bucket->key] = $bucket;
            unset($buckets[$i]);
        }

        // Déplace tous les buckets qui ne sont pas des tags racine comme enfant de leur tag parent
        foreach ($buckets as $key => $bucket) {
            // Récupère les différents segments qui composent la clé du bucket
            $parts = explode('/', (string) $key);

            // Si c'est un tag racine (un seul segment), terminé
            if (count($parts) < 2) {
                continue;
            }

            // Détermine l'endroit où ce bucket doit figurer dans la hiérarchie
            $current = & $buckets;
            $last = array_pop($parts);
            foreach ($parts as $i => $part) {
                // Si ce noeud n'existe pas encore dans la hiérarchie, on le crée
                if (!isset($current[$part])) {
                    $path = implode('/', array_slice($parts, 0, $i + 1));
                    $current[$part] = (object) ['key' => $path, 'doc_count' => null];
                    // Remarque : si plus tard on tombe sur ce noeud dans la liste, les données seront fusionnées
                }

                // Si ce noeud n'a pas encore de noeuds enfants, crée la propriété 'children'
                !isset($current[$part]->children) && $current[$part]->children = [];

                // Continue à descendre dans la hiérarchie
                $current = & $current[$part]->children;
            }

            // A ce stade, $current pointe sur le tableau 'children' dans lequel on doit insérer le bucket

            // Si on a déjà un bucket avec ce nom, c'est un bucket vide créé précédemment, récupère 'children'
            isset($current[$last]) && $bucket->children = $current[$last]->children;

            // Ajoute le bucket dans la liste des fils
            $current[$last] = $bucket;

            // Supprime le bucket de la liste des buckets
            unset($buckets[$key]);
        }

        // Retourne la liste hiérarchisée
        return $buckets;
    }

    /**
     * {@inheritDoc}
     */
    public function getBucketLabel(stdClass $bucket)
    {
        if ($bucket->key === static::MISSING) {
            return $this->getMissingLabel();
        }

        return $bucket->key;
    }

    /**
     * {@inheritDoc}
     */
    protected function renderBucketLabel(stdClass $bucket)
    {
        /**
         * Si l'option 'hierarchy' est à true, on ne tient compte que de la dernière partie du path du bucket.
         */
        if ($this->options['hierarchy']) {
            $bucket = clone $bucket;
            if (false !== $pos = strrpos($bucket->key, '/')) {
                $bucket->key = substr($bucket->key, $pos + 1);
            }
        }

        return parent::renderBucketLabel($bucket);
    }

    /**
     * Retourne le libellé à utilisé pour le bucket 'missing'.
     *
     * @return string
     */
    protected function getMissingLabel()
    {
        return __('Non disponible', 'docalist-search');
    }
}

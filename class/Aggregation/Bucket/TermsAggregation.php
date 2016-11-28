<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation\Bucket;

use Docalist\Search\Aggregation\MultiBucketsAggregation;
use stdClass;

/**
 * Une agrégation de type "buckets" qui regroupe les documents en fonction des termes trouvés dans un champ donné.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket-terms-aggregation.html
 */
class TermsAggregation extends MultiBucketsAggregation
{
    const TYPE = 'terms';

    /**
     * Valeur utilisée pour indiquer "non disponible" (Not Available)
     *
     * @var string
     */
    const MISSING = 'n-a';

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

    public function getDefaultOptions()
    {
        $options = parent::getDefaultOptions();

        $options['hierarchy']  = false;             // Génère ou non une liste hiérarchique.
        $options['hierarchy.sep']  = '/';           // Séparateur utilisé pour le champ hierarchy.

        return $options;
    }

    public function getDefinition()
    {
        if (! $this->options['hierarchy']) {
            return parent::getDefinition();
        }

        $parameters = $this->getParameters();
        $parameters['include'] = $this->getIncludeRegex();

        return [$this->getType() => $parameters];
    }

    /**
     * Retourne l'expression régulière à utiliser pour le paramètre 'include' de l'agrégation.
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
        foreach($filters as $filter) {
            $include .= '|' . ($filter) . '/' . '[^/]+';
        }

        return $include;
    }

    public function setResult(stdClass $result)
    {
        // Hiérarchise les buckets
        if ($this->options['hierarchy']) {
            $result->buckets = $this->createBucketsHierarchy($result->buckets);
        }

        // Stocke le résultat
        return parent::setResult($result);
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
     * @return stdClass[] La liste hiérarchisée : chaque bucket contient un tableua 'children' qui liste les fils.
     */
    protected function createBucketsHierarchy(array $buckets)
    {
        // Active ou non de débuggage de la fonction
        $debug = false;

        // Debug : affiche la liste initiale
        if ($debug) {
            echo '<h1>', $this->getName(), ' : liste initiale des buckets</h1>';
            echo '<pre>', json_encode($buckets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), '</pre>';
        }

        // Indexe les buckets par clé
        foreach($buckets as $i => $bucket) {
            $buckets[$bucket->key] = $bucket;
            unset($buckets[$i]);
        }

        // Déplace tous les buckets qui ne sont pas des tags racine comme enfant de leur tag parent
        foreach($buckets as $key => $bucket) {

            // Récupère les différents segments qui composent la clé du bucket
            $parts = explode('/', $key);

            // Si c'est un tag racine (un seul segment), terminé
            if (count($parts) < 2) {
                continue;
            }

            // Détermine l'endroit où ce bucket doit figurer dans la hiérarchie
            $current = & $buckets;
            $last = array_pop($parts);
            foreach($parts as $i => $part) {

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

        // Debug : affiche la liste obtenue
        if ($debug) {
            echo '<h1>', $this->getName(), ' : liste hiérarchisée</h1>';
            echo '<pre>', json_encode($buckets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), '</pre>';
        }

        // Retourne la liste hiérarchisée
        return $buckets;
    }

    public function getBucketLabel(stdClass $bucket)
    {
        if ($bucket->key === static::MISSING) {
            return $this->getMissingLabel();
        }

        return  $bucket->key;
    }

    protected function renderBucketLabel(stdClass $bucket)
    {
        /**
         * Si l'option 'hierarchy' est à true, on ne tient compte que de la dernière partie du path du bucket.
         */
        if ($this->options['hierarchy']) {
            $bucket = clone $bucket;
            $sep = $this->options['hierarchy.sep'];
            if (false !== $pos = strrpos($bucket->key, $sep)) {
                $bucket->key = substr($bucket->key, $pos + strlen($sep));
            }
        }

        return parent::renderBucketLabel($bucket);
    }

    /**
     * Retourne le libellé à utilisé pour le bucket 'missing'.
     *
     * @return string
     */
    protected function getMissingLabel() {
        return __('Non disponible', 'docalist-search');
    }
}

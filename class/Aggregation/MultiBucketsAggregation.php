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
namespace Docalist\Search\Aggregation;

use stdClass;

/**
 * Classe de base pour les agrégations de type "bucket" qui retournent plusieurs buckets.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket.html
 */
abstract class MultiBucketsAggregation extends BucketAggregation
{
    public function getDefaultOptions()
    {
        $options = parent::getDefaultOptions();

        $options['container.css']  = 'facet';
        $options['content.tag']  = 'ul';

        $options['bucket.tag']  = 'li';             // Tag à utiliser pour les buckets
        $options['bucket.css']  = false;            // Génère ou non un attribut "class" pour chaque bucket
        $options['bucket.label.tag']  = 'span';     // Tag à utiliser pour le libellé des buckets  (vide : pas de tag)
        $options['bucket.count.tag']  = 'em';       // Tag à utiliser pour le count des buckets (vide : pas de count)

        return $options;
    }

    protected function renderResult()
    {
        return $this->renderBuckets($this->getBuckets());
    }

    /**
     * Génère le rendu des buckets passés en paramètre.
     *
     * @param stdClass[] $buckets
     *
     * @return string
     */
    protected function renderBuckets(array $buckets)
    {
        $result = '';
        foreach ($buckets as $bucket) {
            $bucket = $this->prepareBucket($bucket);
            $bucket && $result .= $this->renderBucket($bucket);
        }

        return $result;
    }

    /**
     * Génère le rendu du bucket passé en paramètre.
     *
     * @param stdClass $bucket
     *
     * @return string
     */
    protected function renderBucket(stdClass $bucket)
    {
        // Génère le libellé du bucket
        $result = $this->renderBucketLabel($bucket);

        // Génère le nombre de documents obtenus pour ce bucket
        $result .= $this->renderBucketCount($bucket);

        // Génère le lien permettant d'activer ou de désativer ce bucket
        $result = $this->renderBucketLink($bucket, $result);

        // Génère les sous-agrégations de ce bucket
        foreach($this->getAggregations() as $aggregation) {
            $result .= $aggregation->render(['container' => false, 'title' => false]);
        }

        // Génère les buckets enfants de ce bucket (s'il s'agit d'une hiérarchie)
        if (!empty($bucket->children) && $children = $this->renderBuckets($bucket->children)) {
            $tag = $this->options['content.tag'];
            $result .= $this->renderTag($tag, [], $children);
        }

        // Génère les attributs du bucket
        $field = $this->getParameter('field');
        $filter = $this->getBucketFilter($bucket);
        $searchUrl = $this->getSearchRequest()->getSearchUrl();

        $attributes = [];
        $class = $this->options['bucket.css'] ? $this->getBucketClass($bucket) : '';
        $searchUrl->hasFilter($field, $filter) && $class = ltrim($class . ' filter-active');
        $class && $attributes['class'] = $class;
        if ($this->options['data']) {
            $attributes['data-bucket'] = $bucket->key;
            $attributes['data-count'] = $bucket->doc_count;
        }

        // Génère le bucket
        return $this->renderTag($this->options['bucket.tag'], $attributes, $result);
    }

    /**
     * Génère le libellé du bucket passé en paramètre.
     *
     * @param stdClass $bucket
     *
     * @return string
     */
    protected function renderBucketLabel(stdClass $bucket)
    {
        $tag = $this->options['bucket.label.tag'];
        $label = $this->getBucketLabel($bucket);

        return $tag ? $this->renderTag($tag, [], $label) : $label;
    }

    /**
     * Génère le nombre de documents obtenus pour le bucket passé en paramètre.
     *
     * @param stdClass $bucket
     *
     * @return string
     */
    protected function renderBucketCount(stdClass $bucket)
    {
        $tag = $this->options['bucket.count.tag'];

        return $tag ? $this->renderTag($tag, [], $bucket->doc_count) : '';
    }

    /**
     * Génère le lien du bucket passé en paramètre.
     *
     * @param stdClass  $bucket
     * @param string    $content Contenu du lien.
     *
     * @return string
     */
    protected function renderBucketLink(stdClass $bucket, $content)
    {
        // Génère le lien permettant d'activer ou de désativer ce bucket
        $field = $this->getParameter('field');
        $filter = $this->getBucketFilter($bucket);
        $searchUrl = $this->getSearchRequest()->getSearchUrl();
        $url = $searchUrl->toggleFilter($field, $filter);

        return $this->renderTag('a', ['href' => $url], $content);
    }

    /**
     * Retourne la valeur du filtre qui sera généré pour le bucket passé en paramètre.
     *
     * @param stdClass $bucket
     *
     * @return string
     */
    protected function getBucketFilter(stdClass $bucket)
    {
        return $bucket->key;
    }

    /**
     * Retourne les classes css à générer pour le bucket passé en paramètre.
     *
     * @param stdClass $bucket
     *
     * @return string
     */
    protected function getBucketClass(stdClass $bucket)
    {
        return $bucket->key;
    }
}

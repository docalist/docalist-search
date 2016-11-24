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
    public function getDefaultRenderOptions()
    {
        $options = parent::getDefaultRenderOptions();

        $options['container.css']  = 'facet';
        $options['content.tag']  = 'ul';

        $options['bucket.tag']  = 'li';             // Tag à utiliser pour les bucket
        $options['bucket.label.tag']  = 'span';     // Tag à utiliser pour le libellé des buckets
        $options['bucket.count.tag']  = 'em';       // Tag à utiliser pour le count des buckets ou false

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
        $tag = $this->renderOptions['bucket.label.tag'];
        $result = $this->renderTag($tag, [], $this->getBucketLabel($bucket));

        // Génère le nombre de documents obtenus pour ce bucket
        $tag = $this->renderOptions['bucket.count.tag'];
        $tag && $result .= $this->renderTag($tag, [], $bucket->doc_count);

        // Génère le lien permettant d'activer ou de désativer ce bucket
        $field = $this->getParameter('field');
        $searchUrl = $this->getSearchRequest()->getSearchUrl();
        $url = $searchUrl->toggleFilter($field, $bucket->key);
        $result = $this->renderTag('a', ['href' => $url], $result);

        // Génère les sous-agrégations de ce bucket
        foreach($this->getAggregations() as $aggregation) {
            $result .= $aggregation->render(['container' => false, 'title' => false]);
        }

        // Génère les buckets enfants de ce bucket (s'il s'agit d'une hiérarchie)
        if (!empty($bucket->children)) {
            if ($children = $this->renderBuckets($bucket->children)) {
                $tag = $this->renderOptions['content.tag'];
                $result .= $this->renderTag($tag, [], $children);
            }
        }

        // Génère les attributs du bucket
        $attributes = ['class' => $bucket->key];
        $searchUrl->hasFilter($field, $bucket->key) && $attributes['class'] .= ' filter-active';
        if ($this->renderOptions['data']) {
            $attributes['data-bucket'] = $bucket->key;
            $attributes['data-count'] = $bucket->doc_count;
        }

        // Génère le bucket
        return $this->renderTag($this->renderOptions['bucket.tag'], $attributes, $result);
    }
}

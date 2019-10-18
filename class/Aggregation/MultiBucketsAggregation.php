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

namespace Docalist\Search\Aggregation;

use stdClass;

/**
 * Classe de base pour les agrégations de type "bucket" qui retournent plusieurs buckets.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/search-aggregations-bucket.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class MultiBucketsAggregation extends BucketAggregation
{
    /**
     * Pendant l'affichage des buckets, niveau auquel on est dans la hiérarchie (0-based).
     *
     * La propriété est incrémentée à chaque fois qu'on entre dans renderBuckets() et elle est décrémentée
     * à chaque fois qu'on en sort.
     *
     * @var int
     */
    protected $bucketsLevel = -1;

    /**
     * {@inheritDoc}
     */
    public function getDefaultOptions(): array
    {
        $options = parent::getDefaultOptions();

        $options['collapsible']  = false;           // Par défaut, les facettes ne sont pas repliables
        $options['collapsed']  = true;              // Mais si elles sont repliables, elles sont repliées par défaut

        $options['container.css']  = 'facet';

        $options['content.tag']  = 'ul';

        $options['bucket.tag']  = 'li';             // Tag à utiliser pour les buckets
        $options['bucket.css']  = false;            // Génère ou non un attribut "class" pour chaque bucket
        $options['bucket.label.tag']  = 'span';     // Tag à utiliser pour le libellé des buckets  (vide : pas de tag)
        $options['bucket.label.css']  = '';         // Css pour le libellé
        $options['bucket.count.tag']  = 'em';       // Tag à utiliser pour le count des buckets (vide : pas de count)
        $options['bucket.count.css']  = '';         // Css pour le count

        return $options;
    }

    protected function renderResult()
    {
        return $this->renderBuckets($this->getBuckets());
    }

    /**
     * Ajoute une classe dans l'attribut passé en paramètre.
     *
     * La méthode évite d'avoir à tester si l'attribut dans lequel on veut ajouter une classe existe déjà ou pas
     * (on le passe par référence).
     *
     * Exemples :
     *
     * $attributes = ['class' => 'large'];
     * addclass($attributes['class'], 'visible'); // -> result : 'large visible'
     *
     * $attributes = [];
     * addclass($attributes['class'], 'visible'); // -> result : 'visible', pas de warning
     *
     * $attributes = [class => '   '];
     * addclass($attributes['class'], '  '); // -> result : '', trim appliqué
     *
     * @param string $destination
     * @param string $class
     */
    private function addclass(?string & $destination, string $class): void
    {
        $destination = ltrim($destination . ' ' . $class);
    }

    protected function getContainerAttributes(): array
    {
        $attributes = parent::getContainerAttributes();
        $this->options['collapsible'] && $this->addclass($attributes['class'], 'collapsible');

        return $attributes;
    }

    protected function getContentAttributes()
    {
        $attributes = parent::getContentAttributes();
        $this->options['collapsible'] && $this->addclass($attributes['class'], 'collapsible-content');

        return $attributes;
    }

    final protected function renderTitle(): string
    {
        // Si la facette n'est pas repliable ou qu'on n'a pas de titre, rien à faire
        if (!$this->options['collapsible'] || empty($this->options['title'])) {
            return parent::renderTitle();
        }

        // Détermine l'ID de la checkbox et de l'attribut "for" du label
        $id = $this->getName();

        // Génère une checkbox
        $checkbox = $this->renderTag('input', [
            'type' => "checkbox",
            'hidden' => 'hidden',
            'class' => 'collapse',
            'id' => $id,
            'checked' => $this->options['collapsed'] && !$this->isActive()
        ]);

        // Génère un tag <label> contenant le titre
        $label = $this->renderTag('label', ['for' => $id], $this->options['title']);

        // Génère le titre
        $tag = $this->options['title.tag'];
        $class = $this->options['title.css'];
        $title = $this->renderTag($tag, $class ? ['class' => $class] : [], $label);

        // Retourne la checkbox + le titre modifié
        return $checkbox . $title;
    }

    /**
     * Génère le rendu des buckets passés en paramètre.
     *
     * @param stdClass[]    $buckets    Les buckets à afficher.
     *
     * @return string
     */
    protected function renderBuckets(array $buckets)
    {
        ++$this->bucketsLevel;
        $result = '';
        foreach ($buckets as $bucket) {
            $bucket = $this->prepareBucket($bucket);
            $bucket && $result .= $this->renderBucket($bucket);
        }
        --$this->bucketsLevel;

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
        foreach ($this->getAggregations() as $aggregation) {
            $result .= $aggregation->render(['container' => false, 'title' => false]);
        }

        // Génère les buckets enfants de ce bucket (s'il s'agit d'une hiérarchie)
        if (!empty($bucket->children) && $children = $this->renderBuckets($bucket->children)) {
            $tag = $this->options['content.tag'];
            $class = $this->options['content.css'];
            $result .= $this->renderTag($tag, $class ? ['class' => $class] : [], $children);
        }

        // Génère les attributs du bucket
        $field = $this->getParameter('field');
        $filter = $this->getBucketFilter($bucket);
        $searchUrl = $this->getSearchRequest()->getSearchUrl();

        $attributes = [];
        $class = $this->options['bucket.css'];// ? $this->getBucketClass($bucket) : '';
        ($class === false) && $class = '';
        ($class === true) && $class = $this->getBucketClass($bucket);
        $searchUrl && $searchUrl->hasFilter($field, $filter) && $class = ltrim($class . ' filter-active');
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
        $css = $this->options['bucket.label.css'];
        $label = $this->getBucketLabel($bucket);

        return $tag ? $this->renderTag($tag, $css ? ['class' => $css] : [], $label) : $label;
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
        $css = $this->options['bucket.count.css'];

        return $tag ? $this->renderTag($tag, $css ? ['class' => $css] : [], (string) $bucket->doc_count) : '';
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
        if ($searchUrl) {
            $url = $searchUrl->toggleFilter($field, $filter);

            return $this->renderTag('a', ['href' => $url], $content);
        }
        return $this->renderTag('span', [], $content);
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

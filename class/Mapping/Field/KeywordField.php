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

namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Field\Parameter\IgnoreAbove;
use Docalist\Search\Mapping\Field\Parameter\IgnoreAboveTrait;
use Docalist\Search\Mapping\Field\Parameter\Normalizer;
use Docalist\Search\Mapping\Field\Parameter\NormalizerTrait;
use Docalist\Search\Mapping\Field\Parameter\Similarity;
use Docalist\Search\Mapping\Field\Parameter\SimilarityTrait;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;

/**
 * Un champ texte dont le contenu est stockée tel quel dans l'index de recherche.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/keyword.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class KeywordField extends Field implements IgnoreAbove, Normalizer, Similarity
{
    use IgnoreAboveTrait, NormalizerTrait, SimilarityTrait;

    /**
     * {@inheritDoc}
     */
    final public function __construct(string $name)
    {
        parent::__construct($name);
        $this->setSimilarity(self::BOOLEAN_SIMILARITY);
    }

    /**
     * {@inheritDoc}
     */
    final public function getSupportedFeatures(): array
    {
        return [self::FILTER, self::EXCLUSIVE, self::AGGREGATE, self::SORT, self::LOOKUP];
    }

    /**
     * {@inheritDoc}
     */
    final public function mergeWith(Field $other): void
    {
        try {
            parent::mergeWith($other);

            $this->mergeIgnoreAbove($other);
            $this->mergeNormalizer($other);
            $this->mergeSimilarity($other);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($other->getName() . ': ' . $e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     */
    final public function getMapping(Options $options): array
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Type de champ
        $mapping['type'] = 'keyword';

        // Si le champ n'est pas utilisé en recherche, inutile d'indexer les valeurs
        if (!$this->hasFeature(self::FILTER)) {
            $mapping['index'] = false;
        }

        // Si le champ n'est pas utilisé pour le tri ou les agrégations, inutile de générer les doc_values
        if (!$this->hasFeature(self::SORT) && !$this->hasFeature(self::AGGREGATE)) {
            $mapping['doc_values'] = false;
        }

        // Applique les autres paramètres
        $this->applyIgnoreAbove($mapping);
        $this->applyNormalizer($mapping);
        $this->applySimilarity($mapping);

        // Ok
        return $mapping;
    }
}

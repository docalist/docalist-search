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
use Docalist\Search\Mapping\Options;
use Docalist\Search\Analysis\Analyzer\Hierarchy;
use Docalist\Search\Mapping\Field\Parameter\Analyzer;
use Docalist\Search\Mapping\Field\Parameter\AnalyzerTrait;
use Docalist\Search\Mapping\Field\Parameter\SearchAnalyzer;
use Docalist\Search\Mapping\Field\Parameter\SearchAnalyzerTrait;
use Docalist\Search\Mapping\Field\Parameter\FieldData;
use Docalist\Search\Mapping\Field\Parameter\FieldDataTrait;
use Docalist\Search\Mapping\Field\Parameter\Similarity;
use Docalist\Search\Mapping\Field\Parameter\SimilarityTrait;
use InvalidArgumentException;

/**
 * Un champ special permettant de filtrer et d'agréger sur un path.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class HierarchyField extends Field implements Analyzer, FieldData, SearchAnalyzer, Similarity
{
    use AnalyzerTrait, FieldDataTrait, SearchAnalyzerTrait, SimilarityTrait;

    /*
     * Ressources :
     * https://github.com/elastic/elasticsearch/issues/8896
     * https://github.com/opendatasoft/elasticsearch-aggregation-pathhierarchy
     * https://docs.searchkit.co/stable/components/navigation/hierarchical-refinement-filter.html
     * https://shoppinpal.gitbook.io/docs-shoppinpal-com/6.-elasticsearch/fun-with-path-hierarchy-tokenizer
     */

    /**
     * {@inheritDoc}
     */
    final public function __construct(string $name)
    {
        parent::__construct($name);
        $this->setAnalyzer(Hierarchy::getName());
        $this->setSearchAnalyzer('keyword');
        $this->enableFieldData();
        $this->setSimilarity(self::BOOLEAN_SIMILARITY);
    }

    /**
     * {@inheritDoc}
     */
    final public function getSupportedFeatures(): int
    {
        return self::FILTER | self::EXCLUSIVE | self::AGGREGATE;
    }

    /**
     * {@inheritDoc}
     */
    final public function mergeWith(Field $other): void
    {
        try {
            parent::mergeWith($other);

            $this->mergeAnalyzer($other);
            $this->mergeFieldData($other);
            $this->mergeSearchAnalyzer($other);
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
        $mapping['type'] = 'text';

        // Applique les autres paramètres
        $this->applyAnalyzer($mapping, $options);
        $this->applyFieldData($mapping);
        $this->applySearchAnalyzer($mapping, $options);
        $this->applySimilarity($mapping);

        // Ok
        return $mapping;
    }
}

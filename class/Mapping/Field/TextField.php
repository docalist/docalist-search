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
use Docalist\Search\Mapping\Field\Parameter\Analyzer;
use Docalist\Search\Mapping\Field\Parameter\AnalyzerTrait;
use Docalist\Search\Mapping\Field\Parameter\FieldData;
use Docalist\Search\Mapping\Field\Parameter\FieldDataTrait;
use Docalist\Search\Mapping\Field\Parameter\IndexOptions;
use Docalist\Search\Mapping\Field\Parameter\IndexOptionsTrait;
use Docalist\Search\Mapping\Field\Parameter\SearchAnalyzer;
use Docalist\Search\Mapping\Field\Parameter\SearchAnalyzerTrait;
use Docalist\Search\Mapping\Field\Parameter\Similarity;
use Docalist\Search\Mapping\Field\Parameter\SimilarityTrait;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;

/**
 * Un champ texte qui utilise un analyseur pour découper le contenu en termes de recherche.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/text.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class TextField extends Field implements Analyzer, FieldData, IndexOptions, SearchAnalyzer, Similarity
{
    use AnalyzerTrait, FieldDataTrait, IndexOptionsTrait, SearchAnalyzerTrait, SimilarityTrait;

    // title search
    // https://opensourceconnections.com/blog/2014/12/08/title-search-when-relevancy-is-only-skin-deep/

    /**
     * {@inheritDoc}
     */
    final public function getSupportedFeatures(): array
    {
        return [
            self::FULLTEXT,
            self::AGGREGATE,    // Requiert fielddata, exception sinon
        ];
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
            $this->mergeIndexOptions($other);
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

        // Si le champ n'est pas utilisé en recherche, inutile d'indexer les mots
        if (!$this->hasFeature(self::FULLTEXT)) {
            $mapping['index'] = false;
        }

        // Pour faire une agrégation sur un champ "text", il faut que les fielddata soient activés
        if ($this->hasFeature(self::AGGREGATE) && !$this->hasFieldData()) {
            throw new InvalidArgumentException('Aggregating on a text field requires fielddata');
        }

        // Applique les autres paramètres
        $this->applyAnalyzer($mapping, $options);
        $this->applyFieldData($mapping);
        $this->applyIndexOptions($mapping);
        $this->applySearchAnalyzer($mapping, $options);
        $this->applySimilarity($mapping);

        // Ok
        return $mapping;
    }
}

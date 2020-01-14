<?php
/**
 * This file is part of Docalist Search.
 *
 * Copyright (C) 2012-2020 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Docalist\Search\Mapping\Field;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Options;
use Docalist\Search\Mapping\Field\Parameter\Analyzer;
use Docalist\Search\Mapping\Field\Parameter\SearchAnalyzer;
use Docalist\Search\Mapping\Field\Parameter\AnalyzerTrait;
use Docalist\Search\Mapping\Field\Parameter\SearchAnalyzerTrait;
use InvalidArgumentException;

/**
 * Un type particuler de champ texte qui permet de faire de l'autocomplete.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters-completion.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class CompletionField extends Field implements Analyzer, SearchAnalyzer
{
    use AnalyzerTrait, SearchAnalyzerTrait;

    /**
     * {@inheritDoc}
     */
    final public function getSupportedFeatures(): int
    {
        return self::LOOKUP;
    }

    /**
     * {@inheritDoc}
     */
    final public function mergeWith(Field $other): void
    {
        try {
            parent::mergeWith($other);

            $this->mergeAnalyzer($other);
            $this->mergeSearchAnalyzer($other);
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
        $mapping['type'] = 'completion';

        // Applique les autres paramètres
        $this->applyAnalyzer($mapping, $options);
        $this->applySearchAnalyzer($mapping, $options);

        // Ok
        return $mapping;
    }
}

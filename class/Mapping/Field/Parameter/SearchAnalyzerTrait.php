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

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Field\Parameter\SearchAnalyzer;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;

/**
 * Implémentation de l'interface SearchAnalyzer.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait SearchAnalyzerTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var string
     */
    private $searchAnalyzer = '';

    /**
     * {@inheritDoc}
     */
    final public function setSearchAnalyzer(string $searchAnalyzer): self
    {
        $this->searchAnalyzer = $searchAnalyzer;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getSearchAnalyzer(): string
    {
        return $this->searchAnalyzer;
    }

    /**
     * Fusionne avec un autre SearchAnalyzer.
     *
     * @param Analyzer $other
     */
    final protected function mergeSearchAnalyzer(SearchAnalyzer $other): void
    {
        if ($other->getSearchAnalyzer() !== $this->searchAnalyzer) {
            throw new InvalidArgumentException(sprintf(
                'search_analyzer mismatch (%s vs %s)',
                var_export($other->getSearchAnalyzer(), true),
                var_export($this->searchAnalyzer, true)
            ));
        }
    }

    /**
     * Applique le paramètre au mapping.
     *
     * @param array     $mapping    Mapping à modifier.
     * @param Options   $options    Options du mapping.
     */
    final protected function applySearchAnalyzer(array & $mapping, Options $options): void
    {
        switch ($this->searchAnalyzer) {
            case '':
                return;

            case Options::DEFAULT_ANALYZER:
                $analyzer = $options->getDefaultAnalyzer();
                break;

            case Options::LITERAL_ANALYZER:
                $analyzer = $options->getLiteralAnalyzer();
                break;

            default:
                $analyzer = $this->searchAnalyzer;
        }

        $mapping['search_analyzer'] = $analyzer;
    }
}

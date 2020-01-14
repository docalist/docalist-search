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

namespace Docalist\Search\Analysis\Analyzer;

use Docalist\Search\Analysis\Analyzer;

/**
 * Classe de base des Analyzer.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class CustomAnalyzer implements Analyzer
{
    /**
     * {@inheritDoc}
     */
    final public function getType(): string
    {
        return 'custom';
    }

    /**
     * {@inheritDoc}
     */
    public function getCharFilters(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenizer(): string
    {
        return 'standard';
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenFilters(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    final public function getDefinition(): array
    {
        $definition = ['type' => $this->getType()];

        $charFilters = $this->getCharFilters();
        !empty($charFilters) && $definition['char_filter'] = $charFilters;

        $definition['tokenizer'] = $this->getTokenizer();

        $tokenFilters = $this->getTokenFilters();
        !empty($tokenFilters) && $definition['filter'] = $tokenFilters;

        return $definition;
    }
}

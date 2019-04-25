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

use Docalist\Search\Mapping\Field\Parameter\Analyzer;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;

/**
 * Implémentation de l'interface Analyzer.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait AnalyzerTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var string
     */
    private $analyzer = Options::DEFAULT_ANALYZER;

    /**
     * {@inheritDoc}
     */
    final public function setAnalyzer(string $analyzer): self
    {
        if (empty($analyzer)) {
            throw new InvalidArgumentException('Invalid analyzer (empty)');
        }

        $this->analyzer = $analyzer;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getAnalyzer(): string
    {
        return $this->analyzer;
    }

    /**
     * Fusionne avec un autre Analyzer.
     *
     * @param Analyzer $other
     */
    final protected function mergeAnalyzer(Analyzer $other): void
    {
        if ($other->getAnalyzer() !== $this->analyzer) {
            throw new InvalidArgumentException(sprintf(
                'analyzer mismatch (%s vs %s)',
                var_export($other->getAnalyzer(), true),
                var_export($this->analyzer, true)
            ));
        }
    }

    /**
     * Applique le paramètre au mapping.
     *
     * @param array     $mapping    Mapping à modifier.
     * @param Options   $options    Options du mapping.
     */
    final protected function applyAnalyzer(array & $mapping, Options $options): void
    {
        switch ($this->analyzer) {
            case Options::DEFAULT_ANALYZER:
                $analyzer = $options->getDefaultAnalyzer();
                break;

            case Options::LITERAL_ANALYZER:
                $analyzer = $options->getLiteralAnalyzer();
                break;

            default:
                $analyzer = $this->analyzer;
        }

        $mapping['analyzer'] = $analyzer;
    }
}

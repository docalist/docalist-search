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

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Field\Parameter\IndexOptions;
use InvalidArgumentException;

/**
 * Implémentation de l'interface IndexOptions.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait IndexOptionsTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var string
     */
    private $indexOptions = '';

    /**
     * {@inheritDoc}
     */
    final public function setIndexOptions(string $indexOptions): self
    {
        switch ($indexOptions) {
            case IndexOptions::INDEX_DOCS:
            case IndexOptions::INDEX_DOCS_AND_FREQS:
            case IndexOptions::INDEX_DOCS_FREQS_AND_POSITIONS:
            case IndexOptions::INDEX_DOCS_FREQS_POSITIONS_AND_OFFSETS:
                $this->indexOptions = $indexOptions;

                return $this;
        }

        throw new InvalidArgumentException('Invalid index options: "' . $indexOptions . '"');
    }

    /**
     * {@inheritDoc}
     */
    final public function getIndexOptions(): string
    {
        return $this->indexOptions;
    }

    /**
     * Fusionne avec un autre IndexOptions.
     *
     * @param IndexOptions $other
     */
    final protected function mergeIndexOptions(IndexOptions $other): void
    {
        $order = [
            '' => 0,
            IndexOptions::INDEX_DOCS => 1,
            IndexOptions::INDEX_DOCS_AND_FREQS => 2,
            IndexOptions::INDEX_DOCS_FREQS_AND_POSITIONS => 3,
            IndexOptions::INDEX_DOCS_FREQS_POSITIONS_AND_OFFSETS => 4,
        ];

        $other = $other->getIndexOptions();
        if ($order[$other] > $order[$this->indexOptions]) {
            $this->indexOptions = $other;
        }
    }

    /**
     * Applique le paramètre au mapping.
     *
     * @param array $mapping Mapping à modifier.
     */
    final protected function applyIndexOptions(array & $mapping): void
    {
        !empty($this->indexOptions) && $mapping['index_options'] = $this->indexOptions;
    }
}

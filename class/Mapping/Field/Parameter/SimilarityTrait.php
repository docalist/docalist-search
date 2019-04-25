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

use Docalist\Search\Mapping\Field\Parameter\Similarity;
use InvalidArgumentException;

/**
 * Implémentation de l'interface Similarity.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait SimilarityTrait
{
    /**
     * Valeur du paramètre.
     *
     * @var string
     */
    private $similarity = Similarity::DEFAULT_SIMILARITY;

    /**
     * {@inheritDoc}
     */
    final public function setSimilarity(string $similarity): self
    {
        $this->similarity = $similarity;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    final public function getSimilarity(): string
    {
        return $this->similarity;
    }

    /**
     * Fusionne avec un autre Similarity.
     *
     * @param Similarity $other
     */
    final protected function mergeSimilarity(Similarity $other): void
    {
        if ($other->getSimilarity() !== $this->similarity) {
            throw new InvalidArgumentException(sprintf(
                'similarity mismatch (%s vs %s)',
                var_export($other->getSimilarity(), true),
                var_export($this->similarity, true)
            ));
        }
    }

    /**
     * Applique le paramètre au mapping.
     *
     * @param array $mapping Mapping à modifier.
     */
    final protected function applySimilarity(array & $mapping): void
    {
        !empty($this->similarity) && $mapping['similarity'] = $this->similarity;
    }
}

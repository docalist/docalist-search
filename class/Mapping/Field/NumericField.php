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

/**
 * Classe de base pour les champs de type numérique.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/number.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
abstract class NumericField extends Field
{
    /**
     * Retourne le type du champ (integer, long, float...)
     *
     * @return string
     */
    abstract protected function getNumericType(): string;

    /**
     * {@inheritDoc}
     */
    public function getMapping(Options $options): array // pas "final", surchargée dans ScaledFloatField
    {
        // Génère le mapping de base
        $mapping = parent::getMapping($options);

        // Type de champ
        $mapping['type'] = $this->getNumericType();
        $mapping['ignore_malformed'] = true;

        // Ok
        return $mapping;
    }
}

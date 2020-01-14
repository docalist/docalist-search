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

namespace Docalist\Search\Mapping;

use Docalist\Search\Mapping\Field\Parameter\Name;
use Docalist\Search\Mapping\Field\Parameter\NameTrait;
use Docalist\Search\Mapping\Field\Parameter\CopyTo;
use Docalist\Search\Mapping\Field\Parameter\CopyToTrait;
use Docalist\Search\Mapping\Field\Info\Label;
use Docalist\Search\Mapping\Field\Info\LabelTrait;
use Docalist\Search\Mapping\Field\Info\Description;
use Docalist\Search\Mapping\Field\Info\DescriptionTrait;
use Docalist\Search\Mapping\Field\Info\Features;
use Docalist\Search\Mapping\Field\Info\FeaturesTrait;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;

/**
 * Classe de base pour un champ de mapping docalist-search.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Field implements Name, Label, Description, Features, CopyTo
{
    use NameTrait, LabelTrait, DescriptionTrait, FeaturesTrait, CopyToTrait;

    /**
     * Initialise le champ.
     *
     * @param string $name Nom du champ.
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * Essaie de fusionner le champ en cours avec le champ passé en paramètre.
     *
     * @param Field $other Champ à fusionner.
     *
     * @throws InvalidArgumentException Si le champ n'est pas compatible.
     */
    public function mergeWith(Field $other): void
    {
        if (get_class($other) !== get_class($this)) {
            throw new InvalidArgumentException(sprintf(
                'type mismatch (%s vs %s)',
                get_class($other),
                get_class($this)
            ));
        }

        $this->mergeName($other);
        $this->mergeLabel($other);
        $this->mergeDescription($other);
        $this->mergeFeatures($other);
        $this->mergeCopyTo($other);
    }

    /**
     * Génère le mapping ElasticSearch.
     *
     * @param Options $options Options du mapping.
     *
     * @return array
     */
    public function getMapping(Options $options): array
    {
        $mapping = [];

        $this->applyCopyTo($mapping);

        return $mapping;
    }
}

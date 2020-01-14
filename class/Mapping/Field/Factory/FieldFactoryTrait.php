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

namespace Docalist\Search\Mapping\Field\Factory;

use Docalist\Search\Mapping\Field\Factory\BaseFactoryTrait;
use Docalist\Search\Mapping\Field\TextField;
use Docalist\Search\Mapping\Field\CompletionField;
use Docalist\Search\Mapping\Field\HierarchyField;
use Docalist\Search\Mapping\Field\Parameter\Analyzer;
use Docalist\Search\Mapping\Options;
use Docalist\Search\Analysis\Analyzer\Suggest;
use Docalist\Search\Analysis\Analyzer\Url;
use Docalist\Search\Mapping\Field\Info\Features;
use Docalist\Search\Mapping\Field\Parameter\Similarity;

/**
 * Ce trait enrichit la factory de base et ajoute plusieurs méthodes utilitaires qui simplifient la création
 * de champs de mapping.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
trait FieldFactoryTrait
{
    use BaseFactoryTrait;

    /**
     * Ajoute un champ de type 'text' paramétré avec l'analyseur litéral par défaut.
     *
     * @param string $name Nom du champ
     *
     * @return TextField
     */
    public function literal(string $name): TextField
    {
        return $this->text($name)->setAnalyzer(Options::LITERAL_ANALYZER);
    }

    /**
     * Ajoute un champ de type 'text' permettant de faire des recherches sur le path d'un tag dans une hiérarchie.
     *
     * @param string $name Nom du champ
     *
     * @return TextField
     */
    public function hierarchy(string $name): HierarchyField
    {
        return $this->addField(new HierarchyField($name));
    }

    /**
     * Ajoute un champ de type 'completion' paramétré avec l'analyseur 'suggest'.
     *
     * @param string $name Nom du champ
     *
     * @return CompletionField
     */
    public function suggest(string $name): CompletionField
    {
        return $this->completion($name)->setAnalyzer(Suggest::getName());
    }

    /**
     * Ajoute un champ de type 'text' paramétré avec l'analyseur 'url'.
     *
     * @param string $name Nom du champ
     *
     * @return TextField
     */
    public function url(string $name): TextField
    {
        return $this->text($name)->setAnalyzer(Url::getName());
    }
}

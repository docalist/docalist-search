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

namespace Docalist\Search\Mapping\Field\Factory;

use Docalist\Search\Mapping\Field\Factory\BaseFactoryTrait;
use Docalist\Search\Mapping\Field\TextField;
use Docalist\Search\Mapping\Field\CompletionField;
use Docalist\Search\Mapping\Field\Parameter\Analyzer;
use Docalist\Search\Mapping\Options;
use Docalist\Search\Analysis\Analyzer\Hierarchy;
use Docalist\Search\Analysis\Analyzer\Suggest;
use Docalist\Search\Analysis\Analyzer\Url;
use Docalist\Search\Mapping\Field\Info\Features;

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
     *
     * @see Url
     */
    public function hierarchy(string $name): TextField
    {
        /*
         * Ressources :
         * https://github.com/elastic/elasticsearch/issues/8896
         * https://github.com/opendatasoft/elasticsearch-aggregation-pathhierarchy
         * https://docs.searchkit.co/stable/components/navigation/hierarchical-refinement-filter.html
         * https://shoppinpal.gitbook.io/docs-shoppinpal-com/6.-elasticsearch/fun-with-path-hierarchy-tokenizer
         */
        return $this->text($name)
            ->setAnalyzer(Hierarchy::getName())
            ->setSearchAnalyzer('keyword')
            ->enableFieldData()
            ->setFeatures([Features::AGGREGATE]);
    }

    /**
     * Ajoute un champ de type 'completion' paramétré avec l'analyseur 'suggest'.
     *
     * @param string $name Nom du champ
     *
     * @return CompletionField
     *
     * @see Url
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
     *
     * @see Url
     */
    public function url(string $name): TextField
    {
        return $this->text($name)->setAnalyzer(Url::getName());
    }
}

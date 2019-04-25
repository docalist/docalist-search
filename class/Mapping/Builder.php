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

namespace Docalist\Search\Mapping;

use Docalist\Search\Analysis\Component;
use Docalist\Search\Analysis\Analyzer;
use Docalist\Search\Analysis\CharFilter;
use Docalist\Search\Analysis\Tokenizer;
use Docalist\Search\Analysis\TokenFilter;
use Docalist\Search\Mapping;
use Docalist\Search\Mapping\Options;
use InvalidArgumentException;

/**
 * Génère le mapping complet d'un index (les settings) à partir d'une liste de Mapping.
 *
 * La classe permet de fusionner plusieurs mappings en un seul en vérifiant que les champs communs à plusieurs
 * mappings sont compatibles. Elle permet ensuite de générer les settings de l'index en incluant les composants
 * d'analyse qui sont utilisés (analyseurs, filtres de caractères, tokenizers, filtres de tokens) et le mapping
 * final.
 *
 * Exemple :
 * <code>
 *     $builder = new Mapping\Builder($options);
 *
 *     $builder->addMapping($postIndexer->getMapping());
 *     $builder->addMapping($database->getMapping());
 *
 *     $esClient->put('/my-index', $builder->getSettings());
 * </code>
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
class Builder
{
    /**
     * Le mapping fusionné.
     *
     * @var Mapping
     */
    private $mapping;

    /**
     * Options de génération du mapping.
     *
     * @var Options
     */
    private $options;

    /**
     * Initialise le builder.
     *
     * @param Options|null $options Options de Mapping ou null pour utiliser les options par défaut.
     */
    public function __construct(Options $options = null)
    {
        $this->mapping = new Mapping('_doc');
        $this->options = $options ?: new Options();
    }

    /**
     * Ajoute un mapping au mapping résultat.
     *
     * @param Mapping $mapping Le mapping à fusionner.
     *
     * @throws InvalidArgumentException Si le mapping contient des champs qui ne sont pas compatibles ceux qui
     * figurent déjà dans le mapping fusionné.
     */
    public function addMapping(Mapping $mapping): void
    {
        $this->mapping->mergeWith($mapping);
    }

    /**
     * Retourne les settings de l'index.
     *
     * @return array
     */
    public function getIndexSettings(): array
    {
        // Paramètres de base de l'index
        $index = [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ]
        ];

        // Détermine la liste des analyseurs docalist utilisés dans les mappings
        $analyzers = $this->getAnalyzers();

        // Détermine l'ordre et la liste des différents composants utilisés dans les analyseurs docalist trouvés
        $sections = [
            'char_filter'   => $this->getCharFilters($analyzers),
            'tokenizer'     => $this->getTokenizers($analyzers),
            'filter'        => $this->getTokenFilters($analyzers),
            'analyzer'      => $analyzers,
        ];

        // Génère la section "analysis" des settings
        $analysis = [];
        foreach ($sections as $section => $components) {
            ksort($components);
            foreach ($components as $name => $component) { /** @var Component $component */
                $analysis[$section][$name] = $component->getDefinition();
            }
        }
        !empty($analysis) && $index['settings']['analysis'] = $analysis;

        // Génère le mapping
//        $index['mappings'][$mapping->getName()] = $mapping->getMapping($this->options);
        $index['mappings'] = $this->mapping->getMapping($this->options);

        // Ok
        return $index;
    }

    /**
     * Retourne les Analyzer docalist utilisés dans le mapping.
     *
     * @param Mapping $mapping
     *
     * @return Analyzer[]
     */
    private function getAnalyzers(): array
    {
        $analyzers = [];
        foreach ($this->mapping->getAnalyzers() as $name) {
            $analyzer = $this->options->getAnalyzer($name);
            !is_null($analyzer) && $analyzers[$analyzer->getName()] = $analyzer;
        }

        return $analyzers;
    }

    /**
     * Retourne les CharFilter docalist référencés dans le tableau d'Analyzer passé en paramètre.
     *
     * @param Analyzer[] $analyzers
     *
     * @return CharFilter[]
     */
    private function getCharFilters(array $analyzers): array
    {
        $charFilters = [];
        foreach ($analyzers as $analyzer) {
            foreach ($analyzer->getCharFilters() as $name) {
                $charFilter = $this->options->getCharFilter($name);
                !is_null($charFilter) && $charFilters[$name] = $charFilter;
            }
        }

        return $charFilters;
    }

    /**
     * Retourne les Tokenizer docalist référencés dans le tableau d'Analyzer passé en paramètre.
     *
     * @param Analyzer[] $analyzers
     *
     * @return Tokenizer[]
     */
    private function getTokenizers(array $analyzers): array
    {
        $tokenizers = [];
        foreach ($analyzers as $analyzer) {
            $name = $analyzer->getTokenizer();
            $tokenizer = $this->options->getTokenizer($name);
            !is_null($tokenizer) && $tokenizers[$name] = $tokenizer;

        }

        return $tokenizers;
    }

    /**
     * Retourne les TokenFilter docalist référencés dans le tableau d'Analyzer passé en paramètre.
     *
     * @param Analyzer[] $analyzers
     *
     * @return TokenFilter[]
     */
    private function getTokenFilters(array $analyzers): array
    {
        $tokenFilters = [];
        foreach ($analyzers as $analyzer) {
            foreach ($analyzer->getTokenFilters() as $name) {
                $tokenFilter = $this->options->getTokenFilter($name);
                !is_null($tokenFilter) && $tokenFilters[$name] = $tokenFilter;
            }
        }

        return $tokenFilters;
    }
}

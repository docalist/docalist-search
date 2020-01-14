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

namespace Docalist\Search;

use Docalist\Search\Mapping\Field\Parameter\Name;
use Docalist\Search\Mapping\Field\Parameter\NameTrait;
use Docalist\Search\Mapping\Field\Parameter\Fields;
use Docalist\Search\Mapping\Field\Parameter\FieldsTrait;
use Docalist\Search\Mapping\Field\Factory\FieldFactoryTrait;
use Docalist\Search\Mapping\Field\Info\FeaturesConstants;
use Docalist\Search\Mapping\Options;
use Docalist\Search\Analysis\Analyzer;
use Docalist\Search\Analysis\CharFilter;
use Docalist\Search\Analysis\Tokenizer;
use Docalist\Search\Analysis\TokenFilter;
use InvalidArgumentException;
use Docalist\Search\Mapping\Field\Info\Features;
use Docalist\Search\Mapping\Field\Info\Label;
use Docalist\Search\Mapping\Field\Info\Description;

/**
 * Une liste d'attributs de recherche stockés dans l'index elasticsearch.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Mapping implements Name, Fields, FeaturesConstants
{
    use NameTrait, FieldsTrait, FieldFactoryTrait;

    /**
     * Initialise le mapping.
     *
     * @param string $name Nom du mapping.
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * Fusionne le mapping passé en paramètre dans le mapping en cours.
     *
     * @param Mapping $other Mapping à fusionner.
     *
     * @throws InvalidArgumentException Si le mapping contient des champs incompatibles avec ceux du mapping en cours.
     */
    final public function mergeWith(Mapping $other): void
    {
        try {
            $this->mergeFields($other);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException($other->getName() . '.' . $e->getMessage());
        }
    }

    /**
     * Génère le mapping ElasticSearch.
     *
     * @param Options $options Options du mapping.
     *
     * @return array
     */
    final public function getMapping(Options $options): array
    {
        // Le mapping n'est pas dynamique (génère une exception si on introduit un champ qui n'a pas été déclaré)
        $mapping = ['dynamic' => 'strict'];

        // Génère le mapping des champs
        $mapping['properties'] = [];
        foreach ($this->fields as $name => $field) {
            $mapping['properties'][$name] = $field->getMapping($options);
        }

        // Ok
        return $mapping;
    }

    /**
     * Retourne les settings de l'index.
     *
     * @param Options $options Options de mapping.
     *
     * @return array
     */
    final public function getIndexSettings(Options $options): array
    {
        // Instancie les analyseurs docalist utilisés dans le mapping
        $analyzers = [];
        foreach ($this->getAnalyzers() as $name) {
            $analyzer = $options->getAnalyzer($name);
            !is_null($analyzer) && $analyzers[$analyzer->getName()] = $analyzer;
        }

        // Détermine l'ordre et la liste des différents composants utilisés dans les analyseurs docalist trouvés
        $sections = [
            'char_filter'   => $this->getCharFilters($analyzers, $options),
            'tokenizer'     => $this->getTokenizers($analyzers, $options),
            'filter'        => $this->getTokenFilters($analyzers, $options),
            'analyzer'      => $analyzers,
        ];

        // Génère la section "analysis" des settings
        $settings = $analysis = [];
        foreach ($sections as $section => $components) {
            ksort($components);
            foreach ($components as $name => $component) { /** @var Component $component */
                $analysis[$section][$name] = $component->getDefinition();
            }
        }
        !empty($analysis) && $settings['settings']['analysis'] = $analysis;

        // Génère le mapping
        $mapping = $this->getMapping($options);

        // Avant la version 7 de elasticsearch, il faut inclure le nom du mapping
        if (!version_compare($options->getVersion(), '6.99', '>')) { // tient compte des versions alpha, rc...
            $mapping = [$this->getName() => $mapping];
        }

        // Stocke le mapping dans les settings
        $settings['mappings'] = $mapping;

        // Ok
        return $settings;
    }

    /**
     * Retourne les CharFilter docalist référencés dans le tableau d'Analyzer passé en paramètre.
     *
     * @param Analyzer[]    $analyzers  Liste des Analyzer.
     * @param Options       $options    Options de mapping.
     *
     * @return CharFilter[]
     */
    private function getCharFilters(array $analyzers, Options $options): array
    {
        $charFilters = [];
        foreach ($analyzers as $analyzer) {
            foreach ($analyzer->getCharFilters() as $name) {
                $charFilter = $options->getCharFilter($name);
                !is_null($charFilter) && $charFilters[$name] = $charFilter;
            }
        }

        return $charFilters;
    }

    /**
     * Retourne les Tokenizer docalist référencés dans le tableau d'Analyzer passé en paramètre.
     *
     * @param Analyzer[]    $analyzers  Liste des Analyzer.
     * @param Options       $options    Options de mapping.
     *
     * @return Tokenizer[]
     */
    private function getTokenizers(array $analyzers, Options $options): array
    {
        $tokenizers = [];
        foreach ($analyzers as $analyzer) {
            $name = $analyzer->getTokenizer();
            $tokenizer = $options->getTokenizer($name);
            !is_null($tokenizer) && $tokenizers[$name] = $tokenizer;
        }

        return $tokenizers;
    }

    /**
     * Retourne les TokenFilter docalist référencés dans le tableau d'Analyzer passé en paramètre.
     *
     * @param Analyzer[]    $analyzers  Liste des Analyzer.
     * @param Options       $options    Options de mapping.
     *
     * @return TokenFilter[]
     */
    private function getTokenFilters(array $analyzers, Options $options): array
    {
        $tokenFilters = [];
        foreach ($analyzers as $analyzer) {
            foreach ($analyzer->getTokenFilters() as $name) {
                $tokenFilter = $options->getTokenFilter($name);
                !is_null($tokenFilter) && $tokenFilters[$name] = $tokenFilter;
            }
        }

        return $tokenFilters;
    }

    /**
     * Retourne les libellés des champs du mapping.
     *
     * @return string[] Un tableau de la forme nom de champ => libellé.
     *
     * Remarque : seuls les champs qui ont un libellé non vide sont retournés.
     */
    final public function getFieldsLabel(): array
    {
        $func = null;
        $func = function (array $fields, string $prefix = '') use (& $func): array {
            $result = [];
            foreach ($fields as $name => $field) {
                if ($field instanceof Fields) {
                    $result += $func($field->getFields(), $prefix . $name . '.');
                    continue;
                }
                if ($field instanceof Label) {
                    $label = $field->getLabel();
                    !empty($label) && $result[$prefix . $name] = $label;
                }
            }

            return $result;
        };

        return $func($this->getFields());
    }

    /**
     * Retourne les descriptions des champs du mapping.
     *
     * @return string[] Un tableau de la forme nom de champ => description.
     *
     * Remarque : seuls les champs qui ont une description non vide sont retournés.
     */
    final public function getFieldsDescription(): array
    {
        $func = null;
        $func = function (array $fields, string $prefix = '') use (& $func): array {
            $result = [];
            foreach ($fields as $name => $field) {
                if ($field instanceof Fields) {
                    $result += $func($field->getFields(), $prefix . $name . '.');
                    continue;
                }
                if ($field instanceof Description) {
                    $description = $field->getDescription();
                    !empty($description) && $result[$prefix . $name] = $description;
                }
            }

            return $result;
        };

        return $func($this->getFields());
    }

    /**
     * Retourne les caractéristiques des champs du mapping.
     *
     * @return int[] Un tableau de la forme nom de champ => features.
     *
     * Remarque : seuls les champs qui ont au moins une caractéristique sont retournés.
     */
    final public function getFieldsFeatures(): array
    {
        $func = null;
        $func = function (array $fields, string $prefix = '') use (& $func): array {
            $result = [];
            foreach ($fields as $name => $field) {
                if ($field instanceof Fields) {
                    $result += $func($field->getFields(), $prefix . $name . '.');
                    continue;
                }
                if ($field instanceof Features) {
                    $features = $field->getFeatures();
                    $result[$prefix . $name] = $features;
                }
            }

            return $result;
        };

        $fields = $func($this->getFields());
        ksort($fields);

        return $fields;
    }

    /**
     * Retourne un tableau de chaines qui décrivent les features passées en paramètre.
     *
     * @param int $features Optionnel bitmask des features à décrire (toutes par défaut).
     *
     * @return string[]
     */
    final public static function describeFeatures(int $features = -1): array
    {
        $labels = [];
        ($features & self::FULLTEXT) && $labels[self::FULLTEXT] = 'fulltext';
        ($features & self::FILTER) && $labels[self::FILTER] = 'filter';
        ($features & self::EXCLUSIVE) && $labels[self::EXCLUSIVE] = 'exclusive';
        ($features & self::AGGREGATE) && $labels[self::AGGREGATE] = 'aggregate';
        ($features & self::SORT) && $labels[self::SORT] = 'sort';
        ($features & self::LOOKUP) && $labels[self::LOOKUP] = 'lookup';
        ($features & self::SPELLCHECK) && $labels[self::SPELLCHECK] = 'spellcheck';
        ($features & self::HIGHLIGHT) && $labels[self::HIGHLIGHT] = 'highlight';

        return $labels;
    }
}

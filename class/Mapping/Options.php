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

use Docalist\Search\Analysis\Analyzer;
use Docalist\Search\Analysis\CharFilter;
use Docalist\Search\Analysis\Component;
use Docalist\Search\Analysis\Tokenizer;
use Docalist\Search\Analysis\TokenFilter;
use Docalist\Search\Analysis\Components;
use InvalidArgumentException;

/**
 * Gère les options de génération d'un mapping docalist-search.
 *
 * Cette classe contient des options qui permettent de personnaliser les mappings générés par docalist-search
 * (analyseurs par défaut, composants d'analyse personnalisés, version de elasticsearch, etc.)
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Options
{
    /**
     * Nom de code utilisé pour désigner l'analyseur par défaut.
     *
     * L'analyseur par défaut est choisi par l'administrateur en fonction de la langue des documents
     * à indexer. Normalement, c'est un analyseur qui fait du stemming (par exemple "french_text"
     * ou "english_text").
     *
     * Lors de la création du mapping, les champs qui portent sur du texte indiquent qu'ils veulent
     * l'analyseur par défaut en indiquant son code :
     *
     * <code>
     *     $title = new TextField('title');
     *     $title->setAnalyzer(Options::DEFAULT_ANALYZER);
     * </code>
     *
     * Lors de la génération du mapping, le nom de code temporaire est remplacé automatiquement par
     * le nom réel de l'analyseur indiqué comme analyseur par défaut dans les options :
     *
     * <code>
     *     $options = new Options(['default-analyzer' => 'french_text', ...]);
     *     $title->getMapping($options); // ['type' => 'text', 'analyzer' => 'french_text']
     * </code>
     *
     * @var string
     */
    public const DEFAULT_ANALYZER = '(default)';

    /**
     * Nom de code utilisé pour désigner l'analyseur littéral.
     *
     * L'analyseur littéral est utilisé pour indexer des chaines de caractères qui ne sont pas pas
     * des mots de la langue : noms de personnes, noms d'organismes, sigles...
     *
     * Normalement, il s'agit d'un analyseur qui ne fait pas de stemming (par exemple "text").
     *
     * Lors de la création du mapping, les champs indiquent qu'ils veulent l'analyseur litéral en
     * indiquant son code :
     *
     * <code>
     *     $author = new TextField('author');
     *     $title->setAnalyzer(Options::LITERAL_ANALYZER);
     * </code>
     *
     * Lors de la génération du mapping, le nom de code temporaire est remplacé automatiquement par
     * le nom réel de l'analyseur littéral indiqué dans les options :
     *
     * <code>
     *     $options = new Options(['literal-analyzer' => 'text', ...]);
     *     $title->getMapping($options); // ['type' => 'text', 'analyzer' => 'text']
     * </code>
     *
     * @var string
     */
    public const LITERAL_ANALYZER = '(literal)';

    /**
     * Les options du mapping généré.
     *
     * @var array
     */
    private $options;

    /**
     * Nom de l'option qui indique le numéro de version d'elasticsearch.
     *
     * La valeur associée est une chaine qui contient un numéro de version de la forme "x.y.z".
     *
     * Valeur par défaut : "6.7.1".
     *
     * @var string
     */
    public const OPTION_VERSION = 'version';

    /**
     * Nom de l'option qui indique l'analyseur par défaut.
     *
     * La valeur associée est une chaine qui indique le nom de code de l'analyseur par défaut.
     *
     * Valeur par défaut : "english_text".
     *
     * @var string
     */
    public const OPTION_DEFAULT_ANALYZER = 'default-analyzer';

    /**
     * Nom de l'option qui indique l'analyseur littéral.
     *
     * La valeur associée est une chaine qui indique le nom de code de l'analyseur littéral.
     *
     * Valeur par défaut : "text".
     *
     * @var string
     */
    public const OPTION_LITERAL_ANALYZER = 'literal-analyzer';

    /**
     * Nom de l'option qui fournit la liste des analyseurs disponibles.
     *
     * La valeur associée est un tableau qui indique le nom de code et le nom de classe php des
     * analyseurs (objets de type Analyzis\Analyzer) définis dans l'application.
     *
     * Exemple : ['custom_text' => 'My\Namespace\CustomAnalyzer']
     *
     * Valeur par défaut : la liste retournée par Analyzis\Components::getAnalyzers().
     *
     * @var string
     */
    public const OPTION_ANALYZERS = 'analyzers';

    /**
     * Nom de l'option qui fournit la liste des filtres de caractères disponibles.
     *
     * La valeur associée est un tableau qui indique le nom de code et le nom de classe php des
     * filtres de caractères (objets de type Analyzis\CharFiter) définis dans l'application.
     *
     * Exemple : ['remove_bad_chars' => 'My\Namespace\BadCharsFilter']
     *
     * Valeur par défaut : la liste retournée par Analyzis\Components::getCharFilters().
     *
     * @var string
     */
    public const OPTION_CHARFILTERS = 'charfilters';

    /**
     * Nom de l'option qui fournit la liste des tokenizers disponibles.
     *
     * La valeur associée est un tableau qui indique le nom de code et le nom de classe php des
     * tokenizers (objets de type Analyzis\Tokenizer) définis dans l'application.
     *
     * Exemple : ['split_words' => 'My\Namespace\SplitWordsTokenizer']
     *
     * Valeur par défaut : la liste retournée par Analyzis\Components::getTokenizers().
     *
     * @var string
     */
    public const OPTION_TOKENIZERS = 'tokenizers';

    /**
     * Nom de l'option qui fournit la liste des filtres de tokens disponibles.
     *
     * La valeur associée est un tableau qui indique le nom de code et le nom de classe php des
     * filtres de tokens (objets de type Analyzis\TokenFilter) définis dans l'application.
     *
     * Exemple : ['remove_numbers' => 'My\Namespace\RemoveNumbersTokenFilter']
     *
     * Valeur par défaut : la liste retournée par Analyzis\Components::getTokenFilters().
     *
     * @var string
     */
    public const OPTION_TOKENFILTERS = 'tokenfilters';

    /**
     * Initialise les options de génération des mappings docalist-search.
     *
     * @param array $options Optionnel, un tableau d'options de la forme : "nom d'option" => valeur.
     *
     * Consultez la documentation des constantes OPTION_* pour voir la liste des options disponibles
     * (nom, type, format, valeur par défaut).
     *
     * Si une option n'est pas fournie, elles est initialisée avec sa valeur par défaut.
     *
     * Exemple :
     *
     * <code>
     *     $options = new Options([
     *         Options::OPTION_VERSION => "7.0.0",
     *         Options::OPTION_DEFAULT_ANALYZER => "french_text",
     *     ]);
     * </code>
     */
    final public function __construct(array $options = [])
    {
        if (empty($options[self::OPTION_VERSION])) {
            $options[self::OPTION_VERSION] = '6.4.7';
        }

        if (empty($options[self::OPTION_DEFAULT_ANALYZER])) {
            $options[self::OPTION_DEFAULT_ANALYZER] = 'english_text';
        }

        if (empty($options[self::OPTION_LITERAL_ANALYZER])) {
            $options[self::OPTION_LITERAL_ANALYZER] = 'text';
        }

        if (empty($options[self::OPTION_ANALYZERS])) {
            $options[self::OPTION_ANALYZERS] = Components::getAnalyzers();
        }

        if (empty($options[self::OPTION_CHARFILTERS])) {
            $options[self::OPTION_CHARFILTERS] = Components::getCharFilters();
        }

        if (empty($options[self::OPTION_TOKENIZERS])) {
            $options[self::OPTION_TOKENIZERS] = Components::getTokenizers();
        }

        if (empty($options[self::OPTION_TOKENFILTERS])) {
            $options[self::OPTION_TOKENFILTERS] = Components::getTokenFilters();
        }

        $this->options = $options;
    }

    /**
     * Retourne la version de elasticsearch.
     *
     * @return string
     */
    final public function getVersion(): string
    {
        return $this->options[self::OPTION_VERSION];
    }

    /**
     * Retourne le nom de l'analyseur par défaut.
     *
     * @return string
     */
    final public function getDefaultAnalyzer(): string
    {
        return $this->options[self::OPTION_DEFAULT_ANALYZER];
    }

    /**
     * Retourne le nom de l'analyseur littéral.
     *
     * @return string
     */
    final public function getLiteralAnalyzer(): string
    {
        return $this->options[self::OPTION_LITERAL_ANALYZER];
    }

    /**
     * Retourne l'Analyzer docalist ayant le nom indiqué.
     *
     * @param string $name
     *
     * @return Analyzer|null Retourne l'analyseur demandé ou null s'il s'agit d'un composant prédéfini par ES.
     */
    final public function getAnalyzer(string $name): ?Analyzer
    {
        switch ($name) {
            case self::DEFAULT_ANALYZER:
                $name = $this->getDefaultAnalyzer();
                break;

            case self::LITERAL_ANALYZER:
                $name = $this->getLiteralAnalyzer();
                break;
        }

        return $this->getAnalysisComponent(self::OPTION_ANALYZERS, $name);
    }

    /**
     * Retourne le CharFilter docalist ayant le nom indiqué.
     *
     * @param string $name
     *
     * @return Tokenizer|null Retourne le CharFilter demandé ou null s'il s'agit d'un composant prédéfini par ES.
     */
    final public function getCharFilter(string $name): ?CharFilter
    {
        return $this->getAnalysisComponent(self::OPTION_CHARFILTERS, $name);
    }

    /**
     * Retourne le Tokenizer docalist ayant le nom indiqué.
     *
     * @param string $name
     *
     * @return Tokenizer|null Retourne le tokenizer demandé ou null s'il s'agit d'un composant prédéfini par ES.
     */
    final public function getTokenizer(string $name): ?Tokenizer
    {
        return $this->getAnalysisComponent(self::OPTION_TOKENIZERS, $name);
    }

    /**
     * Retourne le TokenFilter docalist ayant le nom indiqué.
     *
     * @param string $name
     *
     * @return TokenFilter|null Retourne le TokenFilter demandé ou null s'il s'agit d'un composant prédéfini par ES.
     */
    final public function getTokenFilter(string $name): ?TokenFilter
    {
        return $this->getAnalysisComponent(self::OPTION_TOKENFILTERS, $name);
    }

    /**
     * Retourne le composant d'analyse ayant le type et le nom indiqué.
     *
     * @param string $category  Type de composant à retourner (analyzers, tokenizers, filters ou charfilters)
     * @param string $name      Nom du composant à retourner.
     *
     * @return Component|null
     */
    private function getAnalysisComponent(string $category, string $name): ?Component
    {
        // Correspondance catégorie => nom d'interface
        $interfaces = [
            self::OPTION_ANALYZERS => Analyzer::class,
            self::OPTION_CHARFILTERS => CharFilter::class,
            self::OPTION_TOKENIZERS => Tokenizer::class,
            self::OPTION_TOKENFILTERS => TokenFilter::class,
        ];

        // Vérifie que la catégorie indiquée est valide
        if (! isset($interfaces[$category])) {
            throw new InvalidArgumentException('Invalid category');
        }

        // Si ce n'est pas un composant connu, considère qu'il s'agit d'un composant prédéfini de elasticsearch
        if (! isset($this->options[$category][$name])) {
            return null;
        }

        // Récupère le nom de la classe qui implémente ce composant
        $class = $this->options[$category][$name];

        // Vérifie que la classe indiquée existe
        if (! class_exists($class)) {
            throw new InvalidArgumentException(sprintf(
                '%s: unable to create component "%s", class %s not found',
                ucfirst($category),
                $name,
                $class
            ));
        }

        // Vérifie que la classe indiquée est bien du bon type
        $interface = $interfaces[$category];
        if (! is_a($class, $interface, true)) {
            throw new InvalidArgumentException(sprintf(
                '%s: component "%s" (%s) must implement interface %s',
                ucfirst($category),
                $name,
                $class,
                $interface
            ));
        }

        // Ok
        return new $class();
    }
}

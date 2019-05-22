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

namespace Docalist\Search;

use Docalist\Search\Mapping\Field\Info\Features;
use Closure;

/**
 * Gère la liste des attributs de recherche générés par le mapping.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class SearchAttributes
{
    /**
     * Libellés des champs.
     *
     * @var Closure | string[] Initialement une fonction puis un tableau de la forme nom de champ => label.
     */
    private $labels;

    /**
     * Descriptions des champs.
     *
     * @var Closure | string[] Initialement une fonction puis un tableau de la forme nom de champ => description.
     */
    private $descriptions;

    /**
     * Caractéristiques des champs.
     *
     * @var Closure | int[] Initialement une fonction puis un tableau de la forme nom de champ => features.
     */
    private $features;

    /**
     * Initialise la liste des attributs de recherche.
     *
     * @param Closure $labels       Une fonction qui retourne un tableau de la forme nom de champ => label.
     * @param Closure $description  Une fonction qui retourne un tableau de la forme nom de champ => description.
     * @param Closure $features     Une fonction qui retourne un tableau de la forme nom de champ => features.
     */
    final public function __construct(Closure $labels, Closure $descriptions, Closure $features)
    {
        $this->labels = $labels;
        $this->descriptions = $descriptions;
        $this->features = $features;
    }

    /**
     * Retourne le libellé des attributs.
     *
     * @return string[] Un tableau de la forme nom de champ => libellé.
     */
    final public function getAllLabels(): array
    {
        ($this->labels instanceof Closure) && $this->labels = ($this->labels)();

        return $this->labels;
    }

    /**
     * Retourne le libellé d'un attribut.
     *
     * @param string $name Nom de l'attribut recherché.
     *
     * @return string Libellé de l'attribut ou une chaine vide si l'attribut n'existe pas.
     */
    final public function getLabel(string $name): string
    {
        return $this->getAllLabels()[$name] ?? '';
    }

    /**
     * Retourne la description des attributs.
     *
     * @return string[] Un tableau de la forme nom de champ => description.
     */
    final public function getAllDescriptions(): array
    {
        ($this->descriptions instanceof Closure) && $this->descriptions = ($this->descriptions)();

        return $this->descriptions;
    }

    /**
     * Retourne la description d'un attribut.
     *
     * @param string $name Nom de l'attribut recherché.
     *
     * @return string Description de l'attribut ou une chaine vide si l'attribut n'existe pas.
     */
    final public function getDescription(string $name): string
    {
        return $this->getAllDescriptions()[$name] ?? '';
    }

    /**
     * Retourne les caractéristiques des attributs.
     *
     * @return int[] Un tableau de la forme nom de champ => features.
     */
    final public function getAllFeatures(): array
    {
        ($this->features instanceof Closure) && $this->features = ($this->features)();

        return $this->features;
    }

    /**
     * Retourne les caractéristiques d'un attribut.
     *
     * @param string $name Nom de l'attribut recherché.
     *
     * @return int Les caractéristiques du champ ou 0 si l'attribut n'existe pas.
     */
    final public function getFeatures(string $name): int
    {
        return $this->getAllFeatures()[$name] ?? 0;
    }

    /**
     * Teste l'existence d'un attribut avec toutes les caractéristiques indiquées.
     *
     * @param string    $name       Nom de l'attribut à tester.
     * @param int       $features   Optionnel, caractéristique(s) à tester.
     *
     * @return bool
     */
    final public function has(string $name, int $features = 0): bool
    {
        return ($this->getFeatures($name) & $features) === $features;
    }

    /**
     * Retourne la liste des attributs qui ont toutes les caractéristiques indiquées.
     *
     * @param int $features Caractéristique(s) à tester.
     *
     * @return int[] Un tableau de la forme nom de champ => features.
     */
    final public function filterByFeatures(int $features): array
    {
        return array_filter($this->getAllFeatures(), function (int $fieldFeatures) use ($features): bool {
            return ($fieldFeatures & $features) === $features;
        });
    }
}

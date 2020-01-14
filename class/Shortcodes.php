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

use Docalist\Search\Shortcode;
use Docalist\Search\Shortcode\SearchResults;
use LogicException;

/**
 * Gère les shortcodes de docalist-search.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
final class Shortcodes
{
    /**
     * Liste des shortcodes disponibles.
     *
     * @var array nom => class
     */
    private $list = [
        'docalist_search_results' => SearchResults::class,
    ];

    /**
     * Déclare les shortcodes disponibles dans WordPress.
     */
    public function register(): void
    {
        foreach ($this->getList() as $name) {
            add_shortcode($name, function ($attributes, string $content, string $name) {
                // $attributes n'est pas typé car WordPress peut nous passer une chaine, normalisé ci-dessous
                return $this->getShortcode($name)->render(is_string($attributes) ? [] : $attributes, $content);
            });
        }
    }

    /**
     * Retourne la liste des shortcodes disponibles.
     *
     * @return string[] Un tableau contenant le nom des shortcodes.
     */
    public function getList(): array
    {
        return array_keys($this->list);
    }

    /**
     * Retourne un shortcode.
     *
     * @param string $name Nom du shortcode à retourner.
     *
     * @throws LogicException Si le shortcode indiqué n'existe pas.
     *
     * @return Shortcode
     */
    public function getShortcode(string $name): Shortcode
    {
        if (! isset($this->list[$name])) {
            throw new LogicException(sprintf('shortcode not found "%s"', $name));
        }

        return new $this->list[$name];
    }
}

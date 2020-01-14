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

/**
 * Interface des shortcodes de docalist-search.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Shortcode
{
    /**
     * Retourne la liste des attributs du shortcode et leur valeur par défaut.
     *
     * @return string[]
     */
    public function getDefaultAttributes(): array;

    /**
     * Exécute le shortcode avec les attributs et le contenu passé en paramètre et retourne le résultat.
     *
     * @param array     $attributes Attributs indiqués dans le shortcode.
     * @param string    $content    Contenu du shortcode (entre les balises d'ouverture et de fermeture).
     *
     * @return string Contenu généré par le shortcode.
     */
    public function render(array $attributes, string $content): string;
}

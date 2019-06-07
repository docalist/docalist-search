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

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Options;

/**
 * Gère le paramètre "copy_to" d'un champ de mapping.
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/copy-to.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface CopyTo
{
    /**
     * Ajoute un champ au paramètre "copy_to" du champ.
     *
     * La méthode peut être appellée plusieurs fois pour copier le champ vers plusieurs destinations.
     *
     * @param string $field Nom du champ à ajouter.
     *
     * @return self
     */
    public function copyTo(string $field); // pas de return type en attendant covariant-returns

    /**
     * Retourne la valeur du paramètre "copy_to" du champ.
     *
     * @return string[] La méthode retourne toujours un tableau.
     */
    public function getCopyTo(): array;
}

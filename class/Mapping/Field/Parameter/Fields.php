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

namespace Docalist\Search\Mapping\Field\Parameter;

use Docalist\Search\Mapping\Field;
use Docalist\Search\Mapping\Field\Parameter\Analyzer;
use InvalidArgumentException;

/**
 * Gère la liste des champs d'un champ de mapping de type "object" (paramètre "properties").
 *
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/object.html
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
interface Fields
{
    /**
     * Ajoute un champ.
     *
     * @param Field $field Champ à ajouter.
     *
     * @throws InvalidArgumentException S'il existe déjà un champ avec le même nom.
     *
     * @return Field Le champ ajouté.
     */
    public function addField(Field $field): Field;

    /**
     * Teste si le champ indiqué existe.
     *
     * @param string $name Nom du champ.
     *
     * @return bool
     */
    public function hasField(string $name): bool;

    /**
     * Retourne un champ.
     *
     * @param string $name Nom du champ à retourner.
     *
     * @throws InvalidArgumentException Si le champ indiqué n'existe pas.
     *
     * @return Field
     */
    public function getField(string $name): Field;

    /**
     * Retourne la liste des champs.
     *
     * @return Field[]
     */
    public function getFields(): array;

    /**
     * Retourne la liste des analyseurs utilisés dans les champs et sous-champs de l'objet.
     *
     * @return string[] Le nom des analyseurs utilisés.
     */
    public function getAnalyzers(): array;

    /**
     * Retourne la liste des normalizers utilisés dans les champs et sous-champs de l'objet.
     *
     * @return string[] Le nom des normalizers utilisés.
     */
    public function getNormalizers(): array;
}

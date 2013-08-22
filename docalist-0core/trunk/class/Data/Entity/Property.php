<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Core
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Data\Entity;

use Docalist\Data\Schema\FieldInterface;
use ArrayObject;
use InvalidArgumentException;
use Docalist\Utils;
/**
 * Implémentation standard de l'interface PropertyInterface.
 *
 * Cette classe permet d'encapsuler :
 * - une propriété scalaire répétable (int[], string[], ...)
 * - un objet anonyme (sans entité associée)
 * - une collection d'objets anonymes (object[])
 * - une collection d'entités
 *
 * Les propriétés scalaires simples (non répétables) sont directement
 * représentée par le type php correspondant.
 */
class Property extends SchemaBasedObject implements PropertyInterface {
    /**
     * Le schéma de la propriété.
     *
     * @var FieldInterface
     */
    protected $schema;

    /**
     * Construit une nouvelle propriété.
     *
     * @param FieldInterface $schema Le schéma de la propriété.
     *
     * @param array $data Les données initiales de la propriété.
     */
    public function __construct(FieldInterface $schema, array $data = null) {
        // Stocke le schéma
        $this->schema = $schema;

        parent::__construct($data);
    }

    public function schema($field = null) {
        return is_null($field) ? $this->schema : $this->schema->field($field);
    }
}
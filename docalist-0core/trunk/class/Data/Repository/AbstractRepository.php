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
namespace Docalist\Data\Repository;

use Docalist\Data\Entity\EntityInterface;
use InvalidArgumentException;

/**
 * Classe de base abstraite pour implémenter un dépôt.
 *
 * La classe se contente de fournir des méthodes utilitaires :
 * - checkType
 * - checkId
 */
abstract class AbstractRepository implements RepositoryInterface {
    /**
     * Le type du dépôt.
     *
     * @var string
     */
    protected $type;

    /**
     * Crée un nouveau dépôt.
     *
     * @param string $type le nom complet de la classe Entité utilisée pour
     * représenter les enregistrements de ce dépôt.
     *
     * @throws Exception Si $type ne désigne pas une classe d'entité.
     */
    public function __construct($type) {
        $this->checkType($type, 'Docalist\Data\Entity\EntityInterface');
        $this->type = $type;
    }

    public function type() {
        return $this->type;
    }

    /**
     * Vérifie que l'objet ou le nom de classe passé en paramètre correspond
     * au type indiqué et génère une exception si ce n'est pas le cas.
     *
     * @param string|object $test Le nom de classe ou l'objet à tester.
     *
     * @param string $type Le type requis. Si aucun type n'est indiqué, le
     * type de l'entrepôt est utilisé à la place.
     *
     * @return bool Retourne true si $test est du type indiqué.
     *
     * @throws InvalidArgumentException Sinon
     */
    protected function checkType($test, $type = null) {
        // Appel de la forme checkType(false) = récupérer données brutes
        if ($test === false) {
            return $test;
        }

        // Appel de la forme checktype($entity) : on prend le type du dépôt
        is_null($type) && $type = $this->type();

        // Si test est du bon type, terminé
        if (is_a($test, $type, true)) {
            return true;
            // is_a ou instanceof ?
            // - test peut être soit un nom de classe, soit un objet
            // - type peut être un nom de classe ou un nom d'interface
            // is_a est le seul qui fonctionne dans tous les cas
            // apparemment class instanceof class ne fonctionne pas bien
        }

        // Erreur
        $msg = 'Incorrect entity type %s, expected %s';
        is_object($test) && $test = get_class($test);
        throw new InvalidArgumentException(sprintf($msg, $test, $type));
    }

    /**
     * Extrait l'id de l'entité à partir du paramètre indiqué.
     *
     * @param scalar|EntityInterface $entity
     *
     * @param bool $required Indique s'il faut obtenir un ID non vide.
     *
     * @return scalar Si $entity est déjà un ID, il est retourné tel quel.
     * Si $entity est une entité, la méthode retourne l'ID de l'entité.
     *
     * @throws InvalidArgumentException si le type de $entity n'est pas correct
     * ou si $required est à true et que l'ID obtenu est vide.
     *
     */
    protected function checkId($entity, $required = false) {
        if (is_scalar($entity)) {
            $id = $entity;
        } elseif (is_object($entity)) {
            $this->checkType($entity);
            $id = $entity->id();
        } else {
            $msg = 'Unable to get entity ID';
            throw new InvalidArgumentException($msg);
        }

        if ($required && empty($id)) {
            $msg = 'Entity ID is required';
            throw new InvalidArgumentException($msg);
        }

        return $id;
    }
}
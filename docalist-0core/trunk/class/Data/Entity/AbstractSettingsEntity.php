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

use Docalist\Data\Repository\SettingsRepository;

/**
 * Classe de base des entités stockées dans un dépôt SettingsRepository.
 */
abstract class AbstractSettingsEntity extends AbstractEntity {
    protected static $repository;

    public function __construct($id) {
        // Stocke l'id du settings
        $this->id = $id;

        // Initialise le dépôt si nécessaire
        if (is_null(self::$repository)) {
            self::$repository = new SettingsRepository();
        }

        // Charge les données brutes du settings
        $data = self::$repository->load($id, false);

        // Initialise l'instance
        parent::__construct($data);
    }

    public function save() {
        self::$repository->store($this);
    }

    public function reset() {
        self::$repository->delete($this);
        parent::exchangeArray(array());
    }
}
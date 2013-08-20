<?php
/**
 * This file is part of a "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Data\Repository;
use Exception;

/**
 * Un dépôt dans lequel les entités sont stockées dans une table personnalisée
 * au sein de la base de données WordPress.
 */
class CustomTableRepository extends AbstractRepository {
    public function __construct($type) {
        throw new Exception('Not implemented');
    }
}
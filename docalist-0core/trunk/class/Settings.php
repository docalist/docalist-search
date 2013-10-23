<?php
/**
 * This file is part of the 'Docalist Core' plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist;

use Docalist\Data\Entity\AbstractSettingsEntity;
use Docalist\Table\TableInfo;

/**
 * Config de Docalist Core.
 *
 * @property Docalist\Table\TableInfo[] $tables Liste des tables.
 */
class Settings extends AbstractSettingsEntity
{
    protected function loadSchema() {
        return array(
            'tables' => array(
                'type' => 'Docalist\Table\TableInfo*',
                'label' => __('Liste des tables d\'autorité personnalisées', 'docalist-core'),
            ),
        );
    }
}
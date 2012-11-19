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

namespace Docalist\Core;

/**
 * Plugin core de Docalist.
 */
class Plugin extends AbstractPlugin {
    /**
     * {@inheritdoc}
     */
    protected function getDefaultOptions() {
        return array(
            'core.test' => __('TEST', 'docalist-biblio'),
            '' => '',
        );
    }

    /**
     * {@inheritdoc}
     * 
     * Pour qu'il soit chargé en premier, le plugin Docalist-core a un nom de 
     * répertoire un peu spécial (docalist-0core) et par défaut, c'est ce nom
     * qui devrait être utilisé comme domaine pour les traductions.
     * 
     * En surchargeant cette méthode, on indique le bon domaine : docalist-core.
     */
    public function textDomain() {
        return 'docalist-core';
    }

}

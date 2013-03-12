<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Search;
use Docalist\Plugin;

/**
 * Plugin elastic-search.
 */
class Search extends Plugin {
    /**
     * @inheritdoc
     */
    public function register() {
        // Configuration du plugin
        $this->add(new Settings);

        // Client ElasticSearch
        $this->add(new ElasticSearch);

        // Page outils
        $this->add(new Actions);

        // Page de configuration
        add_action('admin_menu', function() {
            $this->add(new SettingsPage);
        });
    }

}

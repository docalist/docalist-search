<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core\Tools
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Core\Tools;
use Docalist\Core\AbstractTool;

/**
 * PhpInfo.
 */
class PhpInfo extends AbstractTool {
    /**
     * {@inheritdoc}
     */
    protected $ajax = true;

    /**
     * {@inheritdoc}
     */
    protected $extraArguments = 'height=600&width=800';

    /**
     * {@inheritdoc}
     */
    public function name() {
        return __('Informations PHP', 'docalist-core');
    }


    /**
     * {@inheritdoc}
     */
    public function description() {
        return __('Informations détaillées sur la configuration de PHP et les extensions installées sur ce serveur.', 'docalist-core');
    }


    /**
     * Génère le PhpInfo.
     */
    public function actionIndex() {
        // Début de la page
        echo '<p id="toc">Ce site exécute <strong>PHP version ', PHP_VERSION, '</strong> avec les modules suivants :</p>';
        echo '<table class="widefat">';
        echo '<tr><th>Extension</th><th>Version</th></tr>';

        // Trie la liste des extensions, en laissant 'Core' en premier
        $extensions = get_loaded_extensions();
        unset($extensions['Core']);
        natcasesort($extensions);
        array_unshift($extensions, 'Core');

        // Affiche la liste
        foreach ($extensions as $extension) {
            $version = phpversion($extension);
            echo '<tr>';
            printf('<td class="row-title"><a href="#module_%1$s">%1$s</a></td>', $extension);
            printf('<td>%s</a></td>', $version);
            echo '</tr>';
        }
        echo '<table>';

        // Config de chacune des extensions
        ob_start();
        phpinfo(8);
        $info = ob_get_clean();
        $info = preg_replace('~^.*<body>(.*)</body>.*$~ms', '$1', $info);
        $info = preg_replace('~<h2><a name="(.*?)">(.*?)</a></h2>~', '<h3 id="$1" style="margin-top:100em; padding: 1em 0;"><a style="float: right" href="#toc">Retour</a>Configuration du module $2</h3>', $info);
        $info = preg_replace('~(<table.*?)(width.*?)(>)~', '$1class="widefat"$3', $info);
        $info = preg_replace('~<td class="e">~', '<td class="row-title">', $info);
        $info = preg_replace('~cellpadding="3"~', '', $info);
        $info = preg_replace('~<hr />~', '', $info);

        echo $info;

    }


}

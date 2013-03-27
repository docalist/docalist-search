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
use Docalist\AbstractAdminPage;
use Docalist\Forms\Form;

class Tools extends AbstractAdminPage{
    /**
     * {@inheritdoc}
     */
    protected $parentPage = 'tools.php';

    /**
     * Réindexation complète.
     *
     * Lance une réindexation complète de tous les contenus WordPress.
     *
     * L'index existant, s'il existe, est supprimé. Un nouvel index est
     * créé et tous les contenus existants dans WordPress sont envoyés à
     * ElasticSearch.
     */
    public function actionReindex() {
        if (! $this->confirm('Vous confirmez ?')) return;

        echo "<h3>Lancement de la réindexation</h3>";

        $es = $this->parent->get('elasticsearch');
        $index = $this->setting('server.index');

        $type = 'dclref';
        $bulk = true;
        $batchSize = 10000;

        // Teste si l'index existe déjà
        if ($es->exists("/$index")) {
            echo "<p>L'index $index existe déjà, SUPPRESSION.</p>";
            $es->delete("/$index");
        }

        $settings = apply_filters("dclsearch_{$type}_mapping", null);
        if (is_null($settings)) {
            echo "<p>Aucun plugin n'a retourné de mapping pour le type <code>$type</code>.</p>";
            $settings = array();
        }
        $settings['_meta']['docalist-search'] = 0.1; // this version

        echo "<p>Création de l'index <code>$index</code>.</p>";
        $es->put("/$index", $settings);
/*
        echo "<p>Création d'un mapping pour le type <code>$type</code>.</p>";
        $es->put("/$index/$type/_mapping", $mapping);
*/
        $nb = 0;

        echo '<pre>';
        echo 'Temps écoulé (sec) ; Nb de notices chargées ; memory_get_usage() ; memory_get_usage(true) ; memory_get_peak_usage() ; memory_get_peak_usage(true)', '<br />';

        set_time_limit(3600);
        while(ob_get_level()) ob_end_flush();

        $time = microtime(true);
        for ($batchNumber = 1 ;; $batchNumber++) {
            $posts = get_posts(array(
                'posts_per_page' => $batchSize,
                'post_type' => $type,
                'paged' => $batchNumber,
                'post_status' => 'publish',
                'orderby' => 'ID',
                'order' => 'ASC',
            ));

            if (count($posts) === 0) {
                break;
            }

            $data = '';
            foreach($posts as $post) {
                if (0 === $nb % 100) {
                    echo round(microtime(true)-$time,2), ' ; ', $nb, ' ; ', memory_get_usage(), ' ; ', memory_get_usage(true), ' ; ', memory_get_peak_usage(), ' ; ', memory_get_peak_usage(true), '<br />';
                    flush();
                    \wp_cache_init();
                }
                ++$nb;

                $id = $post->ID;
                $post = apply_filters("dclsearch_{$type}_index", $post);
                unset($post['notes']); // TODO

                if ($bulk) {
                    $data .= sprintf('{"index":{"_id":%d}}%s%s%s',
                        $id,
                        "\n",
                        json_encode($post, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        "\n"
                    );
                } else {
                    $es->put("/$index/$type/$id", $post);
                }
            }

            if ($bulk) {
                $es->bulk("/$index/$type/_bulk", $data);
            }

//            if ($nb>=100) break;
        }
        $time = microtime(true) - $time;

        echo '</pre>';
        echo "<p>Notices indexées : $nb.</p>";
        echo "<p>Temps total : ", round($time, 2), " secondes.</p>";
    }

    /**
     * Affiche un enregistrement ElasticSearch.
     *
     * Cette action permet de voir comment ElasticSearch a stocké un
     * enregistrement. Elle affiche le contenu de l'enregistrement et la liste
     * des tokens d'indexation résultant de l'analyse.
     */
    public function actionShow($id = null) {
        printf('<h2>%s</h2><p>%s</p>', $this->title(), $this->description());

        $this->ask('input', 'id', "ID de l'enregistrement à afficher :");
        if (empty($id)) {
            return;
        }

        $es = $this->parent->get('elasticsearch');
        $index = $this->setting('server.index');

        $facets = array(
            'facets' => array(
                'title' => array(
                    'terms' => array(
                        'size' => 1000,
                        'field' => 'title',
                    ),
                ),
            ),
        );
        $data = $es->get("$index/_search?pretty&q=_id:$id", $facets);

        echo '<pre>', print_r($data->hits->hits[0]->_source, true), '</pre>';
        $this->dumpv($data->hits->hits[0]->_source);
        echo '<hr />';
        $this->dumpv($data);
        echo '<pre>', print_r($data->facets->title,true), '</pre>';

        $this->dump($es->get("$index/_mapping"));

    }

    /**
     * Requête ElasticSearch.
     *
     * Permet d'envoyer une requête GET au serveur ElasticSearch et de voir le
     * résultat.
     *
     * @param string $method
     * @param string $endpoint
     */
    public function actionEsRequest($method = null, $endpoint = null) {
        $usual = array(
            'HEAD /' => 'Le serveur fonctionne ?',
            'GET /_aliases' => 'liste des alias',
            'GET /_cluster/health',
            'GET /_cluster/nodes',
            'GET /_cluster/nodes/hot_threads',
            'GET /_cluster/nodes/stats',
            'GET /_cluster/settings',
            'GET /_cluster/state',
            'GET /_count',
            'GET /_flush',
            'GET /_mapping',
            'GET /_nodes',
            'GET /_nodes/fs/stats',
            'GET /_nodes/hot_threads',
            'GET /_nodes/http',
            'GET /_nodes/http/stats',
            'GET /_nodes/indices/stats',
            'GET /_nodes/jvm',
            'GET /_nodes/jvm/stats',
            'GET /_nodes/network',
            'GET /_nodes/network/stats',
            'GET /_nodes/os',
            'GET /_nodes/os/stats',
            'GET /_nodes/process',
            'GET /_nodes/process/stats',
            'GET /_nodes/settings',
            'GET /_nodes/stats',
            'GET /_nodes/stats/fs',
            'GET /_nodes/stats/http',
            'GET /_nodes/stats/indices',
            'GET /_nodes/stats/jvm',
            'GET /_nodes/stats/network',
            'GET /_nodes/stats/os',
            'GET /_nodes/stats/process',
            'GET /_nodes/stats/thread_pool',
            'GET /_nodes/stats/transport',
            'GET /_nodes/thread_pool',
            'GET /_nodes/thread_pool/stats',
            'GET /_nodes/transport',
            'GET /_nodes/transport/stats',
            'GET /_optimize',
            'GET /_refresh',
            'GET /_search',
            'GET /_search/scroll',
            'GET /_search_shards',
            'GET /_segments',
            'GET /_settings',
            'GET /_stats',
            'GET /_stats/flush',
            'GET /_stats/get',
            'GET /_stats/indexing',
            'GET /_stats/merge',
            'GET /_stats/refresh',
            'GET /_stats/search',
            'GET /_stats/store',
            'GET /_stats/warmer',
            'GET /_status',
            'GET /_stats/docs',
        );

        echo '<h3>Choisissez une requête standard dans la liste ci-dessous :</h3>';
        $url = $this->url() ;
        $script = sprintf('window.location="'.$url.'&method="+%s+"&endpoint="+%s;',
            'this.options[this.selectedIndex].dataset.method',
            'this.options[this.selectedIndex].value'
        );
        $script = htmlspecialchars($script);
        echo "<select id='usual' onchange='$script' size='10' style='height: 10em'>";
        foreach($usual as $url => $title) {
            if (is_int($url)) {
                $url = $title;
                $title = '';
            }
            list($method, $url) = explode(' ', $url, 2);
            $title ? $title = "$url ($title)" : $title = $url;
            printf(
                '<option value="%s" data-method="%s" %s>%s</option>',
                $url,
                $method,
                $endpoint === $url ? 'selected="selected"' : '',
                $title
            );
        }
        echo '</select>';

        echo '<h3>Ou saisissez votre requête dans le formulaire ci-dessous :</h3>';

        empty($_REQUEST['method']) && $_REQUEST['method'] = 'HEAD';
        empty($_REQUEST['endpoint']) && $_REQUEST['endpoint'] = '/';

        $form = new Form('', 'GET');

        $form->select('method')
             ->label('Méthode HTTP à utiliser :')
             ->options(array('HEAD', 'GET'));

        $form->input('endpoint')
             ->label("Point d'entrée à appeller dans l'API :");

        $form->submit('Go »');
        $form->hidden('page');
        $form->hidden('m');

        $form->bind($_REQUEST);
        $form->render('wordpress');

        printf('<h3>Résultat de la requête %s %s :</h3>', $_REQUEST['method'],$_REQUEST['endpoint']);
        $es = $this->parent->get('elasticsearch');
        $this->dump($es->request($_REQUEST['method'], $_REQUEST['endpoint']));
    }

    private function dump($data) {
        // Scalaire
        if (is_scalar($data)) {
            return printf('<code>%s</code>', print_r($data, true));
        }

        // Tableaux
        if (is_array($data)) {
            // Tableaux de scalaires
            if (is_int(key($data)) && is_scalar(current($data))) {
                foreach($data as $i => $value) {
                    if ($i) echo '<br />';
                    $this->dump($value);
                }

                return;
            }
            // Tableaux d'objets
            return $this->dumph($data);
        }

        // Objet
        if (is_object($data)) {
            $t = (array) $data;
            if (count($t) < 5 && count(array_filter($t, 'is_scalar')) === count($t)) {
                foreach($data as $key => $value) {
                    printf('<em>%s : </em>', $key);
                    $this->dump($value);
                    echo '<br />';
                }
                return;
            }

            return $this->dumpv($data);
        }

        // ??
        print_r($data);
    }

    private function dumpv($data) {
        echo '<table border="0" style="border-collapse: collapse; border: 1px solid #ddd;">';
        foreach ($data as $key => $value) {
            echo "<tr><th valign='top' style='width: 100px; text-align: right;'>$key : </th><td>";
            $this->dump($value);
            echo '</td></tr>';
        }
        echo '</table>';
    }

    private function dumph($data) {
        $columns = array();
        foreach($data as $value) {
            $columns += (array) $value;
        }
        $columns = array_keys($columns);

        echo '<table border="0" style="border-collapse: collapse; border: 0px solid red; width:100%">';
        echo '<tr>';
        foreach($columns as $column) {
            echo "<th style='width: 100px;text-align: left;font-weight: normal; font-style: italic;'>$column</th>";
        }
        echo '</tr>';

        foreach($data as $value) {
            echo '<tr>';
            foreach($columns as $column) {
                echo '<td valign="top">';
                if (isset($value->$column)) {
                    $this->dump($value->$column);
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';

    }
}

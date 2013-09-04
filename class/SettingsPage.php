<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package Docalist
 * @subpackage Search
 * @author Daniel Ménard <daniel.menard@laposte.net>
 * @version SVN: $Id$
 */
namespace Docalist\Search;

use Docalist\AbstractAdminPage, Docalist\Forms\Form;
use Docalist\Data\Entity\EntityInterface;

/**
 * Options de configuration du plugin.
 */
class SettingsPage extends AbstractAdminPage {
    /**
     *
     * @var Settings
     */
    protected $settings;

    /**
     *
     * @var Indexer
     */
    protected $indexer;

    /**
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings, Indexer $indexer) {
        // @formatter:off
        parent::__construct('options-general.php',            // page parent
            __('Options Docalist Search', 'docalist-search'), // titre de page
            __('Docalist Search', 'docalist-search')          // libellé menu
        );
        // @formatter:on

        $this->settings = $settings;
        $this->indexer = $indexer;
        parent::register(); // @todo : à enlever après quand AbstractAdminPage ne sera plus un Registrable

        $this->indexer->ping();
    }

    /**
     *
     * @param Form $form
     * @param EntityInterface $part
     *
     * @return boolean Retourne true si les paramètrs ont été enregistrés.
     */
    protected function handle(Form $form, EntityInterface $part) {
        $saved = false;
        $submit = __('Enregistrer les modifications', 'docalist-search');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['reset'])) {
                $noreset = true; // ne pas réafficher le bouton reset
                foreach ($part as $key => $value) {
                    $part->$key = null;
                }

                $submit = __('Restaurer ces valeurs par défaut', 'docalist-search');
                $msg = __('Le formulaire affiche maintenant les paramètres avec leur valeur par défaut. <b>Cliquez sur le bouton "%s"</b> pour supprimer vos paramètres actuels.', 'docalist-search');
                $msg = sprintf($msg, $submit);
            } else {
                $form->bind($_POST);
                foreach ($form->data() as $key => $data) {
                    $part->$key = $data;
                }

                $this->settings->save();
                $msg = __('Vos options ont bien été enregistrées.', 'docalist-search');
                $saved = true;
            }
            printf('<div class="updated"><p>%s</p></div>', $msg);
        }

        // Définit l'url et la méthode du formulaire
        $form->attribute('action', $this->url())->attribute('method', 'post'); // @todo: charset ?

        // Ajoute la description de l'action en texte d'intro
        /* $form->description($this->description()); déjà fait par AbstractActions::run(). */

        // Ajoute un bouton "enregistrer" au formulaire
        $form->submit($submit);

        // Ajoute un bouton "reset" au formulaire
        if (!isset($noreset)) {
            // @formatter:off
            $form->button(__('Restaurer les valeurs d\'usine...', 'docalist-search'))
                 ->name('reset')
                 ->attribute('type', 'submit');
                // @todo : mettre le bouton reset au bon endroit
            // @formatter:off
        }

        $form->bind($part)->render('wordpress');

        return $saved;
    }

    /**
     * Paramètres du serveur ElasticSearch.
     */
    public function actionServerSettings() {
        $box = new Form();
        $box->input('url');
        $box->input('index');
        $box->input('timeout');

        return $this->handle($box, $this->settings->server);
//         if ($this->handle($box, $this->settings->server)) {
//             do_action('docalist_search_setup'); // crée l'index, etc.
//         }
    }

    /**
     * Est-ce que le serveur ES répond ?
     *
     * Indique si le serveur répond et teste si l'index existe.
     */
    public function actionServerStatus() {
        switch($this->indexer->ping()) {
            case 0:
                $msg = __("L'url %s ne répond pas.", 'docalist-search');
                return printf($msg,
                    $this->settings->server->url
                );
            case 1:
                $msg = __("Le serveur Elastic Search répond à l'url %s. L'index %s n'existe pas.", 'docalist-search');
                return printf($msg,
                    $this->settings->server->url,
                    $this->settings->server->index
                );
            case 2:
                $msg = __("Le serveur Elastic Search répond à l'url %s. L'index %s existe.", 'docalist-search');
                return printf($msg,
                    $this->settings->server->url,
                    $this->settings->server->index
                );
        }
    }

    /**
     * Paramètres de l'indexeur.
     */
    public function actionIndexerSettings() {
        $box = new Form();
        $box->input('bulkMaxSize');
        $box->input('bulkMaxCount');

        return $this->handle($box, $this->settings->indexer);
    }

    /**
     * Contenus à indexer.
     */
    public function actionTypes() {
        $box = new Form();
        $box->description('@todo : Attention si vous désélectionnez un type, cela le supprime de l\'index ElasticSearch. Quand vous enregistrez, tous les mappings sont mis à jour.');
        $box->checklist('types')->options($this->availableTypes());

        if ($this->handle($box, $this->settings)) {
            do_action('docalist_search_setup'); // crée l'index, les mappings, etc.
        }
    }

    /**
     * Gestion des synonymes
     *
     * todo : pouvoir définir les synonymes qu'on veut utiliser pour les champs
     *
     * autre p
     */
    // public function actionSynonyms() {}

    /**
     * Fichiers logs
     *
     * todo : logs des recherches, lors des indexations, slowlog...
     */
    // public function actionLogs() {}

    /**
     * Avancé.
     * Paramétrage des mappings
     *
     * todo : édition du json des mappings d'un type donné. Utile ?
     */
    // public function actionMappings() {}

    /**
     * Avancé.
     * Paramétrage des mots vides
     *
     * todo : à voir. Dernière version de ES, plus besoin de mots vides.
     */
    // public function actionStopwords() {}

    /**
     * Réindexer la base
     *
     * Permet de lancer une réindexation complète des collections en
     * choisissant les types de documents à réindexer.
     *
     * @param array $types Les types à réindexer
     */
    public function actionReindex($selected = null) {
        // Permet à l'utilisateur de choisir les types à réindexer
        if (empty($selected)) {
            // Parmi ceux qui sont indexés
            $types = $this->settings->types->toArray();
            $types = array_flip($types);
            $types = array_intersect_key($this->availableTypes(), $types);

            // Erreur si l'admin n'a pas encore choisit les types à indexer
            if (empty($types)) {
                //@formatter:off
                $msg = __(
                    'Avant de lancer l\'indexation de vos documents, vous devez
                    choisir les contenus qui seront disponibles dans votre moteur
                    de recherche.

                    Allez dans la page <a href="%s">"%s"</a>, choisissez vos
                    contenus puis revenez sur cette page pour lancer
                    l\'indexation.',
                    'docalist-search'
                );

                return printf($msg, //@todo : générer br dans msg
                    $this->url('Types'),
                    $this->title('Types')
                );
                // @formatter:on
            }

            // Affiche le formulaire
            //@formatter:off
            $box = new Form();
            $box->checklist('selected')
                ->label(__('Choisissez les types à réindexer', 'docalist-search'))
                ->options($types);
            $box->submit(__('Réindexer les types sélectionnés', 'docalist-search'));
            // @formatter:on

            return $box->render('wordpress');
        }

        // Installe les actions qui vont nous permettre de suivre le process
        $this->reindexUI();

        // Lance la réindexation des types sélectionnés
        $this->indexer->reindex($selected);

        echo "Réindexation terminée.";
    }

    protected $count;
    protected $startTime;

    protected function reindexUI() {
        add_action('docalist_search_before_reindex', function(array $types) {
            while(ob_get_level()) ob_end_flush();

            if (count($types) === 1) {
                $msg =__('Une collection à réindexer : %2$s.', 'docalist-search');
            } else {
                $msg =__('%1$s collections à réindexer : %2$s.', 'docalist-search');
            }
            $msg = sprintf($msg, count($types), implode(', ', $types)); // @todo afficher libellé plutôt que postype
            printf('<p>%s</p>', $msg);
            flush();
        }, 10, 1);

        add_action('docalist_search_before_reindex_type', function($type, $label) {
            printf('<h3><p>%s</p></h3>', $label);
            printf('<table border="1" style="border-collapse:collapse; table-layout:fixed; text-align: right;"><tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>',
                __('Documents', 'docalist-search'),
                __('Temps écoulé', 'docalist-search'),
                __('memory_get_usage', 'docalist-search'),
                __('memory_get_usage(true)', 'docalist-search'),
                __('memory_get_peak_usage()', 'docalist-search'),
                __('memory_get_peak_usage(true)', 'docalist-search')
            );
            flush();

            $this->startTime = microtime(true);
            $this->count = 0;
        }, 10, 2);

        add_action('docalist_search_index', function($type) {
            ++$this->count;
            if (0 === $this->count % 1000) {
                printf('<tr><td>%d</td><td>%.2f</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td></tr>',
                    $this->count,
                    microtime(true) - $this->startTime,
                    memory_get_usage(),
                    memory_get_usage(true),
                    memory_get_peak_usage(),
                    memory_get_peak_usage(true)
                );
                flush();
            }
        }, 999, 1);

        add_action('docalist_search_after_flush', function($count, $size) {
            $msg =__('Flush du cache (%d documents, %s octets)', 'docalist-search');
            $msg = sprintf($msg, $count, $size);

            printf('<tr><td colspan="6" style="text-align: center">%s</td></tr>', $msg);
            flush();
        }, 10, 2);

        add_action('docalist_search_after_reindex_type', function($type, $label, $stats) {
            printf('<tr><td>%d</td><td>%.2f</td><td>%d</td><td>%d</td><td>%d</td><td>%d</td></tr></table>',
                $this->count,
                microtime(true) - $this->startTime,
                memory_get_usage(),
                memory_get_usage(true),
                memory_get_peak_usage(),
                memory_get_peak_usage(true)
            );

            $msg =__('Terminé, %d document(s) ajouté(s) ou mis à jour, %d document(s) supprimé(s) en %.2f secondes.', 'docalist-search');
            $msg = sprintf($msg, $stats['added'], $stats['deleted'], microtime(true) - $this->startTime);
            printf('<p>%s</p>', $msg);
            var_dump($stats);
            flush();
        }, 10, 3);

        add_action('docalist_search_after_reindex', function(array $types, array $stats) {
            $msg=__('Réindexation terminée.', 'docalist-search');
            printf('<h1><p>%s</p></h1>', $msg);
            var_dump($stats);
            flush();
        }, 10, 2);
    }

    /**
     * Valide les options saisies.
     *
     * @return string Message en cas d'erreur.
     */

    /*
     * protected function validateSettings() { }
     *  todo : - tester si l'url indiquée pour le server est correcte / répond
     *  - faire une action ajax qui prend en paramètre l'url et
     *    répond true ou un message d'erreur
     *
     *  - ajouter un javascript qui fait enabled.onchange = appeller l'url et
     *    mettre un message à coté de la zone de texte (ok, pas ok).
     *
     *  - faire la même chose dès le chargement de la page (comme ça quand on
     *    va sur la page, on sait tout de suite si le serveur est ok ou pas).
     *
     *  - tester si l'index indiqué existe déjà ou pas - ne fait quelque chose
     *    que si on sait que le serveur répond
     *
     *  - faire une action ajax qui teste si l'index existe
     *
     *  - le javascript ajoute un message qui signale simplement si l'index
     *    existe ou non. Lorsqu'on installe docalist search, ça fait office de
     *    warning (attention, vous allez mettre vos données dans un index qui
     *    existe déjà). Après en routine, c'est une simple confirmation (ok,
     *    l'index que j'ai choisit existe toujours).
     */

    /**
     * Retourne la liste des types de contenus susceptibles d'être indexés.
     *
     * @return array Un tableau de la forme type => libellé du type, trié par
     * libellés.
     */
    protected function availableTypes() {
        // Récupère la liste de tous les types indexables
        $types = apply_filters('docalist_search_get_types', array());

        // Trie par label
        natcasesort($types);

        return $types;
    }

    protected function description($action = null) {
        is_null($action) && $action = $this->action();

        switch ($action) {

            case 'Index':
                //@formatter:off
                $intro = __(
                    'Docalist Search est un plugin qui permet de doter votre site
                    WordPress d\'un moteur de recherche moderne et performant.

                    Utilisez les liens ci-dessous pour paramétrer votre moteur.',
                    'docalist-search'
                );

                return sprintf($intro,
                    '',   // %1
                    '--'
                );

            case 'ServerSettings':
                //@formatter:off
                $intro = __(
                    'Pour fonctionner, Docalist Search doit pouvoir accéder à un
                    moteur de recherche <a href="%1$s">ElasticSearch</a> dans
                    lequel il va créer un index qui permettra de stocker et de
                    retrouver vos contenus.
                    ElasticSearch peut être <a href="%2$s">installé sur le serveur</a>
                    qui héberge votre site web ou bien vous pouvez faire appel à un
                    <a href="%3$s">service d\'hébergement dédié</a>.

                    Cette page vous permet de spécifier l\'url de votre moteur
                    ElasticSearch, le nom de l\'index à utiliser ainsi que d\'autres
                    paramètres.',
                    'docalist-search'
                );

                return sprintf($intro,
                    'http://www.elasticsearch.org/',                            // %1
                    'http://www.elasticsearch.org/guide/reference/setup/',      // %2
                    'https://www.google.com/search?q=elasticsearch+hosting',    // %3
                    '--'
                );
                // @formatter:on

            case 'IndexerSettings':
                //@formatter:off
                $intro = __(
                    'Lorsque vous créez ou que vous modifiez des documents en série
                    (lors d\'un import, par exemple) Docalist Search utilse un
                    buffer dans lequel il stocke les documents à mettre à jour
                    et à supprimer.
                    Ce buffer permet <a href="%1$s">d\'optimiser l\'indexation des
                    documents</a> en limitant le nombre de requêtes adressées à
                    ElasticSearch, mais il consomme de la mémoire sur votre serveur
                    et génère un délai avant que les nouveaux documents indexés
                    n\'apparaissent dans vos résultats de recherche.

                    Cette page vous permet de paramétrer le fonctionnement de ce
                    buffer et de fixer des limites sur la quantité de mémoire
                    utilisée et le nombre de documents stockés dans le buffer.
                    Dès que l\'une des deux limites est atteinte, le buffer est
                    envoyé à ElasticSearch puis est réinitialisé.',
                    'docalist-search'
                );

                return sprintf($intro,
                    'http://www.elasticsearch.org/guide/reference/api/bulk/',   // %1
                    '--'
                );
                // @formatter:on

            case 'Types':
                //@formatter:off
                $intro = __(
                    'Cette page vous permet de choisir les contenus de votre site
                    qui serons disponibles dans votre moteur de recherche.',
                    'docalist-search'
                );
                // @formatter:on

                return sprintf($intro, '',                 // %1
                '--');

            default:
                return parent::description($action);
        }
    }
}
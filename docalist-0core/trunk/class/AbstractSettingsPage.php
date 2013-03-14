<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012, 2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id: Metabox.php 447 2013-02-27 15:00:13Z
 * daniel.menard.35@gmail.com $
 */
namespace Docalist;
use Docalist\Forms\Themes, Docalist\Forms\Fields;

/**
 * Représente une metabox
 */
abstract class AbstractSettingsPage extends AbstractAdminPage {
    /**
     * @inheritdoc
     */
    protected $parentPage = 'options-general.php';

    /**
     * @var Fields Le formulaire à afficher.
     */
    protected $form;

    /**
     * @var string Nom de l'objet Settings qu'on va éditer.
     *
     * Si le plugin n'a qu'un seul objet Settings (et que la classe
     * s'appelle Settings), il n'y a pas besoin de changer cette propriété.
     */
    protected $settingsName = 'settings';

    /**
     * @var AbstractSettings L'objet Settings auquel s'applique la page.
     * Initialisé automatiquement dans register() en appellant
     * $this->plugin()->get($this->settingsName)
     */
    protected $settings;

    /**
     * @inheritdoc
     */
    public function register() {
        // Indique à WordPress le nom de notre setting
        $this->settings = $this->plugin()->get($this->settingsName);
        $id = $this->settings->id();

        //@formatter:off
        register_setting(
            $id,                  // Codex : "make $option_group match $option_name"
            $id,                  // Clé dans la table wp_options
            function($settings) { // Filtre et valide les données avant le save
                $defaults = $this->settings->defaults();
                $settings = $this->filterSettings($settings, $defaults);
                $this->validate($settings);

                return $settings;
            }
        );
        //@formatter:on

        // Ajoute la page dans WordPress
        parent::register();
    }

    /**
     * @inheritdoc
     */
    public function getAssets() {
        $assets = parent::getAssets();

        if ($this->form) {
            $assets->add(Themes::assets('wordpress'));
            $assets->add($this->form->assets());
        }

        return $assets;
    }

    /**
     * Retourne le titre de la page.
     *
     * Par défaut, la méthode retourne le label du formulaire.
     * Si aucun formulaire n'a été définit, ou si le celui-ci n'a pas de
     * label, elle retourne un titre construit à partir de l'id de la page.
     *
     * @return string
     */
    protected function pageTitle() {
        if ($this->form && $label = $this->form->label()) {
            return $label;
        }

        return parent::pageTitle();
    }

    /**
     * Valide les options de configuration saisies par l'utilisateur avant
     * que celles-ci ne soient écrites dans la base.
     *
     * Par défaut, cette méthode ne fait rien, elle est destinée à être
     * surchargée par les classes descendantes.
     *
     * @param array|null $settings Les options à valider.
     */
    protected function validate(&$settings) {
    }

    /**
     * Affiche un message d'erreur à l'utilisateur si les settings ne sont pas
     * {@link validate() valides}.
     *
     * @param string $message Le message à afficher
     */
    protected function error($message) {
        $setting = $this->settings->id();
        $msgId = count(get_settings_errors($setting)) + 1;
        add_settings_error($setting, $msgId, $message);
    }

    /**
     * Filtre les options de configuration saisies par l'utilisateur avant
     * que celles-ci ne soient écrites dans la base.
     *
     * Le filtrage consiste à :
     * - supprimer toutes les clés qui n'existent pas dans la config par défaut
     * - supprimer toutes les valeurs vides.
     * - vérifier (et caster si besoin) les valeurs pour qu'elles correspondent
     *   aux valeurs par défaut.
     * - supprimer les options dont la valeur est identique à la valeur par
     *   défaut.
     *
     * Les classes descendantes peuvent surcharger la méthode {@link validate()}
     * pour effectuer des vérifications supplémentaires.
     *
     * @param array $settings Les options de configuration à filtrer.
     * @param array $defaults Les valeurs par défaut.
     *
     * @return array
     */
    private function filterSettings(array $settings, array $defaults) {
        $debug = false;
        if ($debug) {
            echo '<hr />filter<br />';
            echo 'Settings=<pre>',  var_export($settings, true), '</pre>';
            echo 'Defaults=<pre>',  var_export($defaults, true), '</pre>';
        }

        if (empty($settings)) {
            if ($debug)
                echo "empty settings, return null<br />";
            return null;
        }

        // Si l'option est un tableau de valeurs (i.e. pas un groupe de clés)
        if (empty($defaults) || is_int(key($defaults))) {
            if ($debug)
                echo "liste de valeurs, return array_values(settings)<br />";

            return array_values($settings);
        }

        foreach ($settings as $key => $value) {
            // Vérifie que c'est une option qui existe dans $defaults
            if (!isset($defaults[$key])) {
                if ($debug)
                    echo "$key : option inexistante, unset<br />";
                unset($settings[$key]);
                continue;
            }

            $default = $defaults[$key];

            // Vérifie que l'option n'est pas vide
            if (empty($value)) {
                if ($debug)
                    echo "$key : empty, unset<br />";
                unset($settings[$key]);
                continue;
            }

            // On attend un scalaire et on nous a passé autre chose
            if (is_scalar($default) && !is_scalar($value)) {
                if ($debug)
                    echo "$key : not scalar, unset<br />";
                unset($settings[$key]);
                continue;
            }

            // On attend un tableau et on nous a passé autre chose
            if (is_array($default) && !is_array($value)) {
                if ($debug)
                    echo "$key : not array, unset<br />";
                unset($settings[$key]);
                continue;
            }

            // On attend un booléen
            if (is_bool($default)) {
                if ($debug)
                    echo "$key : cast to bool<br />";
                $settings[$key] = (bool)$value;
            }

            // On attend un entier
            elseif (is_int($default)) {
                if ($debug)
                    echo "$key : cast to int<br />";
                $settings[$key] = (int)$value;
            }

            // On attend un réel
            elseif (is_float($default)) {
                if ($debug)
                    echo "$key : cast to float<br />";
                $settings[$key] = (float)$value;
            }

            // On attend une chaine
            elseif (is_string($default)) {
                if ($debug)
                    echo "$key : cast to string<br />";
                $settings[$key] = (string)$value;
            }

            // Ignore les options qui ont la valeur par défaut
            if ($settings[$key] === $default) {
                if ($debug)
                    echo "$key : equals default, unset<br />";
                unset($settings[$key]);
                continue;
            }

            // Si la clé est un groupe d'options, valide les sous-clés
            if (is_array($settings[$key])) {
                if ($debug)
                    echo "$key : is array, filter children<blockquote>";
                $settings[$key] = $this->filterSettings($settings[$key], $default);
                if ($debug)
                    echo '</blockquote>';
                if (empty($settings[$key])) {
                    if ($debug)
                        echo "$key : empty après filterchildren, unset<br />";
                    unset($settings[$key]);
                    continue;
                }
            }
        }

        if ($debug) {
            if (empty($settings)) {
                echo "empty settings, return null<br />";
            } else {
                echo 'Settings final=<pre>',             var_export($settings, true), '</pre>';
            }
        }

        return empty($settings) ? null : $settings;
    }

    /**
     * Détermine si le formulaire doit être affiché sous forme d'onglets ou
     * sous forme d'une simple page contenant les différentes sections.
     *
     * Par défaut, les formulaires qui ne contiennent que des containers au
     * premier niveau sont affichés sous forme d'onglets, chaque groupe de
     * premier niveau devenant un onglet.
     *
     * Si le formulaire contient directement des champs, il sera affiché sous
     * la forme d'une page unique (sans onglets) et chaque containeur deviendra
     * une section au sein de cette page.
     *
     * Si votre formulaire s'affiche sous forme d'onglet et que ce n'est pas
     * ce que vous voulez, ajoutez l'attribut notab=true au formulaire pour
     * désactiver les onglets :
     *
     * <code>
     * $form->attribute('notab', true);
     * </code>
     *
     * @return bool
     */
    private function hasTabs() {
        // Attribut notab=true, on le supprime et on retourne false
        if ($this->form->attribute('notab') === true) {
            $this->form->attribute('notab', false);

            return false;
        }

        // Retourne true si tous les champs de niveau 1 sont des containers
        foreach ($this->form->fields() as $field) {
            if (!($field instanceof Fields)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Affiche la page permattant à l'utilisateur de modifier les options de
     * configuration du plugin.
     */
    public function actionIndex() {
        // Pas de formulaire, render() non surchargée : affiche un message
        if (!$this->form) {
            $msg = __('Créez un formulaire dans load() ou surchargez
                       actionIndex() dans la classe %s pour ajouter un
                       contenu à cette page.', 'docalist-core');
            printf($msg, Utils::classname($this));

            return;
        }

        $form = $this->form;

        // Description du formulaire, infos générales (au-dessus des onglets)
        if ($description = $form->description()) {
            echo '<p class="description">', $description, '</p>';
            $form->description(false);
        }

        // Charge la configuration actuelle dans le formulaire
        $form->bind($this->settings());
        $fields = $form->fields();

        // Préfixe tous les noms de champs avec le nom de Settings
        $form->name($this->settings->id());

        // Teste s'il faut générer des onglets
        if ($this->hasTabs()) {

            // Détermine l'onglet actif (?tab=xx, le premier si vide)
            $tab = isset($_GET['tab']) ? $_GET['tab'] : $fields[0]->name();

            // Génère les onglets
            echo '<h3 class="nav-tab-wrapper">';
            // h2 = plus gros
            foreach ($fields as $i => $field) {
                $name = $field->name();
                if ($tab === $name) {
                    $class = ' nav-tab-active';
                    $form = $field;
                } else {
                    $class = '';
                }

                //@formatter:off
                printf(
                    '<a href="?page=%s%s" class="nav-tab%s">%s</a>',
                    $this->id(),                            // id de la page
                    $i ? "&tab=$name" : '',                 // id de l'onglet
                    $tab == $name ? ' nav-tab-active' : '', // classe css
                    $field->label()                         // titre de l'onglet
                );
                //@formatter:on
            }
            echo '</h3>';
        }

        // Début du formulaire
        echo '<form method="post" action="options.php">';

        // Affiche les champs de l'onglet en cours
        $form->label(false)->render('wordpress');

        // Génère le bouton "enregistrer les modifications"
        submit_button();

        // Génère les input hidden nécessaire (nonce, referrer, page, etc.)
        settings_fields($this->settings->id());

        // Ferme le formulaire et le wrapper
        echo '</form>';
        //        echo '</div>';

        // Un petit script qui fait disparaitre les erreurs au bout d'un moment
        // Les message disparaissent les uns après les autres (3 sec par msg).
        //@formatter:off
        echo
            '<script type="text/javascript">' .
            'if(typeof jQuery!="undefined") {' .
            '    jQuery(document).ready(function($){' .
            '        $(".settings-error").each(function(i){' .
            '            $(this).delay((i+1)*3000).slideUp("slow");'.
            '        });' .
            '    });' .
            '}' .
            '</script>';
        //@formatter:off
    }

    /**
     * Retourne l'identifiant unique de ce la page.
     *
     * On surcharge pour éviter d'avoir des noms de page à rallonge. En général
     * on n'a qu'une seule page de settings par plugin, donc on reprend comme
     * nom de page le nom du settings auquel appartient la page.
     * S'il y a plusieurs pages dans l'objet Settings, on retourne l'id()
     * standard.
     * Enfin, l'utilisateur peut toujours spécifier explictement un $id dans
     * se classe.
     *
     * @return string
     */
    public function id() {
        return isset($this->id) ? $this->id : $this->settings->id();
    }

}

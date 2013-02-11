<?php
    use Docalist\Forms\Form, Docalist\Forms\Themes;

    // émulation wp
    define('WP_DEBUG', true);

    // charge docalist-form
    require __DIR__ . '/../src/autoload.php';

    $_GET += array(
        'file' => 'form1',
        'theme' => 'default',
        'options' => array(),
    );

    // Si un nom de formulaire a été indiqué, on le charge
    $file = $_GET['file']; // nom du formulaire
    $theme = $_GET['theme']; // le thème à utiliser pour le rendu
    $options = array_flip($_GET['options']); // options
    $form = null; // l'objet formulaire
    $path = null; // son path
    $error = null; // message d'erreur
    $assets = array(); // les css et les js requis par le formulaire
    $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

    $path = __DIR__ . '/forms/' . $file . '.php';

    if (! file_exists($path))
        die("Le formulaire '$form' indiqué en paramètre n'existe pas.");

    // Charge le formulaire
    $form = require ($path);
    $source = file_get_contents($path);

    // Prépare le rendu du formulaire, fait le bind, détermine les assets
    if ($isPost) $form->bind($_POST);
    $form->prepare($theme);
    $assets = $form->assets($theme);

?><!DOCTYPE html>
<html>
<head>
    <meta charset=utf-8 />
    <title>Outil pour tester les formulaires de Docalist-Form</title>
    <!--[if IE]>
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    <?php assets($assets, 'top') ?>
    <script src="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.js" type="text/javascript"></script>
    <link href="http://google-code-prettify.googlecode.com/svn/trunk/src/prettify.css" type="text/css" rel="stylesheet" />
    <link href="docalist-forms-tests.css" type="text/css" rel="stylesheet" />
</head>
<body onload="prettyPrint()">
    <div class="page">
        <h1>Outil pour tester les formulaires de Docalist-Form</h1>

        <ul class="tabs">
            <li>
                <a href="#render" data-toggle="tab">Formulaire</a>
            </li>

            <li>
                <a href="#source" data-toggle="tab">Code source PHP</a>
            </li>

            <li>
                <a href="#html" data-toggle="tab">Code html généré</a>
            </li>

            <li>
                <a href="#assets" data-toggle="tab">Assets</a>
            </li>

            <li>
                <a href="#dump" data-toggle="tab">Dump</a>
            </li>

            <?php if ($isPost): ?>
            <li>
                <a href="#data" data-toggle="tab">$_POST</a>
            </li>
            <?php endif ?>
        </ul>

        <div class="content">
            <div id="render" class="well">
                <?php
                    ob_start();
                    $form->render($theme);
                    $html = ob_get_flush();
                ?>
            </div>

            <div id="source">
                <p>
                    Voici le code php qui a été utilisé pour créer ce
                    formulaire :
                </p>
                <?php prettyPrint($source) ?>
            </div>

            <div id="html">
                <p>
                    Voici le code html généré :
                </p>
                <?php
                    if (isset($options['prettyhtml'])) {
                        $dom = new DOMDocument();
                        $dom->preserveWhiteSpace = false;
                        $dom->formatOutput = true;
                        $dom->loadXml($html);
                        $html = $dom->saveXml($dom->documentElement);
                    }
                    prettyPrint($html);
                ?>
            </div>

            <div id="assets">
                <p>
                    Voici la liste des assets (fichiers javascript et feuilles de style CSS)
                    qui sont déclarées par ce formulaire et/ou par le thème utilisé:
                </p>
                <?php dumpArray($assets, '// Tableau retourné par $form->assets()') ?>
            </div>

            <div id="dump">
                <p>
                    Voici un dump du formulaire tel qu'il est stocké en mémoire :
                </p>
                <?php dumpArray($form->toArray(true), '// Tableau retourné par $form->toArray(true)') ?>
            </div>

            <?php if ($isPost): ?>
            <div id="data">
                <p>
                    Voici les données qui ont été "envoyées" par le formulaire
                    et qui ont été transmises à php dans la variable $_POST :
                </p>
                <?php dumpArray($_POST, '// var_export($_POST)') ?>
            </div>
            <?php endif ?>
        </div>

        <!--Sidebar -->
        <div class="sidebar">
            <?php choose() ?>
        </div>
    </div>

    <?php assets($assets, 'bottom') ?>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js" type="text/javascript"></script>
    <script src="docalist-forms-tests.js" type="text/javascript"></script>

</body>
</html>
<?php

function choose() {
    // Crée la liste des formulaires dispos
    $files = glob(__DIR__ . '/forms/*.php', GLOB_NOSORT);
    foreach($files as &$file) $file = basename($file, '.php');

    $form = new Form('', 'get');
    $form->attribute('class', 'form-horizontal');

    $form->select('file')
         ->label('Formulaire en cours :')
         ->options($files)
         ->multiple(true)
         ->attribute('size', 10);

    $form->select('theme')
         ->label('Thème en cours :')
         ->options(Themes::all())
         ->multiple(true)
         ->attribute('size', 3);

    $form->checklist('options')
         ->label('Options')
         ->options(array('prettyhtml'=>'indenter le code html'));

    $form->submit('Tester ce formulaire »»»')->attribute('class', 'btn btn-primary');

    $form->bind($_GET)->render();
}

function assets($assets, $pos) {
    foreach($assets as $asset) {
        if ($asset['position'] !== $pos) continue;

        extract($asset);

        if (empty($src)) {
            switch($name) {
                case 'jquery':
                    $src = 'http://code.jquery.com/jquery-1.9.1.min.js';
                    break;

                default:
                    die("Je ne sais pas quelle url mettre pour l'asset $type $name\n");
            }
        }

        if ($condition) echo "<!--[if $condition]>\n";
        if ($type === 'css') {
            printf('<link rel="stylesheet" id="%s" href="%s" type="text/css" media="%s" />', $name, $src, $media);
        } elseif ($type === 'js') {
            printf('<script id="%s" type="text/javascript" src="%s"></script>', $name, $src);
        }
        if ($condition) echo "<![endif]-->\n";

    }

}

function prettyPrint($h, $lang='php') {
    echo '<pre class="prettyprint lang-', $lang, '">';
    echo htmlspecialchars($h);
    echo '</pre>';
    return;
    $h = preg_replace ('~\s*array\s+\(~', ' array(', $h);
    $h = preg_replace ('~\d+\s*=>\s*~', '', $h);
    $h = highlight_string($h, true);
    $h = substr($h, 6) ; // <code>
    $h = substr($h, 0, -7); // </code>
//    $h = strtr($h, "\n\r", ' ');
    $h = preg_replace('~\n\s*~', '', $h);
//    $h = trim($h, "\n\r ");
    echo $h;
}

function dumpArray(array $array, $name = '') {
    prettyPrint("<?php $name\nreturn " . var_export($array, true));
}

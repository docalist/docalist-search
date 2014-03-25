<?php
// Initialisation
$table = $this->table();
$valueField = $this->valueField();
$labelField = $this->labelField();

/*
 * Principe pour fournir la valeur initiale du champ à selectize().
 * 1. On récupère le contenu du champ
 * 2. On interroge la table pour obtenir le libellé de chacun des articles en
 *    faisant une requête SELECT code,label WHERE code IN (articles)
 * 3. Crée les options du select
 * 4. On initialise les options du select avec les résultas obtenus
 */

// 1. Récupère le contenu actuel du champ
if ($this->data instanceof Docalist\Data\Entity\SchemaBasedObjectInterface) {
    // par exemple si on a passé un objet "Settings" ou Property comme valeur actuelle du champ
    $data = $this->data->toArray();
} else {
    $data = (array)$this->data;
}

// Le nom complet de la table est de la forme type:table
list($type, $name) = explode(':', $table);

// Si le lookup porte sur un index, on a directement les données
if ($type === 'index') {
  $options = $data;
}

// Sinon, il faut convertir les codes présents dans le champ en libellés
else {
    // 2. Interroge la table pour obtenir le libellé de chacun des articles

    // Construit la clause WHERE ... IN (...)
    $options = [];
    foreach ($data as $option) {
        $options[]= "'" . strtr($option, "'", "\\'") . "'";
    }
    $where = $valueField . ' IN (' . implode(',', $options) . ')';

    // On veut une réponse de la forme $valueField => $labelField pour le select
    $what = "$valueField,$labelField";

    // Recherche tous les articles
    $results = docalist('table-manager')->get($name)->search($what, $where);

    // 3. Construit le tableau d'options, en respectant l'ordre initial des articles
    $options = [];
    foreach($data as $key) {
        // article trouvé
        if (isset($results[$key])) {
            $options[$key] = $results[$key];
        }

        // article non trouvé
        else {
            $options[$key] = 'Invalide : ' . $key;
        }
    }
}

// 4. Initialise les options du Select
$this->options = $options;

// Garantit que le contrôle a un ID, pour y accèder dans le tag <script>
$this->generateId();

// Génère le select
$args['data-table'] = $table;
$valueField !== 'code' && $args['data-valueField'] = $valueField;
$labelField !== 'label' && $args['data-labelField'] = $labelField;

// Génère le script inline qui intialise selectize()
$this->parentBlock($args);
    $writer->startElement('script');
    $writer->writeAttribute('type', 'text/javascript'); // pas nécessaire en html5
    $writer->writeAttribute('class', 'do-not-clone'); // indique à deocalist-forms.js qu'il ne faut pas cloner cet élément

    $id = $this->attribute('id');
    $writer->writeRaw("jQuery('#$id').tableLookup();");
$writer->fullEndElement();
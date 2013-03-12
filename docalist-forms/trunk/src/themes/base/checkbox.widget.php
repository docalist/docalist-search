<?php
/*
 * Si la case est cochée, il faut permettre à l'utilisateur de la décocher.
 *
 * Comme une checkbox non-cochée n'est pas transmise par le navigateur, on
 * génère un input hidden avec value="" juste avant la checkbox.
 *
 * Du coup, le navigateur va systématiquement transmettre dans les données du
 * formulaire : la value du hidden et, si la case est cochée, la value de la
 * checkbox (et il le fait dans cet ordre).
 *
 * Dans Php, seule la dernière valeur sera récupérée (sauf si répétable).
 *
 * Valeur initiale      Utilisateur         Valeur dans $_POST
 * Cochée               Laisse cochée       La value de la checkbox
 *                      Décoche la case     La value du hidden
 *
 * Non cochée           Laisse non cochée   La value du hidden
 *                      Coche la case       La value de la checkbox
 *
 * Si on était sur que le code php gère de la même façon les cas
 * "$POST = value du hidden" et "aucune value dans $POST", on pourrait optimiser
 * et ne générer le hidden que lorsque la checkbox est initialement cochée.
 *
 * Mais c'est problématique car alors on ne peut plus distinguer entre
 * "checkbox à false" et "checkbox non transmise" (null).
 *
 * Du coup, on génère systématiquement le hidden !
 */

// Génère un hidden qui sert à transmettre '' lorsque la case est décochée
$writer->startElement('input');
$writer->writeAttribute('type', 'hidden');
$writer->writeAttribute('name', $this->controlName());
$writer->writeAttribute('value', '');
$writer->endElement();

// Si la case est initiallement cochée, génère un attribut checked="checked"
if ($this->data) {
    $args['checked'] = 'checked';
}

// Génère un input standard
$this->parentBlock($args);
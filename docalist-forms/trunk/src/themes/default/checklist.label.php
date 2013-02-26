<?php
// le label d'une checklist ne peut pas avoir d'attribut for
// (on ne saurait pas à quelle checkbox le rattacher)
$this->parentBlock($args + array('for' => false));

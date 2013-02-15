<?php
// source : http://xahlee.info/js/html5_non-closing_tag.html
$selfClosing = 'area,base,br,col,command,embed,hr,img,input,keygen,link,metaparam,source,track,wbr,';
// virgule à la fin

// On a un nom de tag
if ($this->tag) {
    $writer->startElement($this->tag);
    $this->name && $writer->writeAttribute('name', $this->controlName());
    $this->render($theme, 'attributes', $args);

    $writer->text($this->content);

    if (empty($this->content) && false !== strpos($selfClosing, $this->name . ',')) {
        $writer->endElement();
    } else {
        $writer->fullEndElement();
    }
}

// Pas de tag, affiche data comme un simple bloc de texte, ignore les attributs
else {
    $writer->text($this->content);
}
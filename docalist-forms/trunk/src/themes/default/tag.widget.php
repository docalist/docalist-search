<?php
// source : http://xahlee.info/js/html5_non-closing_tag.html
$selfClosing = 'area,base,br,col,command,embed,hr,img,input,keygen,link,metaparam,source,track,wbr,';
// virgule à la fin

// On a un nom de tag
if ($this->tag) {
    echo '<', $this->tag, $this->render($theme, 'attributes');

    if (empty($this->data) && false !== strpos($selfClosing, $this->name . ',')) {
        echo '/>';
    } else {
        echo '>', $this->data, '</', $this->name, '>';
    }
}

// Pas de tag, affiche data comme un simple bloc de texte, ignore les attributs
else {
    echo $this->tag;
}
<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2013-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\Aggregation\Standard;

use Docalist\Search\Aggregation\Bucket\TermsAggregation;

/**
 * Une agrégation standard de type "terms" sur le champ "status" qui retourne le nombre de documents pour chacun
 * des statuts WordPress trouvés.
 */
class TermsStatus extends TermsAggregation
{
    public function __construct()
    {
        parent::__construct('status', ['size' => 1000]);
        $this->setTitle(__('Statut de publication', 'docalist-search'));
    }

    public function getBucketLabel($bucket)
    {
        if ($status = get_post_status_object($bucket->key)) {
            return $status->label;
        }

        return $bucket->key;
    }
}

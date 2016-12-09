<?php
/**
 * This file is part of the "Docalist Search" plugin.
 *
 * Copyright (C) 2011-2016 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Search
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Search\QueryParser4;

class MultiRuntime extends Runtime
{
    protected $runtimes;

    public function __construct(array $runtimes)
    {
        $this->runtimes = $runtimes;
    }

    public function doNumber($number)
    {
        $result = [];

        foreach($this->runtimes as $key => $runtime)
        {
            $result[$key] = $runtime->doNumber($number);
        }

        return $result;
    }


    public function __call($name, $arguments)
    {
        $result = [];

        foreach($this->runtimes as $key => $runtime)
        {
            $parameters = [];
            foreach($arguments as $arg) {
                $parameters[] = $arg[$key];
            }
//            echo "call $key::$name(", var_export($parameters, true), ')<br />';
            $result[$key] = call_user_func_array([$runtime, $name], $parameters);
        }
//echo '-> result = ', var_export($result, true), '<br />';
        return $result;
    }
}

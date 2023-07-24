<?php

/**
 * This file is part of Blitz PHP framework - Inertia Adapter.
 *
 * (c) 2023 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Inertia;

use Closure;

class Helpers
{
    public static function arrayOnly(array $array, $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * @param null $default
     *
     * @return mixed
     */
    public static function arrayGet($array, $key, $default = null)
    {
        if (! is_array($array)) {
            return self::closureCall($default);
        }

        if (null === $key) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? self::closureCall($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return self::closureCall($default);
            }
        }

        return $array;
    }

    /**
     * @return array|mixed
     */
    public static function arraySet(&$array, $key, $value)
    {
        if (null === $key) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // Si la clé n'existe pas à cette profondeur, nous allons simplement créer un tableau vide 
            // pour contenir la valeur suivante, nous permettant de créer les tableaux pour contenir les valeurs finales 
            // à la bonne profondeur. Ensuite, nous continuerons à creuser dans le tableau.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * @return mixed
     */
    public static function closureCall($closure)
    {
        return $closure instanceof Closure ? $closure() : $closure;
    }
}

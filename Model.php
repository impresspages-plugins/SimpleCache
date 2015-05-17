<?php
/**
 * @package   ImpressPages
 */


/**
 * Created by PhpStorm.
 * User: mangirdas
 * Date: 14.11.12
 * Time: 22.06
 */

namespace Plugin\SimpleCache;


class Model
{
    protected static $model;
    protected function __construct()
    {

    }

    /**
     * @return ModelInterface
     */
    public static function instance()
    {
        if (!self::$model) {
            $engine = ipConfig()->get('SimpleCache_engine');
            if (!$engine) {
                $engine = ipGetOption('SimpleCache.engine', 'Files');
            }
            if ($engine == 'Memcached') {
                self::$model = new ModelMemcached();
            } else {
                self::$model = new ModelFile();
            }
        }
        return self::$model;
    }



}

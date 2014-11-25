<?php
/**
 * @package   ImpressPages
 */


/**
 * Created by PhpStorm.
 * User: mangirdas
 * Date: 14.11.14
 * Time: 11.18
 */

namespace Plugin\SimpleCache;


class Service
{
    public static function invalidatePage($pageId)
    {
        $model = Model::instance();
        $model->clearPageCache($pageId);
    }

    public static function invalidateAll()
    {
        $model = Model::instance();
        $model->clearCache();
    }
}

<?php
/**
 * @package   ImpressPages
 */


/**
 * Created by PhpStorm.
 * User: maskas
 * Date: 15.5.17
 * Time: 23.29
 */

namespace Plugin\SimpleCache;


interface CacheInterface {

    public function getPageCache($requestUri);

    public function setPageCache($pageId, $requestUri, $html);

    public function clearPageCache($pageId);

    public function clearCache();


}

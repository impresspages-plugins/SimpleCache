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
    protected function __construct()
    {

    }

    /**
     * @return Model
     */
    public static function instance()
    {
        return new Model();
    }


    public function getPageCache($requestUri)
    {
        $cacheFile = $this->getCacheFile($requestUri);
        if (!is_file($cacheFile)) {
            return;
        }
        $cache = file_get_contents($cacheFile);
        $cache = str_replace('{SimpleCache_securityToken}', ipSecurityToken(), $cache);
        return $cache;
    }

    public function setPageCache($pageId, $requestUri, $html)
    {
        if (!is_dir($this->cacheDir())) {
            mkdir($this->cacheDir());
        }

        //replace actual security token with placeholder
        $html = str_replace(ipSecurityToken(), "{SimpleCache_securityToken}", $html);

        $cacheFileName = $this->cachedFileName($requestUri);
        file_put_contents($this->cacheDir() . $cacheFileName, $html);

        //collect all cache files related to the same page. May occur if one page is accessed with several GET params.
        $pageStorage = ipPageStorage($pageId);

        $pageCaches = $pageStorage->get('simple_cache');
        if (!is_array($pageCaches)) {
            $pageCaches = array();
        }
        $pageCaches[] = $cacheFileName;

        $pageStorage->set('simple_cache', $pageCaches);
    }

    public function clearPageCache($pageId)
    {
        $pageStorage = ipPageStorage($pageId);
        $pageCaches = $pageStorage->get('simple_cache');
        if (!is_array($pageCaches)) {
            return;
        }
        foreach($pageCaches as $cache) {
            $cacheFile = $this->cacheDir() . $cache;
            if (is_file($cacheFile)) {
                unlink($cacheFile);
            }
        }
        $pageStorage->set('simple_cache', array());
    }

    public function clearCache()
    {
        $files = scandir(self::cacheDir());
        foreach($files as $file) {
            if (!preg_match('/^cache_/', $file)) {
                continue;
            }
            if (!is_file($this->cacheDir() . $file)) {
                continue;
            }
            unlink($this->cacheDir() . $file);
        }
        ipDb()->delete('page_storage', array('key' => 'simple_cache'));
    }


    protected function getCacheFile($requestUri)
    {
        return $this->cacheDir() . $this->cachedFileName($requestUri);
    }

    protected function cachedFileName($requestUri)
    {
        return 'cache_' . md5($requestUri);
    }

    protected function cacheDir()
    {
        return ipFile('file/secure/');
    }
}

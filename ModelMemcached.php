<?php
/**
 * @package   ImpressPages
 */


/**
 * Created by PhpStorm.
 * User: maskas
 * Date: 15.5.17
 * Time: 23.30
 */

namespace Plugin\SimpleCache;



class ModelMemcached implements CacheInterface
{
    protected $m;
    protected $ns;

    public function __construct()
    {
        $m = new \Memcached();
        $m->addServer('localhost', 11211);
        $this->m = $m;
        $this->ns = $m->get('ns');
        if ($this->ns == '') {
            $m->set('ns', 1);
            $this->ns = $m->get('ns');
        }
    }

    public function getPageCache($requestUri)
    {
        $cacheFile = $this->getCacheFile($requestUri);
        $cache = $this->m->get($cacheFile);
        if (!$cache) {
            return;
        }
        $cache = str_replace('{SimpleCache_securityToken}', ipSecurityToken(), $cache);
        return $cache;
    }

    public function setPageCache($pageId, $requestUri, $html)
    {
        $pageStorage = ipPageStorage($pageId);

        $disabled = ipStorage()->get('SimpleCache', 'isClearingInProcess') > time();
        if ($disabled) {
            return false;
        }

        //replace actual security token with placeholder
        $html = str_replace(ipSecurityToken(), "{SimpleCache_securityToken}", $html);

        $cacheFileName = $this->cachedFileName($requestUri);
        $this->m->set($this->cacheDir() . $cacheFileName, $html);

        //collect all cache files related to the same page. May occur if one page is accessed with several GET params.

        $pageCaches = $pageStorage->get('simple_cache');
        if (!is_array($pageCaches)) {
            $pageCaches = array();
        }
        $pageCaches[] = $cacheFileName;

        $pageStorage->set('simple_cache', $pageCaches);

        return true;
    }

    public function clearPageCache($pageId)
    {
        $pageStorage = ipPageStorage($pageId);
        ipStorage()->set('SimpleCache', 'isClearingInProcess', time() + 60);  //we have to disable cache during cleanup time. Otherwise inconsistant cache files may be created during cleanup.
        $pageCaches = $pageStorage->get('simple_cache');
        if (!is_array($pageCaches)) {
            return;
        }
        foreach($pageCaches as $cache) {
            $cacheFile = $this->cacheDir() . $cache;
            $this->m->delete($cacheFile);
        }
        $pageStorage->set('simple_cache', array());
        ipStorage()->remove('SimpleCache', 'isClearingInProcess');
    }

    public function clearCache()
    {
        $this->ns++;
        $this->m->set('ns', $this->ns);
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
        return $this->ns;
    }
}

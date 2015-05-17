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



class ModelFile implements CacheInterface
{
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
        $pageStorage = ipPageStorage($pageId);

        $disabled = ipStorage()->get('SimpleCache', 'isClearingInProcess') > time();
        if ($disabled) {
            return false;
        }

        if (!is_dir($this->cacheDir())) {
            mkdir($this->cacheDir());
        }

        //replace actual security token with placeholder
        $html = str_replace(ipSecurityToken(), "{SimpleCache_securityToken}", $html);

        $cacheFileName = $this->cachedFileName($requestUri);
        file_put_contents($this->cacheDir() . $cacheFileName, $html);

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
            if (is_file($cacheFile)) {
                unlink($cacheFile);
            }
        }
        $pageStorage->set('simple_cache', array());
        ipStorage()->remove('SimpleCache', 'isClearingInProcess');
    }

    public function clearCache()
    {
        ipStorage()->set('SimpleCache', 'isClearingInProcess', time() + 60);  //we have to disable cache during cleanup time. Otherwise inconsistant cache files may be created during cleanup.

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
        ipStorage()->remove('SimpleCache', 'isClearingInProcess');

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

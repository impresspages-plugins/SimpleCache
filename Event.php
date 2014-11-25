<?php
/**
 * @package   ImpressPages
 */


/**
 * Created by PhpStorm.
 * User: mangirdas
 * Date: 14.11.12
 * Time: 20.33
 */

namespace Plugin\SimpleCache;


class Event
{
    /**
     * Display cached page if available
     */
    public static function ipInitFinished_1()
    {
        //don't use caching if admin is logged in
        if (ipAdminId()) {
            return;
        };

        $model = Model::instance();
        $cache = $model->getPageCache($_SERVER['REQUEST_URI']);


        if ($cache) {
            echo $cache;
            exit;
        }

    }

    public static function ipPageAdded($info) {
        $model = Model::instance();
        $model->clearCache();
    }

    public static function ipPageUpdated($info)
    {
        $pageId = $info['id'];

        $page = ipPage($pageId);
        if (!$page) {
            //should never happen
            return;
        }


        $globalKeys = array('title' => 1, 'isVisible' => 1, 'isSecured' => 1, 'isDisabled' => 1, 'isBlank' => 1, 'urlPath' => 1);
        $existingValues = array(
            'title' => $page->gettitle(),
            'isVisible' => $page->isVisible(),
            'isSecured' => $page->isSecured(),
            'isDisabled' => $page->isdisabled(),
            'isBlank' => $page->isBlank(),
            'urlPath' => $page->getUrlPath()
        );
        $intersect = array_intersect_key($globalKeys, $info);

        $clearAllCache = false;
        foreach($intersect as $key => $value) {
            if ($existingValues[$key] != $info[$key]) {
                $clearAllCache = true;
            }
        }

        $model = Model::instance();
        if ($clearAllCache) {
            $model->clearCache();
        } else {
            $model->clearPageCache($pageId);
        }
    }

    public static function ipPageMarkedAsDeleted($info)
    {
        $model = Model::instance();
        $model->clearCache();
    }

    /**
     * Store the response in the file
     * @param $info
     */
    public static function ipBeforeResponseSent_1000($info)
    {
        if (ipAdminId()) {
            return;
        };
        $page = ipContent()->getcurrentPage();
        if (!$page) {
            return;
        }
        $response = $info['response'];
        if (!is_object($response) || !method_exists($response, 'execute')) {
            return;
        }
        $html = $response->execute()->getContent();

        $model = Model::instance();
        $model->setPageCache($page->getId(), $_SERVER['REQUEST_URI'], $html);
    }


    public static function ipCacheClear()
    {
        $model = Model::instance();
        $model->clearCache();
    }

}

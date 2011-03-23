<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Plugins\Articles;

import('core.types');
import('core.mapping');
import('core.exceptions');
import('core.utils.env');
import('3rdparty.markdown');

class Article extends \Core\Mapped {
    protected $_storage;
}

class ArticleMapper extends \Core\Mapper {
    public function create_object($data) {
        try {
            $mc = new \Core\Backend\MemcachedContainer();
            $m = $mc->get_backend();
            $m_enable = True;
        } catch(\Core\Backend\MemcachedNotLoadedError $e) {}
        $data = \Core\Dict::create($data);
        $data->posted_on = new \DateTime($data->posted_on);
        if(strlen($data->custom_url) > 0) {
            $data->seo_title = $data->custom_url;
        } else {
            $data->seo_title = strtolower(str_replace(' ', '-', $data->title));        
        }
        $data->preview = substr(strip_tags($data->body), 0, 440);
        $key = sprintf("site:%s:article:%s:body", \Core\Utils\Env::site_name(), $data['id']);
        if($m_enable) {
            $body = $m->get($key);
        } else {
            $body = False;
        }
        if(!$body) {
            $data['body'] = Markdown($data['body']);
            if($m_enable) {
                $m->set($key, $data['body'], 60);        
            }
        } else {
            $data['body'] = $body;
        }
        return Article::create($data);
    }

    public function get_latest_articles() {
        $items = $this->_storage->fetch(new \Core\Dict(array(
                "order" => new \Core\Order('posted_on', 'desc')
        )));
        return Article::mapper()
            ->get_list($items);
    }

    public function get_article($id) {
        return Article::container()
            ->get_by_id($id);
    }
}

class ArticleContainer extends \Core\MappedContainer {}
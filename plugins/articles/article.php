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
import('core.plugin');
import('core.mapping');
import('core.containment');
import('core.exceptions');
import('core.utils.env');



class Plugin extends \Core\Plugin {
    public static function plugin_name() {
        return 'articles';
    }
}

class Article extends \Core\Mapped {
    public static $fields = array("title", "body", "posted_on", "author", "custom_url");
    protected $_storage;
    public function validation() {
        return array(
            'title' => 'default'
        );
    }

    public function form_values() {
        $this->form_posted_on = $this->posted_on;
        $this->posted_on = new \DateTime($this->posted_on);
        $this->form_body = $this->body;
        if(extension_loaded('discount')) {
            $md = \MarkdownDocument::createFromString($this->body);
            $md->compile();
            $this->body = $md->getHtml();
        } else {            
            import('3rdparty.markdown');
            $this->body = Markdown($this->body);
        }
        return $this;
    }
}

class ArticleMapper extends \Core\Mapper {
    public function create_object($data) {
        try {
            $mc = new \Core\Backend\MemcachedContainer();
            $m = $mc->get_backend();
            $m_enable = True;
        } catch(\Core\Backend\MemcachedNotLoadedError $e) {}
        $data = \Core\Dict::create($data);
        if(strlen($data->custom_url) > 0) {
            $data->seo_title = $data->custom_url;
        } else {
            $data->seo_title = strtolower(str_replace(' ', '-', $data->title));        
        }
        $data->preview = substr(strip_tags($data->body), 0, 440);
        $key = sprintf("site:%s:article:%s:body", \Core\Utils\Env::site_name(), $data['id']);

        $a = Article::create($data)
            ->form_values();
        return $a;
        
    }
    public function get_latest_articles() {
        $items = $this->_storage->fetch(array(
                "order" => new \Core\Order('posted_on', 'desc')
        ));
        return Article::mapper()
            ->get_list($items);
    }

    public function get_article($id) {
        $v = Article::container();
        $article = Article::container()
            ->get_by_id($id);
        if(!$article) {
            throw new ArticleNotFoundError();
        }
        return $article;
    }
}

class ArticleNotFoundError extends \Core\StandardError {}
class ArticleContainer extends \Core\MappedContainer {}
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


class Article extends \Core\Mapped {
    protected $_storage;
}

class ArticleMapper extends \Core\Mapper {
    public function create_object($data) {
        $data = \Core\Dict::create($data);
        $data->posted_on = new \DateTime($data->posted_on);
        if(strlen($data->custom_url) > 0) {
            $data->seo_title = $data->custom_url;
        } else {
            $data->seo_title = strtolower(str_replace(' ', '-', $data->title));        
        }
        $data->preview = substr(strip_tags($data->body), 0, 140);
        return Article::create($data);
    }

    public function get_latest_articles() {
        return Article::mapper()
            ->attach_storage($this->_storage)
            ->get_list(new \Core\Dict(array(
                "order" => new \Core\Order('posted_on', 'desc')
            )));
    }

    public function get_article($id) {
        $articles = Article::mapper()
            ->attach_storage($this->_storage)
            ->get_list(new \Core\Dict(array(
                "filters" => new \Core\Li(
                    new \Core\Filter("id", $id)
                )
            )));
        return $articles[0];
    }
}
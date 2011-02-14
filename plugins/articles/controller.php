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

import('plugins.articles.article');
import('core.controller');

abstract class Controller extends \Core\Controller {
    protected $_storage;
    protected function _init_storage() {
        $this->_storage  = \Core\Storage::container()
            ->get_storage('Article');
    }

    protected function _get_latest_articles() {
        if(!$this->_storage instanceof \Core\Storage) {
            throw new \Core\Error('Article storage not attached.');        
        }
        $articles = \Plugins\Articles\Article::mapper()
                        ->attach_storage($this->_storage)
                        ->get_list(new \Core\Dict(array(
                            "order" => new \Core\Order('posted_on', 'desc')
                        )));
        return $articles;
    }
}
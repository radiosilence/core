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

    public function __construct($args) {
        parent::__construct($args);
        if(!$this->_storage instanceof \Core\Storage) {
            $this->_init_storage();
        }
    }
    protected function _init_storage() {
        $this->_storage  = \Core\Storage::container()
            ->get_storage('Article');
    }

    protected function _get_latest_articles() {
        return Article::mapper()
            ->attach_storage($this->_storage)
            ->get_list(new \Core\Dict(array(
                "order" => new \Core\Order('posted_on', 'desc')
            )));
    }

    protected function _get_article($id) {
        return Article::mapper()
            ->attach_storage($this->_storage)
            ->get_list(new \Core\Dict(array(
                "filters" => new \Core\Li(
                    new \Core\Filter("id", $id)
                )
            )));
    }
}
<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Session\RemoteStorage;

import('core.session.remote_storage.exceptions');
import('core.session.interfaces');
import('core.dependency');
import('core.types');

\Core\Dependency::require_classes('\PDO');
\Core\Dependency::require_functions('json_encode','json_decode');


class PDO extends \Core\Dict implements \Core\Session\RemoteStorage {
    /**
     * Untrusted session details.
     */
    private $untrusted = array();
    /**
     * Actual trusted session details.
     */
    private $actual = array();
    /**
     * Found session
     */
    private $found_session;
    
    /**
     * Remote address
     */
    private $remote_addr;
    
    protected $pdo;

    public function attach_pdo(\PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Set remote address.
     */
    public function set_remote_addr($remote_addr) {
        $this->remote_addr = $remote_addr;
        return $this;
    }

    public function set_data($data) {
        $this->__data__ = $data;
    }

    /**
     * Inserts a new session into the database.
     * @param integer $user_id
     * @return boolean success
     */
    public function add($actual) {
        $sth = $this->pdo->prepare("
            INSERT INTO sessions (
              sid, tok, remote_addr, data
            )
            VALUES (
              :sid, :tok, :remote_addr, :data

            )
        ");
        $current = new \DateTime();
        $ok = $sth->execute(array(
            ":sid" => $actual['sid'],
            ":tok" => $actual['tok'],
            ":remote_addr" => $this->remote_addr,
            ":data" => json_encode($this->__data__)
        ));
        $this->actual = $actual;
    }
 
    /**
     * Find a matching sid/tok/IP in the database
     */
    public function load($untrusted) {
        $this->untrusted = $untrusted;
        $this->find_session();
        $this->actual = $untrusted;
        $this->decode_found_data();
    }

    /**
     * Makes the session in the database have the current data.
     */
    public function save() {
        $sth = $this->pdo->prepare("
            UPDATE sessions
            SET data = :data
            WHERE sid = :sid
        ");
        $current = new \DateTime();
        $ok = $sth->execute(array(
            ":data" => json_encode($this->__data__),
            ":sid" => $this->actual['sid']
        ));
    }

    /**
     * Destroys sid in database
     */
    public function destroy() { 
        $sth = $this->pdo->prepare("
            DELETE FROM sessions
            WHERE sid = :sid
            AND remote_addr = :remote_addr
        ");
        
        $ok = $sth->execute(array(
            ":sid" => $this->actual['sid'],
            ":remote_addr" => $this->remote_addr
        ));
    }

    private function find_session() {
         $sth = $this->pdo->prepare("
            SELECT sid, tok, data, remote_addr
            FROM sessions
            WHERE sid = :sid
            AND tok = :tok
            AND remote_addr = :remote_addr
            LIMIT 1
        ");
        $ok = $sth->execute(array(
            ":sid" => $this->untrusted['sid'],
            ":tok" => $this->untrusted['tok'],
            ":remote_addr" => $this->remote_addr
        ));
        if ($sth->rowCount() < 1) {
            throw new SessionNotFoundError();
        }

        $this->found_session = $sth->fetch(\PDO::FETCH_ASSOC);
    }

    private function decode_found_data() {
        $this->found_session['data'] = json_decode($this->found_session['data'], True);
        $this->__data__ = $this->found_session['data'];
    }
}

?>

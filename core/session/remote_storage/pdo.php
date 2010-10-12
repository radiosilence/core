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

\Core\DEPENDENCY::require_classes('\PDO');
\Core\DEPENDENCY::require_functions('json_encode','json_decode');


class PDO implements \Core\Session\RemoteStorage {
    /**
     * Untrusted session details.
     */
    private $untrusted = array();
    /**
     * Actual trusted session details.
     */
    private $actual = array();
    /**
     * PDO instance
     */
    private $pdo;
    /**
     * Found session
     */
    private $found_session;
    
    /**
     * Remote address
     */
    private $remote_addr;
    
    /**
     * This data
     */
    private $data;
    
    public function __construct() {
        $this->data = new \stdClass();
    }

    /**
     * Set remote address.
     */
    public function set_remote_addr($remote_addr) {
        $this->remote_addr = $remote_addr;
        return $this;
    }

    /**
     * Attach a PDO object. Necessary.
     */
    public function attach_pdo(\PDO $pdo) {
        $this->pdo = $pdo;
        return $this;
    }

    public function __set($key, $value) {
        $this->data->$key = $value;
    }   
    public function __get($key) {
        return $this->data->$key;
    }

    /**
     * Inserts a new session into the database.
     * @param integer $user_id
     * @return boolean success
     */
    public function add($actual) {
        $sth = $this->pdo->prepare("
            INSERT INTO sessions (
              sid, tok, remote_addr, data, latest
            )
            VALUES (
              :sid, :tok, :remote_addr, :data, :latest

            )
        ");
        $current = new \DateTime();
        $ok = $sth->execute(array(
            ":sid" => $actual['sid'],
            ":tok" => $actual['tok'],
            ":remote_addr" => $this->remote_addr,
            ":data" => json_encode($this->data),
            ":latest" => $current->Format('r')
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
            SET data = :data,
              latest = :latest
            WHERE sid = :sid
        ");
        $current = new \DateTime();
        $ok = $sth->execute(array(
            ":data" => json_encode($this->data),
            ":latest" => $current->Format('r'),
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

        $this->found_session = $sth->fetchObject();
    }

    private function decode_found_data() {
        $this->found_session->data = json_decode($this->found_session->data);
        $this->data = $this->found_session->data;
    }
}

?>
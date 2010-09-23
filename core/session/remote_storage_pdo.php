<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace \Core\Session;

import('core.session.interfaces');
import('core.dependency');

\Core\DEPENDENCY::require_classes('PDO');
\Core\DEPENDENCY::require_functions('json_encode','json_decode');


class RemoteStoragePDO implements RemoteStorage {
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
	private $data = array();
	
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

    public function __set($prop_name, $prop_value) {
        $this->data[$prop_name] = $prop_value;
        return true;
    }	
	/**
     * Makes the session in the database have the current data.
     */
    public function update($sid, $data) {
        $sth = $this->pdo->prepare("
            UPDATE sessions
            SET data = :data
            WHERE sid = :sid
        ");
        $ok = $sth->execute(array(
            "data" => json_encode($data),
            "sid" => $sid
        ));
        if(!$ok) {
            throw new SessionUpdateError();
        }

    }

    /**
     * Inserts a new session into the database.
     * @param integer $user_id
     * @return boolean success
     */
    public function add(){
        $sth = $this->pdo->prepare("
            INSERT INTO sessions (
                sid, tok, remote_addr, data
            )
            VALUES (
                :sid, :tok, :remote_addr, :data
            )
        ");
        $ok = $sth->execute(array(
            "sid" => $this->sid,
            "tok" => $this->tok,
            "remote_addr" => $this->remote_addr,
            "data" => $this->data
        ));

        if(!$ok) {
            throw new SessionInsertError();
        }
    }

    /**
     * Destroys sid in database
     */
    public function delete(){ 
        $sth = $this->pdo->prepare("
            DELETE FROM sessions
            WHERE sid = :sid
            AND remote_addr = :remote_addr
        ");
        
        $ok = $sth->execute(array(
            "sid" => $this->sid,
            "remote_addr" => $this->remote_addr
        ));
        if(!$ok) {
            throw new SessionDeleteError();
        }
    }
	
	/**
     * Find a matching sid/tok/IP in the database
     */
    public function find($sid,$tok) {
        $sth = $this->pdo->prepare("
            SELECT sid, tok, data, remote_addr
            FROM sessions
            WHERE sid = :sid
            AND tok = :tok
            AND remote_addr = :remote_addr
            LIMIT 1
        ");
        $ok = $sth->execute(array(
            "sid" => $sid,
            "tok" => $tok,
            "remote_addr" => $this->remote_addr
        ));
        if(!$ok) {
            throw new SessionFindError($this->pdo->errorInfo());
        }
        $this->found_session = $sth->fetchObject();
		$this->decode_found_data()
		return $this->found_session;
    }
	
	private function decode_found_data();
	{
		if(!$this->found_session->data = json_decode($this->found_session->data)){
			throw new JSONDecodeError();
		} 
	}
}

class JSONDecodeError extends SessionFindError {}

<?php
namespace Core;
class Hasher {
    protected $_strength;
    protected $_itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public function __construct($strength=14) {
        $this->_strength = $strength;
    }
    public function hash_password($password, $salt=False) {
        if(!$salt) {
            $salt = $this->_gen_salt();        
        }
        return sprintf(
            '$w$%s$%s$%s',
            $this->_strength,
            $salt,
            $this->_hash_multi($salt . $password, $this->_strength));
    }

    public function check_password($attempt, $hash) {
        $bits = explode('$', $hash);
        if($this->_hash_multi($bits[3] . $attempt, $bits[2]) == $bits[4]) {
            return True;
        } else {
            return False;
        }
    }

    protected function _hash_multi($string, $strength) {
        for($i=0; $i<pow(2, $strength); $i++) {
            $string = hash('whirlpool', $string);
        }
        return $string;
    }

    protected function _gen_salt() {
        return $this->_encode64($this->_get_random_bytes(90), 89);
    }

    protected function _get_random_bytes($count) {
        $output = '';
        if (is_readable('/dev/urandom') &&
            ($fh = @fopen('/dev/urandom', 'rb'))) {
            $output = fread($fh, $count);
            fclose($fh);
        }

        if (strlen($output) < $count) {
            $output = '';
            for ($i = 0; $i < $count; $i += 16) {
                $this->random_state =
                    md5(microtime() . $this->random_state);
                $output .=
                    pack('H*', md5($this->random_state));
            }
            $output = substr($output, 0, $count);
        }
        return $output;
    }

    protected function _encode64($input, $count) {
        $output = '';
        $i = 0;
        do {
            $value = ord($input[$i++]);
            $output .= $this->_itoa64[$value & 0x3f];
            if ($i < $count)
                $value |= ord($input[$i]) << 8;
            $output .= $this->_itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count)
                break;
            if ($i < $count)
                $value |= ord($input[$i]) << 16;
            $output .= $this->_itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count)
                break;
            $output .= $this->_itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }
}
?>
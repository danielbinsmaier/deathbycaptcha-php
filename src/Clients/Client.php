<?php

namespace DanielBinsmaier\DeathByCaptcha\Clients;

use DanielBinsmaier\DeathByCaptcha\Exceptions\InvalidCaptchaException;
use DanielBinsmaier\DeathByCaptcha\Exceptions\RuntimeException;

/**
 * Base Death by Captcha API client
 *
 * @property-read array|null $user    User's details
 * @property-read float|null $balance User's balance (in US cents)
 */
abstract class Client
{
    const API_VERSION = 'DBC/PHP v4.5';

    const DEFAULT_TIMEOUT = 60;
    const DEFAULT_TOKEN_TIMEOUT = 120;
    const POLLS_INTERVAL = array(1, 1, 2, 3, 2, 2, 3, 2, 2);
    const DFLT_POLL_INTERVAL = 3;

    /**
     * Verbosity flag.
     * When it's set to true, the client will produce debug output on every API call.
     *
     * @var bool
     */
    public $is_verbose = false;

    /**
     * DBC account credentials
     *
     * @var array
     */
    protected $_userpwd = array();

    /**
     * @param string $username DBC account username
     * @param string $password DBC account password
     * @throws RuntimeException On missing/empty DBC account credentials
     * @throws RuntimeException When required extensions/functions not found
     */
    public function __construct($username, $password)
    {
        foreach (array('username', 'password') as $k) {
            if (!$$k) {
                throw new RuntimeException(
                    "Account {$k} is missing or empty"
                );
            }
        }
        $this->_userpwd = array($username, $password);
    }

    /**
     * Parses URL query encoded responses
     *
     * @param string $s
     * @return array
     */
    static public function parse_plain_response($s)
    {
        parse_str($s, $a);
        return $a;
    }

    /**
     * Parses JSON encoded response
     *
     * @param string $s
     * @return array
     */
    static public function parse_json_response($s)
    {
        return json_decode(rtrim($s), true);
    }

    /**
     * Returns CAPTCHA text
     *
     * @param int $cid CAPTCHA ID
     * @return string|null
     * @uses DeathByCaptcha_Client::get_captcha()
     */
    public function get_text($cid)
    {
        return ($captcha = $this->get_captcha($cid)) ? $captcha['text'] : null;
    }

    /**
     * Returns CAPTCHA details
     *
     * @param int $cid CAPTCHA ID
     * @return array|null
     */
    abstract public function get_captcha($cid);

    /**
     * Reports an incorrectly solved CAPTCHA
     *
     * @param int $cid CAPTCHA ID
     * @return bool
     */
    abstract public function report($cid);

    /**
     * Tries to solve CAPTCHA by uploading it and polling for its status/text
     * with arbitrary timeout. See {@link DeathByCaptcha_Client::upload()} for
     * $captcha param details.
     *
     * @param int $timeout Optional solving timeout (in seconds)
     * @return array|null CAPTCHA details hash on success
     * @uses DeathByCaptcha_Client::upload()
     * @uses DeathByCaptcha_Client::get_captcha()
     */
    public function decode($captcha = null, $extra = [], $timeout = null)
    {
        if (!$extra || !is_array($extra)) {
            $extra = [];
        }

        if (is_null($timeout)) {
            if (is_null($captcha)) {
                $timeout = self::DEFAULT_TOKEN_TIMEOUT;
            } else {
                $timeout = self::DEFAULT_TIMEOUT;
            }
        }

        $deadline = time() + (0 < $timeout ? $timeout : self::DEFAULT_TIMEOUT);
        if ($c = $this->upload(
            $captcha,
            $extra)
        ) {
            $intvl_idx = 0; // POLLS_INTERVAL index

            while ($deadline > time() && $c && !$c['text']) {
                list($intvl, $intvl_idx) = $this->_get_poll_interval($intvl_idx);
                sleep($intvl);
                $c = $this->get_captcha($c['captcha']);
            }
            if ($c && $c['text'] && $c['is_correct']) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Uploads a CAPTCHA
     *
     * @param string|array|resource $captcha CAPTCHA image file name, vector of bytes, or file handle
     * @param array $extra
     * @return array|null Uploaded CAPTCHA details on success
     * @throws InvalidCaptchaException On invalid CAPTCHA file
     */
    abstract public function upload($captcha, $extra = []);

    /**
     * @param int $idx index of POLLS_INTERVAL to be accessed
     * @return array with interval and index
     */
    protected function _get_poll_interval($idx)
    {
        if (count(self::POLLS_INTERVAL) > $idx) {
            $intvl = self::POLLS_INTERVAL[$idx];
        } else {
            $intvl = self::DFLT_POLL_INTERVAL;
        }
        $idx++;

        return array($intvl, $idx);
    }

    /**
     * @ignore
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Closes opened connection (if any), as gracefully as possible
     *
     * @return Client
     */
    abstract public function close();

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch ($key) {
            case 'user':
                return $this->get_user();
            case 'balance':
                return $this->get_balance();
        }
    }

    /**
     * Returns user details
     *
     * @return array|null
     */
    abstract public function get_user();

    /**
     * Returns user's balance (in US cents)
     *
     * @return float|null
     * @uses DeathByCaptcha_Client::get_user()
     */
    public function get_balance()
    {
        return ($user = $this->get_user()) ? $user['balance'] : null;
    }

    /**
     * Checks if CAPTCHA is valid (not empty)
     *
     * @param string $img Raw CAPTCHA image
     * @throws InvalidCaptchaException On invalid CAPTCHA images
     */
    protected function _is_valid_captcha($img)
    {
        if (0 == strlen($img)) {
            throw new InvalidCaptchaException(
                'CAPTCHA image file is empty'
            );
        } else {
            return true;
        }
    }

    protected function _load_captcha($captcha)
    {
        if (is_resource($captcha)) {
            $img = '';
            rewind($captcha);
            while ($s = fread($captcha, 8192)) {
                $img .= $s;
            }
            return $img;
        } else if (is_array($captcha)) {
            return implode('', array_map('chr', $captcha));
        } else if ('base64:' == substr($captcha, 0, 7)) {
            return base64_decode(substr($captcha, 7));
        } else {
            return file_get_contents($captcha);
        }
    }
}
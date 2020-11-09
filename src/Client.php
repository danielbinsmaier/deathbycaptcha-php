<?php

namespace DanielBinsmaier\DeathByCaptcha;

use DanielBinsmaier\DeathByCaptcha\Clients\HttpClient;
use DanielBinsmaier\DeathByCaptcha\Clients\SocketClient;

class Client {
    /**
     * @var \DanielBinsmaier\DeathByCaptcha\Clients\Client
     */
    private $client;

    /**
     * @var string
     */
    private $captchaId;

    /**
     * Sets the client instance to an http instance.
     * If password is null, will assume the username is an authtoken.
     *
     * @param string $username
     * @param string|null $password
     * @return $this
     * @throws Exceptions\RuntimeException
     */
    public function http($username, $password = null)
    {
        if ($password === null) {
            $password = $username;
            $username = 'authtoken';
        }

        $this->client = new HttpClient($username, $password);

        return $this;
    }

    /**
     * Sets the client instance to a socket instance.
     * If password is null, will assume the username is an authtoken.
     *
     * @param string $username
     * @param string|null $password
     * @return $this
     * @throws Exceptions\RuntimeException
     */
    public function socket($username, $password = null)
    {
        if ($password === null) {
            $password = $username;
            $username = 'authtoken';
        }

        $this->client = new SocketClient($username, $password);

        return $this;
    }

    /**
     * Uploads a captcha to the service and will return a captcha instance.
     *
     * @param string $captcha
     * @param array $extra
     * @return Captcha|null
     * @throws Exceptions\InvalidCaptchaException
     */
    public function upload($captcha, $extra = [])
    {
        $captcha = $this->client->upload($captcha, $extra);

        if ($captcha === null) {
            return null;
        }

        return new Captcha($this, $captcha);
    }

    /**
     * Uploads the captcha and instantly waits for it being solved.
     * Returns either null if timeout or failed to upload or otherwise the text of the captcha.
     *
     * @param $captcha
     * @param array $extra
     * @param int $timeout
     * @return string|null
     * @throws Exceptions\InvalidCaptchaException
     */
    public function solve($captcha, $extra = [], $timeout = \DanielBinsmaier\DeathByCaptcha\Clients\Client::DEFAULT_TIMEOUT)
    {
        $captcha = $this->upload($captcha, $extra);

        if ($captcha === null) {
            return null;
        }

        return $captcha->solve($timeout);
    }

    /**
     * Polls for updated captcha status.
     * Returns either a captcha instance or a raw array, if raw is true.
     *
     * @param string $captchaId
     * @param bool $raw
     * @return array|Captcha|null
     */
    public function captcha($captchaId, $raw = false)
    {
        $captcha = $this->client->get_captcha($captchaId);

        if ($raw) {
            return $captcha;
        } else {
            if ($captcha === null) {
                return null;
            }

            return new Captcha($this, $captcha);
        }
    }

    /**
     * Polls the text of a captcha.
     *
     * @param string $captchaId
     * @return string|null
     */
    public function text($captchaId)
    {
        return $this->client->get_text($captchaId);
    }

    /**
     * Returns the authenticated user information.
     *
     * @return array|null
     */
    public function user()
    {
        return $this->client->get_user();
    }

    /**
     * Returns the authenticated user balance.
     *
     * @return float|null
     */
    public function balance()
    {
        return $this->client->get_balance();
    }

    /**
     * Sets verbosity.
     *
     * @param bool $verbose
     * @return $this
     */
    public function verbose($verbose = true)
    {
        $this->client->is_verbose = $verbose;

        return $this;
    }
}
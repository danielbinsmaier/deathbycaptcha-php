<?php

namespace DanielBinsmaier\DeathByCaptcha;

class Captcha
{
    /**
     * @var string
     */
    public $captchaId;

    /**
     * @var string
     */
    public $text;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $isCorrect;

    /**
     * Captcha constructor.
     *
     * @param Client $client
     * @param array $data
     */
    public function __construct($client, $data)
    {
        $this->client = $client;

        $this->fill($data);
    }

    /**
     * Fills captcha data from raw array.
     *
     * @param $data
     */
    protected function fill($data)
    {
        $this->captchaId = $data['captcha'];
        $this->text = $data['text'];
        $this->isCorrect = $data['is_correct'];
    }

    /**
     * Returns if the captcha was solved already.
     *
     * @return bool
     */
    public function solved()
    {
        return !empty($this->text);
    }

    /**
     * Pools for the updated captcha status and returns if solved.
     *
     * @return bool
     */
    public function poll()
    {
        $this->fill($this->client->captcha($this->captchaId, true));

        return $this->solved();
    }

    /**
     * Polls until the captcha is solved or we timeout.
     * Returns either the text of the captcha or null if timeout.
     *
     * @param int $timeout
     * @return string|null
     */
    public function solve($timeout = \DanielBinsmaier\DeathByCaptcha\Clients\Client::DEFAULT_TIMEOUT)
    {
        $endPoll = time() + $timeout;

        while (time() <= $endPoll) {
            if ($this->poll()) {
                return $this->text;
            }

            sleep(3);
        }

        return null;
    }

    /**
     * Reports the captcha as invalid.
     */
    public function report()
    {
        $this->client->upload($this->captchaId);
    }
}
<?php
/**
 * @copyright 2021 Mazitov Artem
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace MoodleBoost\MoodleApi;

use InvalidArgumentException;

class RestApi
{
    /** @var string[] */
    const FORMAT_EXIST = ['json', 'xml'];

    /** @var string */
    const API_QUERY = 'webservice/rest/server.php';

    /** @var string */
    const API_GET_FILE = 'webservice/pluginfile.php';

    /** @var string */
    protected $url;

    /** @var string */
    protected $token;

    /** @var string */
    protected $format;

    /** @var array */
    protected $body = [];

    /** @var mixed */
    public $answer;

    /** @var string */
    public $answerRaw;

    /**
     * @param string $url
     * @param string $token
     * @param string $format
     */
    public function __construct($url, $token, $format = 'json')
    {
        if (substr($url, -1, 1) != '/') {
            $url .= '/';
        }
        $this->url = $url;

        $this->token = $token;

        if (!in_array($format, self::FORMAT_EXIST)) {
            throw new InvalidArgumentException("Format {$format} not found");
        }
        $this->format = $format;

        $this->body = [
            'wstoken' => $token,
            'moodlewsrestformat' => &$this->format,
        ];
    }

    /**
     * @param string $wsFunction function name
     * @param array $data function args
     * @return mixed
     */
    public function query($wsFunction, $data = [])
    {
        $url = $this->url . self::API_QUERY . '?wsfunction=' . $wsFunction;
        $query = $this->body + $data;

        $this->answerRaw = $this->request($url, $query);
        $this->answer = $this->formatAnswer($this->answerRaw);

        return $this->answer;
    }

    /**
     * @param $url
     * @param $data
     * @return bool|string
     */
    protected function request($url, $data)
    {
        $process = curl_init($url);
        curl_setopt_array($process, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data, '', '&')
        ]);

        $answer = curl_exec($process);
        curl_close($process);
        return $answer;
    }

    /**
     *
     * @param $raw
     * @return mixed
     */
    protected function formatAnswer($raw)
    {
        switch ($this->format) {
            case 'json':
                return json_decode($raw);
            case 'xml':
                return simplexml_load_string($raw);
            default:
                return $raw;
        }
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function getFile($path)
    {
        $url = $this->url . self::API_GET_FILE . $path;
        return $this->request($url, ['token' => $this->token]);
    }

    /**
     * @return mixed
     */
    public function getAllMethods()
    {
        if ($this->format === 'xml'){
            $switchFormat = true;
            $this->format = 'json';
        } else {
            $switchFormat = false;
        }

        $data = $this->query('core_webservice_get_site_info');

        if ($switchFormat){
            $this->format = 'xml';
        }

        return isset($data->functions) ? $data->functions : $data;
    }
}
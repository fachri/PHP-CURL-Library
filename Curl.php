<?php

/**
 * PHP Curl Library
 *
 * @author      Imam Fachri Chairudin
 * @license     MIT License
 * @link        http://fachri.id
 * @editor      PhpStorm
 * @date        9/24/16
 * @time        5:36 PM
 */
class Curl
{
    /**
     * headers
     *
     * @var
     */
    protected $_headers;

    /**
     * method set
     *
     * @var string
     */
    protected $_method = "GET";

    /**
     * posts fields / raw post
     *
     * @var
     */
    protected $_posts;

    /**
     * target URL
     *
     * @var
     */
    protected $_url;

    /**
     * result of curl execution, when succeeded change to string/array
     *
     * @var bool
     */
    protected $_result = false;

    /**
     * curl execution result info
     *
     * @var bool
     */
    protected $_info = false;

    /**
     * execute curl
     *
     * @param null $inputs
     * @return bool|mixed
     */
    public function proceed($inputs = null)
    {
        // set headers if available
        if (isset($inputs['headers']))
            $this->setHeaders($inputs['headers']);

        // set method if available
        if (isset($inputs['method']))
            $this->setMethod($inputs['method']);

        // set posts if available
        if (isset($inputs['posts']))
            $this->setPosts($inputs['posts']);

        // set url if available
        if (isset($inputs['url']))
            $this->setURL($inputs['url']);

        // stop curl execution if URL not set
        if (empty($this->_url) || is_null($this->_url))
            return false;

        // curl initialize
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_URL, $this->_url);

        // if header available, set curl header option
        if (count($this->_headers) > 0)
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->_headers);

        // check method used
        switch ($this->_method)
        {
            case 'POST' :
                // set curl post option
                curl_setopt($curl, CURLOPT_POST, TRUE);
                break;
            case 'GET' :
                // do nothing
                break;
            default :
                // if header not post/get then set curl custom request option
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->_method);
                break;
        }

        // post available, set curl post field option
        if (count($this->_posts) > 0)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->_posts);

        // execute curl
        $this->_result = curl_exec($curl);

        // get execution information detail
        $this->_info = curl_getinfo($curl);

        // if result in JSON format, convert to array
        $this->_decodeToJSONIfPossible();

        // close curl execution
        curl_close($curl);

        // return curl execution result data
        return $this->_result;
    }

    /**
     * get curl execution result data
     * only available after execution, or else it will return false
     *
     * @return bool|mixed
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * get curl execution information detail
     * only available after execution, or else it will return false
     *
     * @return bool|mixed
     */
    public function getInfo()
    {
        return $this->_info;
    }

    /**
     * set method for curl process
     *
     * @param $input
     * @return bool
     */
    public function setMethod($input)
    {
        // if given input is array, then return false
        if (!is_string($input))
            return false;

        // change input to uppercase
        $input = strtoupper($input);

        // validate input
        switch($input)
        {
            case 'POST' :
            case 'PUT' :
            case 'PATCH' :
            case 'DELETE' :
            case 'COPY' :
            case 'HEAD' :
            case 'OPTIONS' :
            case 'LINK' :
            case 'UNLINK' :
            case 'PURGE' :
            case 'LOCK' :
            case 'UNLOCK' :
            case 'PROPFIND' :
            case 'VIEW' :
            case 'GET' :
                $this->_method = $input;
                break;
            // if method not recognise, then return false
            default :
                return false;
        }

        return true;
    }

    /**
     * set url for curl execution
     *
     * @param $input
     * @return bool
     */
    public function setURL($input)
    {
        // if given input is array, then return false
        if (!is_string($input))
            return false;

        $this->_url = $input;

        return true;
    }

    /**
     * set post fields / raw post
     *
     * @param $inputs
     * @param null $value
     * @return bool
     */
    public function setPosts($inputs, $value = null)
    {
        // if method is GET return false
        if ($this->_method == 'GET')
            return false;

        // if post input in array format then build query for curl execution
        if (is_array($inputs))
        {
            $this->_posts = http_build_query($inputs);
        }
        // if string
        else
        {
            // if value set put input as key and value into an array and build query cor curl execution
            if (is_null($value))
            {
                $this->_posts = http_build_query(array($inputs => $value));
            }
            // if value not set, set post as raw post data and added new header as text/plain for content type
            else
            {
                $this->_posts = $inputs;
                $this->setHeaders = $this->setHeaders('Content-Type', 'text/plain');
            }
        }

        return true;
    }

    /**
     * set headers
     *
     * @param $inputs
     * @param null $value
     * @return bool
     */
    public function setHeaders($inputs, $value = null)
    {
        // if inputs is string and value is null then return false,
        // but if value is set then create single header array
        if (!is_array($inputs))
        {
            if (is_null($value))
                return false;

            $inputs = array($inputs => $value);
        }

        // crawl on inputs array and set as header
        foreach ($inputs as $key => $value)
        {
            // if header key is 'authorization' let authorization basic header function generated the header
            if (strtolower($key) == 'authorization')
            {
                if (is_array($inputs['authorization']))
                    $this->setBasicAuthorization($inputs['authorization']);
                else
                    $this->setBasicAuthorization($key, $value);
            }
            // if its not then set header
            else
            {
                $this->_headers[$key] = "{$key}: {$value}";
            }
        }

        return true;
    }

    /**
     * set basic authorization header request
     *
     * @param $inputs
     * @param string $headerName
     * @return bool
     */
    public function setBasicAuthorization($inputs, $headerName = 'Authorization')
    {
        // if inputs is array then set header for each array data
        if (is_array($inputs))
        {
            foreach ($inputs as $key => $value)
                $this->_setBasicAuthorizationValue($key, $value);
        }
        // if its string add new single header basic authorization
        else
        {
            $this->_setBasicAuthorizationValue($inputs, $headerName);
        }

        return true;
    }

    /**
     * generate basic authorization here
     *
     * @param $headerName
     * @param $headerValue
     */
    private function _setBasicAuthorizationValue($headerName, $headerValue)
    {
        // set upper case for the first characters
        $headerName = ucfirst($headerName);

        // encode header value data with base64
        $headerValue = base64_encode($headerValue);

        // add header
        $this->_headers[$headerName] = "{$headerName}: Basic {$headerValue}";
    }

    /**
     * decode curl result if its in JSON format
     */
    private function _decodeToJSONIfPossible()
    {
        // try to decode result from JSON format
        $decoded = json_decode($this->_result, TRUE);

        // if it succeeded then replace the result
        if (!is_null($decoded))
            $this->_result = $decoded;
    }
}

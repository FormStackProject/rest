<?php

/**
*
* Rest.php: Executes curl tests of the REST get, post, put & delete operations
*
* NOTE: Curl.php is a third-party library. No sense in reinventing the wheel.
*
*/

require_once("lib/Curl.php");

class Rest
{
    public function get($table=null)
    {
        $curl = new Curl();
        $curl->setUrl(sprintf("http://testbox.dev/api/%s", $table))
            ->setType('GET');
        $curl->send();
        if ($curl->getStatusCode()==200) {
            return $curl->getResponse();
        } else {
            return $curl->getStatusCode();
        }
    }
    public function post($table=null, $json=null)
    {
        $curl = new Curl();
        $curl->setUrl(sprintf("http://testbox.dev/api/%s", $table))
            ->setData($json)
            ->setType('POST');
        $curl->send();
        if ($curl->getStatusCode()==200) {
            return $curl->getResponse();
        } else {
            return $curl->getStatusCode();
        }
    }
    public function put($table=null, $json=null)
    {
        $curl = new Curl();
        $curl->setUrl(sprintf("http://testbox.dev/api/%s", $table))
            ->setData($json)
            ->setType('PUT');
        $curl->send();
        if ($curl->getStatusCode()==200) {
            return $curl->getResponse();
        } else {
            return $curl->getStatusCode();
        }
    }
    public function delete($table=null, $id=null)
    {
        $curl = new Curl();
        $curl->setUrl(sprintf("http://testbox.dev/api/%s/%s", $table, $id))
            ->setType('DELETE');
        $curl->send();
        if ($curl->getStatusCode()==200) {
            return $curl->getResponse();
        } else {
            return $curl->getStatusCode();
        }
    }
}

<?php

/**
*
*View: JSON-ized output creation, along with error messages.
*
*/

class View
{
    public function __construct()
    {
    }

    public static function init()
    {
        $instance = new self();

        return $instance;
    }
    /**
    *
    * Creates simple JSON return message.
    *
    * @param boolean $status Success of operation
    * @param string $message Description of success of operation
    *
    */
    public function jsonMessage($status=false, $message="")
    {
        $response= array(
            "status" => $status,
            "message"=> $message
        );
    
        printf("%s", json_encode($response));
    }

    /**
    *
    * Creates JSON object from MySQL query result.
    *
    * IMPORTANT NOTE: In the mobile dev world (from which I
    * emerge), I always return JSON data in an array.
    *
    * @param mysqli_result $result Mysqli result
    *
    */
    public function jsonizeQuery($result)
    {
        /** If $result is a boolean, I know the operation failed. */

        if (is_bool($result)) {
            print "[]";
        }
        /** Grab successive rows, encode them, and return them in an array */

        $strJSON = "";
        while ($row = $result->fetch_assoc()) {
            $json = json_encode($row);
            $strJSON .= $json.",";
        }
        $strJSON = substr($strJSON, 0, -1);

        printf("[%s]", $strJSON);
    }
    /**
    *
    * Issue http response with specified code.
    *
    * @param integer $code http response
    *
    */
    public function httpResponse($code=null)
    {
        http_response_code($code);
    }
}

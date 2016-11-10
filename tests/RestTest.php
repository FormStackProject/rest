<?php

/**
*
* RestTest.php: Tests the CRUD operations of the REST implementation.
*
* This PHPUnit test could easily be turned into an automated routine
* to test any number of REST tables. A loop could easily cycle through
* table and field names, checking each table in a complete RESTful
* implementation.
*
* NOTE: This is a destructive test, best suited for testing the initial
* implementation, but it could easily be modified for application to
* live data tables.
*
*/

$dirApi = "../api";

require_once(sprintf("%s/models/Model.php", $dirApi));
require_once(sprintf("%s/lib/password.php", $dirApi));
require_once('Rest.php');
 
class RestTest extends PHPUnit_Framework_TestCase
{
    private $rest;
    private $table = "contacts";
 
    protected function setUp()
    {
        $this->rest = new Rest();
        $this->truncate($this->table);
    }
 
    protected function tearDown()
    {
        $this->rest = null;
    }
 
    public function testCRUD()
    {
        printf("\n\n---------------------------------------------------\n");
        printf("After truncation, GET empty array.\n");
        printf("---------------------------------------------------\n\n");

        $result = $this->rest->get($this->table);

        /** Insure empty array '[]' returned, and not 
            http_response code signalling error. */

        $this->assertTrue($result == "[]");

        printf("Succeeded.\n\n");

        printf("---------------------------------------------------\n");
        printf("Adding record via Post.\n");
        printf("---------------------------------------------------\n\n");

        $jsonIn = array(
            'name_first' => 'Steven',
            'name_last' => 'Sickles',
            'password' => 'password',
            'email' => 1,
            'email_address' => 'stevensickles@gmail.com'
        );
        $json = json_encode($jsonIn);

        $this->rest->post($this->table, $json);

        $result = $this->rest->get($this->table);

        $result = preg_replace('/(^\[|\]$)/', "", $result);

        $jsonOut = json_decode($result);

        foreach ($jsonOut as $key => $value) 
        {
            if (isset($jsonIn[$key])) 
            {
                if ($value != $jsonIn[$key]) 
                {
                    /** Only field that shouldn't match its set value is password. */
                    $this->assertTrue($key == "password");

                    /** The password field value should verify, though. */

                    $this->assertTrue(password_verify("password", $value));
                 }

            } else {

               /** Only field that doesn't match retrieved fields is added index 'id' */
                $this->assertTrue($key == "id");
            }
        }

        printf("Succeeded.\n\n");

        printf("---------------------------------------------------\n");
        printf("Updating record via PUT.\n");
        printf("---------------------------------------------------\n\n");

        $jsonOut->name_first = "Chris";
        $jsonOut->name_last = "Pierce";
        $jsonOut->password = "password";
        $jsonIn = $jsonOut;

        $this->rest->put($this->table, json_encode($jsonIn));

        $result = $this->rest->get($this->table);

        $result = preg_replace('/(^\[|\]$)/', "", $result);

        $jsonOut = json_decode($result);
        foreach ($jsonOut as $key => $value) 
        {
            if (isset($jsonIn->$key)) 
            {
                if ($value != $jsonIn->$key) 
                {

                   # Only field that shouldn't match its set value is password.

                    $this->assertTrue($key == "password");

                    # The password field value should verify, though.

                    $this->assertTrue(password_verify("password", $value));
                 }

            } else {

               # Only field that doesn't match retrieved fields is added index 'id'
                $this->assertTrue($key == "id");
            }
        }
        printf("Succeeded.\n\n");

        $jsonIn = $result;

        printf("---------------------------------------------------\n");
        printf("Deleting only record, and confirming its deletion.\n");
        printf("---------------------------------------------------\n\n");

        $jsonIn = json_decode($jsonIn);

        $result = $this->rest->delete($this->table, $jsonIn->id);

        $result = $this->rest->get($this->table);

        $this->assertTrue($result == "[]");

        printf("Succeeded.\n\n");

        printf("---------------------------------------------------\n");
        printf("All Tests Completed Successfully!\n");
        printf("---------------------------------------------------\n\n");
    }

    protected function truncate($table=null)
    {
        /** Truncate table at onset */

        if (!$model = Model::initAndConnect()) 
        {
            $view->jsonMessage(false, "ERROR: Cannot connect to model.");
            return;
        }
        $model->truncate($table);
    }
}

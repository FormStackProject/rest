<?php 
    
/**
*
*	Model: Model that isolates database interactions
*
*/


class Model
{
    private $sqli = null;
    private $instance = null;
    
    public function initAndConnect()
    {
        global $dirApi;
        require_once(sprintf("%s/inc/connect.inc", $dirApi));
        
        /** Create and maintain connection */
        
        $instance = new self();
        
        /**	NOTE: Connection parameters should normally be stored outside of doc root. */
    $instance->sqli = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['db_name'], $db_config['db_port']);

        if (!$instance->sqli) {
            printf("Error connecting to database: %s". mysql_error());
            return null;
        }
        
        return $instance;
    }
    
    public function remove($table=null, $id=null)
    {
        /**
        * Deletes record based on id.
        *
        * @param string $table Database table
        * @param integer $id Record index
        *
        */
        
        $strSQL        = sprintf("DELETE FROM %s WHERE id='%s'", $table, $id);
        
        if ($debug) {
            printf("%s<br>", $strSQL);
        }

        $result    = mysqli_query($this->sqli, $strSQL);

        return $result;
    }
    
    public function edit($table=null, $keyValues=false, $debug=false)
    {
        /**
        * Performs either create or update, depending on existence of id.
        *
        * @param string $table Database table
        * @param json $keyValues Key-value pair JSON
        * @param boolean $debug When set, MySQL statement is explictly printed
        *
        */
        
        $blnAdd    = !isset($keyValues['id']);
        
        /** Make sure no all non-Null keys have a non-null value. If not, fail. */

        $fieldsNullable    = $this->fieldsNullable($table);
        
        foreach ($keyValues as $field => $value) {
            if (!$value && !in_array($field, $fieldsNullable)) {
                return false;
            }
        }
        
        /** Little bit of legerdemain that forms appropriate INSERT or UPDATE statement */
        
        $updFields    = "";
        $insFields    = "";
        $insValues    = "";
        foreach ($keyValues as $key => $val) {
            
            /**
            * A bit of a kludge to demonstrate knowledge that passwords must be
            * saved encrypted. 'Password.php' from third-party library, with all
            * due credit given.
            */
            
            if ($key === "password") {
                $val    = password_hash($val, PASSWORD_BCRYPT);
            }
            
            $updFields        .= sprintf("%s=\"%s\",", $key, $val);
            $insFields        .= sprintf("%s,", $key);
            $insValues        .= sprintf("\"%s\",", $val);
        }
        $updFields        = substr($updFields, 0, strlen($updFields)-1);
        $insFields        = substr($insFields, 0, strlen($insFields)-1);
        $insValues        = substr($insValues, 0, strlen($insValues)-1);
        
        $insertedID        = null;
        
        if ($blnAdd) {
            $strSQL        = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, $insFields, $insValues);
            if ($debug) {
                printf("%s<br>", $strSQL);
            }
            $success    = mysqli_query($this->sqli, $strSQL);
            $insertedID    = $this->sqli->insert_id;
        } else {
            $strSQL        = sprintf("UPDATE %s SET %s WHERE id=\"%s\"", $table, $updFields, $keyValues['id']);
            if ($debug) {
                printf("%s<br>", $strSQL);
            }
            $success    = mysqli_query($this->sqli, $strSQL);
            $insertedID    = $keyValues['id'];
        }
        
        /**
        * On successful create or update I prefer to return target record. I use
        * this as a check in my mobile apps to insure operation was a total success.
        */
        
        if ($success) {
            return $this->get($table, $insertedID);
        }
        
        return false;
    }
    
    public function isEntity($table=null)
    {
        /**
        * Determine if table exists in database.
        *
        * Rather than look for 'contacts' table explicitly, I prefer to see if
        * REST-ful request targets an actual table. If so, I know the request
        * is a valid one.
        *
        * @param string $table Database table
        *
        */

        $strSQL    = sprintf("SHOW TABLES LIKE '%s';", $table);
        $result    = mysqli_query($this->sqli, $strSQL);
        $result = $result->fetch_assoc();
        return (sizeof($result) == 1);
    }
    
    public function isField($table=null, $field=null)
    {
        /**
        * Check that field referenced in a table actually exists in that table.
        *
        * @param string $table Database table
        * @param string $field Database field to validate
        *
        */
        
        $fields    = $this->fields($table);
        if (!$fields) {
            return false;
        }
        return in_array($field, $fields);
    }
    public function isFields($table=null, $fields=null)
    {
        /**
        *  Insure fields referenced in JSON object targeting a
        * table actually all exist in that table.
        *
        * @param string $table Database table
        * @param string $field Database fields to validate
        *
        */
        
        foreach ($fields as $key => $value) {
            if (!$this->isField($table, $key)) {
                return false;
            }
        }
        return true;
    }
    
    protected function fields($table=null)
    {
        /** Return all field names from specified table.
        *
        * @param string $table Database table
        *
        */
        
        $arrColumns    = array();
        $strSQL    = sprintf("SHOW COLUMNS FROM %s", $table);
        $result    = mysqli_query($this->sqli, $strSQL);
        while (($row=$result->fetch_object())) {
            array_push($arrColumns, $row->Field);
        }
        return $arrColumns;
    }
    
    protected function fieldsNullable($table=null)
    {
        /** Return all fields from a table that can be null.
        *
        * @param string $table Database table
        *
        */
        
        $strSQL    = sprintf("SELECT COLUMN_NAME,IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='%s'", $table);
        $nullables    = array();
        $result    = mysqli_query($this->sqli, $strSQL);
        while (($row=$result->fetch_object())) {
            if ($row->IS_NULLABLE === "YES") {
                array_push($nullables, $row->COLUMN_NAME);
            }
        }
        return $nullables;
    }
    
    public function get($table=null, $id=null)
    {
        /** Return simple query on table
        *
        * @param string $table Database table
        * @param integer $id Record index (optional)
        *
        */
        
        $where    = "";
        if ($id) {
            $where    = sprintf(" where id = '%d'", $id);
        }
        
        $strSQL    = sprintf("SELECT * FROM %s%s ORDER BY ID", $table, $where);
        $result    = mysqli_query($this->sqli, $strSQL);
        return $result;
    }
    public function close()
    {
        mysqli_close($this->sqli);
    }

    public function truncate($table=null)
    {
        /**
        * Truncate table, used for PHPUnit testing
        *
        * @param string $table Database table
        *
        */
        
        $strSQL    = sprintf("TRUNCATE %s", $table);
        $result    = mysqli_query($this->sqli, $strSQL);

        return $result;
    }
}

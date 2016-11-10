<?php

/**
*
*	API (CONTROLLER): Simulate a REST-ful API in PHP
*
*/
    
namespace {

    error_reporting(E_ALL);

    $dirApi        = getcwd();
    require_once(sprintf("%s/inc/libraries.inc", $dirApi));

        /** Initalize view */

    $view    = View::init();

        /** The following few lines parse the nouns, verbs and methods of the simulated REST */

    $svrURI            = $_SERVER['REQUEST_URI'];
    $svrMethod        = $_SERVER['REQUEST_METHOD'];
    
    $dirPaths        = explode("/", $svrURI);
    $shift            = array_search(basename(__DIR__), $dirPaths);
    
    for ($i=0; $i<=$shift; $i++) {
        array_shift($dirPaths);
    }

    if (sizeof($dirPaths)==0) {
        $view->jsonMessage(false, "ERROR: No parameters supplied.");
        return;
    }

    /** $entity is the MySQL table (i.e., model) that the method acts upon. */
    
    $entity        = $dirPaths[0];
    $id            = null;
    
    if (!$model = Model::initAndConnect()) {
        $view->jsonMessage(false, "ERROR: Cannot connect to model.");
        return;
    }
    
    if (!$model->isEntity($entity)) {
        $model->close();
        $view->jsonMessage(false, sprintf("Database table '%s' does not exist.", $entity));
        return;
    }

    /** If there is more than one parameter in this REST implementation, the second param is the record id. */
    
    if (sizeof($dirPaths)>1) {
        $id    = $dirPaths[1];
    }
    
    switch ($svrMethod) {
    
      case 'PUT':        // UPDATE

        $jsonData    = json_decode(file_get_contents("php://input"), true);

        /** Fail if the update JSON object has extraneous keys. */
        
        if (!$model->isFields($entity, $jsonData)) {
            $model->close();
            $view->jsonMessage(false, "Create/update failed: JSON array correspondence error.");
            return;
        }

        /** Fail if the update is unsuccessful. */

        $result    = $model->edit($entity, $jsonData, false);
        $model->close();
        if (!$result) {
            $view->jsonMessage(false, "ERROR: Unable to update record.");
            return;
        }

        /** On success, return a single-element array of the record updated. */

        $view->jsonizeQuery($result);
        return;
          
      case 'POST':        // CREATE

        $jsonData    = json_decode(file_get_contents("php://input"), true);
        /** Fail if the update JSON object has extraneous keys. */

        if (!$model->isFields($entity, $jsonData)) {
            $model->close();
            $view->jsonMessage(false, "Create/update failed: JSON array correspondence error.");
            return;
        }

        /** Fail if record creation is unsuccessful. */

        $result    = $model->edit($entity, $jsonData, false);
        $model->close();
        if (!$result) {
            $view->jsonMessage(false);
            return;
        }

        /** On success, return a single-element array of the record added. */

        $view->jsonizeQuery($result);
        return;
          
      case 'GET':        // READ

        /** If record unspecified, return an array containing all the records. Otherwise, return a single-element array containing the record for the corresponding id. If no record with the corresponding id can be found, return an empty array. */
        $result    = $model->get($entity, $id);
        $model->close();
    
        $view->jsonizeQuery($result);
        return;
     
      case 'DELETE':    // DELETE

        parse_str(file_get_contents("php://input"), $Deletes);

        if ($id && $id > 0) {
            if ($model->remove($entity, $id)) {
                $model->close();
                $view->jsonMessage(true, "Deletion successful.");
                return;
            } else {
                $model->close();
                $view->jsonMessage(false, "Deletion failed.");
                return;
            }
        }

        /** Fail with an error message if no deletion id, or a zero id, is specified. */

        $model->close();
        $view->jsonMessage(false, "Deletion failed. No index specified.");
        return;
          
      default:

        /** Return a 405 http response code if the specified method is not among CRUD ('POST','GET','PUT','DELETE'). */

        $model->close();
        $view->httpResponse(405);
        return;
    }
}

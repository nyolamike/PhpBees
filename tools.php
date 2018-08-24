<?php

    function tools_chain($array_to_be_chained, $template = ""){
        if(is_array($array_to_be_chained) && count($array_to_be_chained) > 0){
            $str = "";
            for ($i=0; $i < count($array_to_be_chained); $i++) { 
                // _dump($array_to_be_chained[$i]);
                $str_temp = ($template != "")?(str_ireplace("{}",$array_to_be_chained[$i],$template)) : ($array_to_be_chained[$i]);
                    // _dump($str_temp);
                    $str = $str . " " . $str_temp . ",";
            }
            $str = trim($str);
            $str = substr($str,0,strlen($str)-1);
            return $str;
        }else if(is_array($array_to_be_chained) && count($array_to_be_chained) == 0){
            return $template;
        }else{
            return str_ireplace("{}",$array_to_be_chained,$template);
        }
    }

    //adopted from
    //https://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    function tools_startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    function tools_get_current_folder_name(){
        return strtolower(substr(str_replace(dirname(dirname(__FILE__)), '', dirname(__FILE__)),1));
    }

    function tools_jsonify($json){
        $temp = null;
        if(is_string($json)){
            $temp = json_decode($json, true);
        }else{
            $temp = json_encode($json);
        }
        $error = json_last_error();
        $msg = "";
        switch ($error) {
             case JSON_ERROR_NONE:
                $msg = "";
                break;
             break;
             case JSON_ERROR_DEPTH:
                $msg = "JSON_ERROR_DEPTH - Maximum stack depth exceeded";
                break;
             break;
             case JSON_ERROR_STATE_MISMATCH:
                $msg = "JSON_ERROR_STATE_MISMATCH - Underflow or the modes mismatch";
                break;
             break;
             case JSON_ERROR_CTRL_CHAR:
                $msg = "JSON_ERROR_CTRL_CHAR - Unexpected control character found";
                break;
             break;
             case JSON_ERROR_SYNTAX:
                $msg = "JSON_ERROR_SYNTAX - Syntax error, malformed JSON";
                break;
             break;
             case JSON_ERROR_UTF8:
                $msg = "JSON_ERROR_UTF8 - Malformed UTF-8 characters, possibly incorrectly encoded";
                break;
             break;
             default:
                $msg = "JSON - Unknown error";
                break;
             break;
        }
        return array($temp,(strlen($msg)>0?array($msg):array()));
    }

    

    //config: username,password,servername,databasename,show_sql_errors
    function tools_ensure_garden_exists($config,$structure,$try_again=true){
        $res = array(null,array());
        $res_data = array();
        $errors = array();

      
        //nyd
        //implement this logic here
        $db_res = db_get_connection(
            $config["username"], 
            $config["password"],
            $config["servername"],
            $config["databasename"],
            false
        );
        if(count($db_res[1])>0){
            $error = strtolower($db_res[1][0]);
            if(strstr($error,"unknown") != FALSE){
                //create the database
                $db_res = db_get_connection(
                    $config["username"], 
                    $config["password"],
                    $config["servername"],
                    $config["databasename"],
                    true
                );
                $default_conn = $db_res[0];
                $errors = array_merge($errors, $db_res[1]);
                if(count($errors)==0){//continue if there are no errors
                    $db_res= db_create_db($default_conn,$config["databasename"],$config["show_sql_on_errors"]);
                    $errors = array_merge($errors, $db_res[1]);
                }
                //close default conection
                $default_conn = null;
                if(count($errors)==0){
                    //get the connection to the created db
                    if($try_again == true){
                        $tools_res = tools_ensure_garden_exists($config,false);
                        $errors = array_merge($errors, $tools_res[1]);
                        if(count($errors)==0){
                            $garden_conn = $tools_res[0];
                            //create garden combs from structure
                            //db_ct($conn,$table_name, $columns, $show_errors)
                            foreach ($structure as $table => $definition) {
                                if(tools_startsWith($table,"_")){
                                    continue;
                                }
                                $colmuns = db_definition_to_cols($definition);
                                $db_res = db_ct($garden_conn, $table, $colmuns[0],$config["show_sql_on_errors"]);
                                $errors = array_merge($errors, $db_res[1]);
                            }
                        }
                        return $tools_res;
                    }else{
                        $res_data = null;
                        array_push($errors,"Failed to establish connection to resources");
                    }
                }
            }
        }else{
            $res_data = $db_res[0]; //the connection to this garden db
        }
        $res[0] = $res_data;
        if(count($errors)>0){
            $res[0] = null; //nullify results if there are any errors
            $res[1] = $errors;
        }
        return $res;
    }

    function tools_get_garden($conn,$show_errors,$structure){
        $res = array(null,array());
        $node_name = "sections";
        $table_name = Inflect::singularize($node_name);
        // $query = array(
        //     "_a" => array(),
        //     "comb" => array(
        //         "hive" => array(
        //             "modules" => array(
        //                 "hive" => array(
        //                     "modules" => array()  
        //                 )
        //             )  
        //         )
        //     ),
        //     "section_fragiles" => array(
        //         "section" => array(
        //             "comb" => array(
        //                 "hive" => array(
        //                     "modules" => array()  
        //                 )
        //             ),
        //         )
        //     )
        // );
        $query = array(
            "_a" => array(),
            "comb" => array(
                "hive" => array(
                    "modules" => array(
                        "hive" => array(
                            "modules" => array()  
                        )
                    )  
                )
            )
        );
        $tools_res = tools_read($node_name,$query,$structure,$conn,"",array(),false,array()); 
        //nyd
        //db_read_indexed($conn,"SELECT * FROM hive ",$show_errors);
        $query_form = array($node_name=>$query);
        if(count($tools_res[1])==0){
            //formulate sql to be exectuted
            $cols_sql = rtrim(trim($tools_res[0]["temp_cols_sql"]), ',');
            $sql = "SELECT " . $cols_sql . " FROM " .  $table_name . " " . " " . $tools_res[0]["temp_inner_join_sql"];
            $db_res = db_run(array("sql" => $sql,"conn" => $conn));
            $res[EI] = array_merge($res[EI],$db_res[EI]);
            //build the data into honey structure
            $rows = $db_res[RI]["data"];
            $honey = array();
            foreach ($rows as $row_index => $row_value) {
                foreach ($row_value as $path_to => $path_value) {
                    if(preg_match('/^\d+$/', $path_to)){
                        continue;
                    }else{
                        //desconstruct the path to the value
                        $path_parts = explode("__",$path_to); 

                        //walk through the honey
                        $honey_ref = &$honey;
                        for ($i=0; $i < count($path_parts); $i++) { 
                            $path_part = $path_parts[$i];
                            //detect if its the last part which indicates value
                            if($i+1 == count($path_parts)){
                                //nyd
                                //consult the structure to know the data type to 
                                //use to render the value

                                //nyd
                                //insert value
                                $honey_ref[$path_part] = $path_value;
                                unset($honey_ref); //http://php.net/manual/en/language.references.arent.php
                                continue;
                            }
                            if(!array_key_exists($path_part,$honey_ref)){
                                //determine if its a collection or an object
                                $singular  = Inflect::singularize($path_part);
                                if($singular == $path_part){//then path_part was singular
                                    //create an array then create and object array inside this one
                                    $honey_ref[$path_part] = array();
                                    $honey_ref = &$honey_ref[$path_part];
                                }else{
                                    $honey_ref[$path_part] = array(array());
                                    $honey_ref = &$honey_ref[$path_part][0];
                                }
                            }else{
                                //the key exists
                                //determine if its a collection or an object
                                $singular  = Inflect::singularize($path_part);
                                if($singular == $path_part){//then path_part was singular
                                    //get a refrence to this object
                                    $honey_ref = &$honey_ref[$path_part];
                                }else{
                                    //get the reference to the last element of the array
                                    $honey_ref = &$honey_ref[$path_part][count($honey_ref[$path_part])-1];
                                }
                            }
                        }                           
                    }
                }
            }

            //echo "<br/>^^^^^^^^^^^^^^<br/>";
            //var_dump($honey);
            //get the child queries at every node of the query
            //using the honey as parent refreneces for where clauses
            $chilren_paths = $tools_res[0]["temp_children"];
            for($c=0; $c < count($chilren_paths); $c++) {
                $chilren_path = $chilren_paths[$c];
                echo "<br/>processing: " . $chilren_path . "<br/>";
                $source_of_truth = $honey;
                $query_source = $query_form;
                //echo "<br/>";
                //var_dump($honey);
                //echo "<br/>";
                $former_ref_kind = "none";
                //echo $chilren_path . "<br/>";
                //desconstruct the path to the children
                $children_path_parts = explode("__",$chilren_path);
                for ($i=0; $i < count($children_path_parts); $i++) {
                    $children_path_part = $children_path_parts[$i];
                    //echo $children_path_part . "<br/>";
                    //detect if its the last part which indicates evaluation
                    if($i+1 == count($children_path_parts)){
                        $child_table_name  = Inflect::singularize($children_path_part);
                        //the source of truth has to contibute parent ids
                        $id_sql = array();
                        if($former_ref_kind == "array"){
                            $parent_table_name  = Inflect::singularize($children_path_parts[$i-1]);
                            foreach ($source_of_truth as $obj) {
                                if(count($id_sql)==0){
                                    array_push($id_sql,array(
                                        $parent_table_name . "_id",
                                        "=",
                                        $obj["id"]
                                    ));
                                }else{
                                    //get the former entry
                                    $entry = $id_sql[0];
                                    array_push($id_sql,array(
                                        $entry,
                                        "OR",
                                        array(
                                            $parent_table_name . "_id",
                                            "=",
                                            $obj["id"]
                                        )
                                    ));
                                }
                                
                            }
                        }elseif($this_is_an_object && $former_ref_kind == "object"){
                            $obj = $source_of_truth;
                            $id_sql = $id_sql . " " . $parent_table_name . "_id = " . $obj["id"] . " OR ";
                            if(count($id_sql)==0){
                                $id_sql[0] = array(
                                    $parent_table_name . "_id",
                                    "=",
                                    $obj["id"]
                                );
                            }else{
                                //get the former entry
                                $entry = $id_sql[0];
                                $id_sql[0] = array(
                                    $entry,
                                    "OR",
                                    array(
                                        $parent_table_name . "_id",
                                        "=",
                                        $obj["id"]
                                    )
                                );
                            }
                        }
                        //echo $id_sql . "<br/>";
                        //var_dump($id_sql);
                        //nyd
                        //get honey of these children
                        $child_node_name = $children_path_part;
                        $child_query = $query_source[$children_path_part];
                        $tools_children_res = tools_read($child_node_name,$child_query,$structure,$conn,"",array(),true,$id_sql); 

                        //inject that honey into current honey structure

                        unset($source_of_truth);
                        //nyd
                        //some thing is missing here
                        continue;
                    }

                    //determine if path is a collection or an object
                    $singular  = Inflect::singularize($children_path_part);
                    if($singular == $children_path_part){
                        $this_is_an_object = true;
                        if($this_is_an_object && $former_ref_kind == "none"){
                            //the sot is a single object at this refrence
                            $obj = $source_of_truth[$children_path_part];
                            $source_of_truth = $obj;
                            $former_ref_kind = "object";
                            $query_source = $query_source[$children_path_part];
                        }elseif($this_is_an_object && $former_ref_kind == "array"){
                            //the sot is an array of objects
                            //where each object will contribute this path as the 
                            //new source of truth
                            $temp_sot = array();
                            foreach ($source_of_truth as $obj) {
                                $temp_ref = $obj[$children_path_part];
                                array_push($temp_sot,$temp_ref);
                            }
                            $source_of_truth = $temp_sot;
                            $former_ref_kind = "array";
                            $query_source = $query_source[$children_path_part];
                        }elseif($this_is_an_object && $former_ref_kind == "object"){
                            //the sot is an object which has an object that
                            //has to be the new source of truth
                            $obj = $source_of_truth[$children_path_part];
                            $source_of_truth = $obj;
                            $former_ref_kind = "object";
                            $query_source = $query_source[$children_path_part];
                        }
                    }else{
                        $this_is_an_array = true;
                        //this points to an array of objects
                        if($this_is_an_array && $former_ref_kind == "none"){
                            //the sot is an array of objects at this refrence
                            $temp_sot = array();
                            //var_dump($source_of_truth);
                            $temp_ref = $source_of_truth[$children_path_part];
                            foreach ($temp_ref as $obj) {
                                array_push($temp_sot,$obj);
                            }
                            $source_of_truth = $temp_sot;
                            $former_ref_kind = "array";
                            $query_source = $query_source[$children_path_part];
                        }elseif($this_is_an_array && $former_ref_kind == "object"){
                            //the sot is an object which has an array that
                            //has objects that are to be the new source of truth
                            $temp_sot = array();
                            $temp_ref = $source_of_truth[$children_path_part];
                            foreach ($temp_ref as $obj) {
                                array_push($temp_sot,$obj);
                            }
                            $source_of_truth = $temp_sot;
                            $former_ref_kind = "array";
                            $query_source = $query_source[$children_path_part];
                        }elseif($this_is_an_array && $former_ref_kind == "array"){
                            //the sot is an array of objects where each  has an array that
                            //has to be the new source of truth
                            $temp_sot = array();
                            foreach ($source_of_truth as $obj) {
                                $inner_array = $obj[$children_path_part];
                                foreach ($inner_array as $obj_sot) {
                                    array_push($temp_sot,$obj_sot);
                                }
                            }
                            $source_of_truth = $temp_sot;
                            $former_ref_kind = "array";
                            $query_source = $query_source[$children_path_part];
                        }
                    }
                }
                
            }
            //echo "<br/>^^^^^^^^^^^^^^<br/>";

            $res[RI] = array($sql,$rows,$honey,$query_form,$chilren_paths);
            $structure =  $tools_res[2];
            array_push($res,$structure);//the 
        }else{
            $res[EI] = $tools_res[1];
        }
        return $res;
    }
/*
    //walk through the honey
    $honey_ref = $honey;
    for ($i=0; $i < count($path_parts); $i++) { 
        $path_part = $path_parts[$i];
        //detect if its the last part which indicates value
        if($i+1 == count($path_parts)){
            //nyd
            //consult the structure to know the data type to 
            //use to render the value

            //nyd
            //insert value
            $honey_ref[$path_part] = $path_value;
            continue;
        }
        if(!array_key_exists($path_part,$honey_ref)){
            $honey_ref[$path_part] = array();
            //$honey_ref = $honey_ref[$path_part];
        }
    }
*/
    

    function tools_read($node_name,$node,$structure,$connection,$path,$children,$is_child_read,$parent_w){
        $res = array(null,array());
        $res_data = array();
        $errors = array();
        $attributes_found = false;
        $temp_cols_sql = "";
        $temp_inner_join_sql = "";
        $path = (strlen($path) == 0 ) ? ($node_name) : ($path . "__" .$node_name);
        


        $table_name = Inflect::singularize($node_name);
        
        //_a must always be there if not it is inserted as the first attribute
        if(!array_key_exists("_a",$node)){
            $node = array_merge(array("_a"=>array()),  $node); 
        }

        //if the parents_where is not empty
        //and this node has no _w then its injected in
        if(count($parent_w) > 0 && !array_key_exists("_w",$node)){
            $node["_w"] = array();
        }

        foreach ($node as $node_key => $node_key_value) {
            if($node_key == "_a"){
                //string * means get everything
                //array() empty array means delete all my attributes after every thing
                $mixer_res = mixer_interprete_attributes($node_name,$node_key_value,$structure,$connection,$path);
                $temp_cols_sql = $mixer_res[0];
                $errors = array_merge($errors, $mixer_res[1]);
                $structure = $mixer_res[2];
                continue;
            }


            if($node_key == "_w"){
                $where_array = array_merge($node_key_value,$parent_w);
                $mixer_res = mixer_interprete_where($node,$node_name,$where_array,$structure,$connection,$path);
                continue;
            }
            
            
            
            //if execution reaches here it means this is a child node or parent node
            //detect parent
            //you must be singular and have parent_id column in this table
            $singular  = Inflect::singularize($node_key);
            $parent_id_exists = array_key_exists($singular."_id", $structure[$table_name]);
            if($node_key == $singular && $parent_id_exists ){
                //echo "foo";
                $tools_res = tools_read($node_key,$node_key_value,$structure,$connection,$path,$children,false,array());
                //var_dump($tools_res);
                $temp_cols_sql = $temp_cols_sql . " " . $tools_res[0]["temp_cols_sql"];
                $errors = array_merge($errors, $tools_res[1]);
                $structure = $tools_res[2];
                $children =  $tools_res[0]["temp_children"];
                $temp_inner_join_sql = " INNER JOIN ".$singular." ON ".$table_name.".".$singular."_id=".$singular.".id " . $tools_res[0]["temp_inner_join_sql"];
                continue;
            }


            //detect children
            //singular must be a table in the structure with table_id as parent column
            $plural  = Inflect::pluralize($node_key);
            $child_id_exists = array_key_exists($table_name."_id", $structure[$singular]);
            //echo $plural . "--" . $node_key . "--" . $table_name . "--" . $singular . "=>" . $child_id_exists ."<br/>";
            if($node_key == $plural && $child_id_exists ){
                //echo "foo";
                array_push($children,$path."__".$node_key);
                //var_dump($children);
                $tools_res = tools_read($node_key,$node_key_value,$structure,$connection,$path,$children,false,array());
                $errors = array_merge($errors, $tools_res[1]);
                $structure = $tools_res[2];
                $children =  $tools_res[0]["temp_children"];
                //var_dump($children);
                continue;
            }

            //if we have reached this far our query is wrong here
            //we can raise an error
            array_push($errors,"Invalid path: " . $path."__".$node_key);
        
        }

        $res_data = array(
            "temp_cols_sql" => $temp_cols_sql,
            "temp_inner_join_sql" => $temp_inner_join_sql,
            "temp_children" => $children
        ); 
        $res[0] = $res_data;
        if(count($errors)>0){
            $res[0] = null; //nullify results if there are any errors
            $res[1] = $errors;
        }
        array_push($res,$structure);
        return $res;
    }

    function tools_create($node){
        $res = array(null,array());
        $res_data = array();
        $errors = array();
        if(array_key_exists("_hive",$node)){
            
            //nyd
            //authorised to access this resource

            //creating a new hive for this user in this garden
            //nyd
            //validate data
            //hive name has to be unique
            //email has to be unique etc
            //and phone number

            $res_data["_hive"] = $node["_hive"];
            $hive_name = strtolower($node["_hive"]);
            //_using what
        }

        $res[0] = $res_data;
        if(count($errors)>0){
            $res[0] = null; //nullify results if there are any errors
            $res[1] = $errors;
        }
        return $res;
    }

    function tools_respond($data,$errors){
        global $conn;
        global $processes_route;
        global $foo;
        $processes_route = TRUE;
        $conn = null;
        //The response
        $data["errors"] = $errors;
        $data["foo"] = $foo;
		//include the header 
        header("Content-type: application/json"); 
        $res =  json_encode($data);
		echo $res;
		exit(0);
    }
?>
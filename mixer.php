<?php
    function mixer_interprete_attributes($table_name_s,$atts,$structure,$conn,$path){
        $errors = array();
        //make singular the table name
        $table_name = Inflect::singularize($table_name_s);

        //preprocess attributes
        $cols = array();
        if(is_string($atts)){
            $atts = trim(strtolower($atts));
            if($atts != "*"){
                $atts = explode(" ",$atts);
            }
        }
        
        if(is_array($atts)){
           
            foreach ($atts as $column_query) {
                //nyd
                //interprete the column query
                $temp_col = $column_query;
                array_push($cols,$temp_col);
            }
        }

        //if table does not exist then it will be created on this connection
        //when the setting of STRICT_HIVE == false;
        if(!array_key_exists($table_name,$structure) && STRICT_HIVE == false ){
            //create this table
            $colmuns = db_definition_to_cols($cols);
            $db_res = db_ct($conn, $table_name, $colmuns[0],SHOW_SQL_ON_ERRORS);
            $errors = array_merge($errors, $db_res[1]);
            
            //add this table to current structure and save it
            $structure[$table_name] = array();
            foreach ($cols as $col_nem) {
                $structure[$table_name][$col_nem] = db_default_column_def();
            }
            file_put_contents($structure["_for"], json_encode($structure));

            //nyd
            //implement transactions and rollback
        }
        if(!array_key_exists($table_name,$structure) && STRICT_HIVE == true ){
            //we have an error here because the table doesnot exist and we cannot create one
            array_push($errors,"Comb " . $table_name . " does not exist");
            return array("",$errors,$structure);
        }

        //this if comes after checking the existence of the table above
        $table_cols = $structure[$table_name];
        if($atts == "*" || (is_array($atts) && count($atts) == 1 && trim($atts[0]) == "*" )  ){
            $cols = array("id");
            //get all the cols for this table including the id
            foreach ($table_cols as $col_name => $col_def) {
                array_push($cols,$col_name);
            }
        }
        //if its empty
        if(count($atts) == 0){
            //this means you want to get all the attributes and then delete them 
            //when processing is done
            $cols = array("id");
            //get all the cols for this table including the id
            foreach ($table_cols as $col_name => $col_def) {
                array_push($cols,$col_name);
            }
            //nyd
            //indicate that these attributes/columns will need to be deleted 
            //after processing and actually delete them from the results
        }

        //the column must be part of the structure
        //if not create it and alter table when STRICT_HIVE == false
        $sql = " ";
        foreach ($cols as $col_name) {
            if(tools_startsWith($col_name,"_")){
                continue;
            }
            if(!array_key_exists($col_name,$table_cols) && $col_name != "id" && STRICT_HIVE == false ){
                //alter table here and structure
                $colm_sql = db_vcn($col_name,100); 
                $db_res = db_ac($conn,$table_name, $colm_sql);
                $errors = array_merge($errors, $db_res[1]);
                //if there were any errors we cannot continue
                if(count($db_res[1]) > 0){
                    return array(null,$errors,$structure);
                }
                //add this column to the structure
                $structure[$table_name][$col_name] = db_default_column_def();
                file_put_contents($structure["_for"], json_encode($structure));
            }
            $temp_path_to = $path . "__" . $col_name;
            $sql = $sql . " " . $table_name . "." . $col_name . " as ". $temp_path_to .",";
        }
        return array($sql,$errors,$structure);
    }


    function mixer_interprete_where($node,$where_array,$structure,$connection,$path){
        $errors = array();



        
        return array($sql,$errors,$structure);
    }
?>
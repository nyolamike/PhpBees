<?php

    //the process of converting the nectoriod
    //into sql_segments
    function segmentation_run($nectoroid,$structure,$connection){
        $res = array(null,array(),$structure);

        //tools_dump("nectoroid",__FILE__,__LINE__,$nectoroid,"unit_conversions");
        //go through the entire nectorid processing
        //node by node on the root
        $whole_honey = array();
        foreach ($nectoroid as $root_node_name => $root_node) {
            //tools_dump("@: ",__FILE__,__LINE__,$root_node_name);
            if(tools_startsWith($root_node_name,"_")){
                //extractions
                if(tools_startsWith($root_node_name,"_xtu_")){
                    if(!array_key_exists("xtu")){
                        $whole_honey["xtu"] = array();
                    }
                    $nn = str_replace("_xtu_","",$root_node_name);
                    $whole_honey["xtu"][$nn] = $root_node;

                    // //tools_dumpx("@root_node: ",__FILE__,__LINE__,$root_node);
                    // $sr_res = segmentation_run($root_node,$structure,$connection);
                    // //tools_dumpx("@1xtu res: ",__FILE__,__LINE__,$sr_res[BEE_RI]);
                    // $hasr_res = hive_after_segmentation_run($sr_res,$root_node,$structure,$connection);
                    // $res[BEE_EI] = array_merge($res[BEE_EI],$hasr_res[BEE_EI]);
                    // //tools_dumpx("@2xtu res: ",__FILE__,__LINE__,$hasr_res[BEE_RI]);
                    // $honey_to_extract = $hasr_res[BEE_RI];
                    // $whole_honey[$node_name] = $honey_to_extract;
                }
                continue;
            }
            $config = array(
                "path" => "",
                "node_name" => $root_node_name,
                "parents_w" => array(),
                "hive_structure" => $res[2],
                "children" => array(),
                "is_child_segmentation_run" => false
            );
            $srp_res = segmentation_run_process($root_node,$config,$connection);
            $res[2] = $srp_res[2];//structure
            $whole_honey[$root_node_name] = $srp_res[BEE_RI];
            $res[BEE_EI] = array_merge($res[BEE_EI],$srp_res[BEE_EI]);
            //var_dump($srp_res);
        }
        $res[BEE_RI] = $whole_honey;
        return $res;
    }

    function segmentation_run_process($nectoroid,$config,$connection){
        $res = array(array(
            "temp_sections_sql" => "",
            "temp_children" => array(),
            "temp_inner_join_sql" => "",
            "temp_where_sql" => "",
            "temp_groupby_sql" => "",
            "temp_orderby_sql" => null,
            "temp_limit_sql" => null,
            "temp_hash" => null,
            "temp_having_sql" => ""
        ),array());


        //variables
        $node = $nectoroid;
        $path = $config["path"];
        $node_name = $config["node_name"];
        $parents_w = (isset($config["parents_w"]))?$config["parents_w"]:array();
        $hive_structure = $config["hive_structure"];
        $res[BEE_RI]["temp_children"] = (isset($config["children"]))?$config["children"]:array();
        $is_child_segmentation_run = isset($config["is_child_segmentation_run"]);

 
        //the current path becomes
        $path = (strlen($path) == 0 ) ? ($node_name) : ($path . BEE_SEP .$node_name);
        //get the name of the comb
        $comb_name = Inflect::singularize($node_name);
        //_a must always be there if not it is inserted as the first attribute
        if(!array_key_exists(BEE_ANN,$node)){
            $node = array_merge(array("_a"=>array()),  $node); 
        }
        //if the parents_where is not empty
        //and this node has no _w then its injected in
        if(count($parents_w) > 0 && !array_key_exists(BEE_WNN,$node)){
            $node[BEE_WNN] = array();
        }
        //process the node
        foreach ($node as $node_key => $node_key_value) {

            if($node_key == "_at"){
                //this is used for extractions so ignore it and move on
                continue;
            }

            if(tools_startsWith($node_key,"_fx_")){
                continue;
            }

            //echo $node_name . " <br/>";
            if($node_key == BEE_ANN){
                //string * means get everything
                //array() empty array means delete all my attributes after every thing
                $sra_res = segmentation_run_a($node_key_value,array(
                    "node_name" => $node_name,
                    "hive_structure" => $hive_structure,
                    "path" => $path,
                    "parent_node" => $node
                ),$connection);
                $res[BEE_RI]["temp_sections_sql"] = $sra_res[BEE_RI];
                $res[BEE_EI] = array_merge($res[BEE_EI], $sra_res[BEE_EI]);
                $hive_structure = $sra_res[2];
                if(count($sra_res[BEE_EI])>0){ //dont continue if we have errors with _a processing
                    return $res;
                }
                continue;
            }

            if($node_key == BEE_WNN){
                $where_array = array_merge($node_key_value,$parents_w);
                $srw_res = segmentation_run_w($where_array,$node_name,$node,$hive_structure);
                $res[BEE_RI]["temp_where_sql"] = $srw_res[BEE_RI];
                $res[BEE_EI] = array_merge($res[BEE_EI], $srw_res[BEE_EI]);
                $hive_structure = $srw_res[2];
                if(count($srw_res[BEE_EI])>0){ //dont continue if we have errors with _w processing
                    return $res;
                }
                continue;
            }

            //having
            if($node_key == "_h"){
                $srw_res = segmentation_run_w($node_key_value,$node_name,$node,$hive_structure);
                $res[BEE_RI]["temp_having_sql"] = $srw_res[BEE_RI];
                $res[BEE_EI] = array_merge($res[BEE_EI], $srw_res[BEE_EI]);
                $hive_structure = $srw_res[2];
                if(count($srw_res[BEE_EI])>0){ //dont continue if we have errors with _h processing
                    return $res;
                }
                continue;
            }
            

            //group by
            if($node_key == "_gb" || $node_key == "_g"){
                $str_temp = "";
                foreach ($node_key_value as $gbi) { //group by item
                    if(strpos($gbi,".")>0){
                        $gbis = explode(".",$gbi);
                        $cnx = Inflect::singularize($gbis[0]); 
                        $str_temp = $str_temp .  ($cnx.".".$gbis[1]) . ", ";
                    }else{
                        $str_temp = $str_temp .  ($comb_name .".".$gbi) . ", ";
                    }
                }
                $res[BEE_RI]["temp_groupby_sql"] = $str_temp;
                continue;
            }


            //order by
            if($node_key == "_ob" || $node_key == "_od" || $node_key == "_o" || $node_key == "_d" ||  $node_key == "_ao" ||  $node_key == "_do"
                || $node_key == "_oba" ||  $node_key == "_obd" ||  $node_key == "_asc" ||  $node_key == "_desc"){
                $str_temp = "";
                $kind_temp = "ASC";
                if($node_key == "_od" || $node_key == "_d" || $node_key == "_do" ||  $node_key == "_obd" ||  $node_key == "_desc"){
                    $kind_temp = "DESC";
                }
                foreach ($node_key_value as $gbi) { //group by item
                    if(strpos($gbi,".")>0){
                        $gbis = explode(".",$gbi);
                        $cnx = Inflect::singularize($gbis[0]); 
                        $str_temp = $str_temp .  ($cnx.".".$gbis[1]) . ", ";
                    }else{
                        $str_temp = $str_temp .  ($comb_name .".".$gbi) . ", ";
                    }
                }
                $res[BEE_RI]["temp_orderby_sql"] = array(
                    "sql" => $str_temp,
                    "kind" => $kind_temp
                ) ;
                continue;
            }

            //limit offset pagination
            if($node_key == "_pg"){
                $pgs = explode(".",strval($node_key_value));
                $limit = intval($pgs[0]);
                $page_number = count($pgs)>1?intval($pgs[1]):0;
                if($page_number > 0){
                    $offset = ($page_number-1) * $limit;
                    $res[BEE_RI]["temp_limit_sql"] = array(
                        "limit" => $limit,
                        "offset" => $offset
                    );
                }else{
                    $res[BEE_RI]["temp_limit_sql"] = array(
                        "limit" => $limit,
                        "offset" => null
                    );
                }
                continue;
            }


            //_hash:"num"
            if($node_key == "_hash"){
                //tools_dumpx("_hash",__FILE__,__LINE__,array($node_key_value,$path));
                $tx_name = trim($node_key_value);
                $has_name = strlen($tx_name)>0?$tx_name:"hash";
                $res[BEE_RI]["temp_hash"] = $path . BEE_SEP . $has_name;
                continue;
            }





            //if execution reaches here it means this is a child node or parent node

            //detect parent
            //you must be singular and have parent_id column in this table
            $singular  = Inflect::singularize($node_key);
            $SWO = tools_startsWith($singular,"other_");
            $ON = ($SWO)?str_replace("other_","",$singular):$singular;
            $parent_id_exists = array_key_exists(
                (($SWO)?$ON:$singular)."_id", 
                $hive_structure[$comb_name]
            );
            // if($comb_name =="unit" || true){
            //     tools_dump("parent_id_exists",__FILE__,__LINE__,$hive_structure[$comb_name]);
            //     tools_dump($singular,__FILE__,__LINE__,$singular);
            // }
            //tools_dump("parent segmentation",__FILE__,__LINE__,array($node_key, $singular, $parent_id_exists));
            if($node_key == $singular && $parent_id_exists ){
                $s_res = segmentation_run_process($node_key_value,array(
                    "path" => $path,
                    "node_name" => $node_key,
                    "hive_structure" => $hive_structure,
                    "parents_w" => array(),
                    "children" => $res[BEE_RI]["temp_children"],
                    "is_child_segmentation_run" => $is_child_segmentation_run
                ),$connection);
                $res[BEE_RI]["temp_sections_sql"] = $res[BEE_RI]["temp_sections_sql"] . " " . $s_res[BEE_RI]["temp_sections_sql"];
                $res[BEE_EI] = array_merge($res[BEE_EI], $s_res[BEE_EI]);
                $hive_structure = $s_res[2];
                $res[BEE_RI]["temp_children"] =  $s_res[BEE_RI]["temp_children"];

                
            if($SWO){
                    $res[BEE_RI]["temp_inner_join_sql"] .= " INNER JOIN ".$ON." AS ". $singular ." ON ".$comb_name.".".$singular."_id=".$singular.".id " . $s_res[BEE_RI]["temp_inner_join_sql"];
                }else{
                    $res[BEE_RI]["temp_inner_join_sql"] .= " INNER JOIN ".$singular." ON ".$comb_name.".".$singular."_id=".$singular.".id " . $s_res[BEE_RI]["temp_inner_join_sql"];
                }
                // if($singular == "unit"){
                //     //tools_dump("print_aspect_kindfooo",__FILE__,__LINE__,$res[BEE_RI]["temp_inner_join_sql"]);
                // }
                // if($singular == "other_unit"){
                //     //tools_dump("print_aspect_kindfaaaaaa",__FILE__,__LINE__,$res[BEE_RI]["temp_inner_join_sql"]);
                // }
                continue;
            }


            //detect children
            //singular must be a table in the structure with table_id as parent column
            $plural  = Inflect::pluralize($node_key);
            $child_id_exists = array_key_exists($comb_name."_id", $hive_structure[(($SWO)?$ON:$singular)]);
            if($node_key == $plural && $child_id_exists ){
                //echo "foo";
                array_push($res[BEE_RI]["temp_children"],$path.BEE_SEP.$node_key);
                //var_dump($res[BEE_RI]["temp_children"]);
                $s_res = segmentation_run_process($node_key_value,array(
                    "path" => $path,
                    "node_name" => $node_key,
                    "hive_structure" => $hive_structure,
                    "parents_w" => array(),
                    "children" => $res[BEE_RI]["temp_children"],
                    "is_child_segmentation_run" => true
                ),$connection);
                $res[BEE_EI] = array_merge($res[BEE_EI], $s_res[BEE_EI]);
                $hive_structure = $s_res[2];
                $res[BEE_RI]["temp_children"] =  $s_res[BEE_RI]["temp_children"];
                //var_dump($res[BEE_RI]["temp_children"]);
                continue;
            }


            //if we have reached this far our query is wrong here
            //we can raise an error
            array_push($res[BEE_EI],"Invalid path: " . $path.BEE_SEP.$node_key);
        }

        //tools_dumpx("detect",__FILE__,__LINE__,$node,"unit");

        
        if(count($res[BEE_EI])>0){
            $res[BEE_RI] = null; //nullify results if there are any errors
        }
        array_push($res,$hive_structure);
        return $res;
    }

    //processes the attributes node _a
    function segmentation_run_a($node,$config,$connection){
        $node_name = $config["node_name"];
        $hive_structure = $config["hive_structure"];
        $path = $config["path"];
        $conn = $connection;
        $parent_node = $config["parent_node"];

        $errors = array();
        //make singular the comb name
        $comb_name = Inflect::singularize($node_name);
        //preprocess attributes
        $sections = array();
        if(is_string($node)){
            $node = trim(strtolower($node));
            if($node != "*"){
                $node = explode(" ",$node);
            }
        }     
        if(is_array($node)){
            foreach ($node as $section_query) {
                //nyd
                //interprete the column query
                $temp_sec = $section_query;
                array_push($sections,$temp_sec);
            }
        }

        $SWO = tools_startsWith($node_name,"other_");
        $ON = ($SWO)?str_replace("other_","",$node_name):$node_name;
    
        //if comb does not exist then it will be created on this connection
        //when the setting of BEE_STRICT_HIVE == false;
        if(!array_key_exists($comb_name,$hive_structure) && BEE_STRICT_HIVE == false && !$SWO ){
            //create this table
            $hrsq_res = hive_run_secture_sqlization($sections);
            $hrc_res = hive_run_ct($connection, $comb_name, $hrsq_res[BEE_RI]);
            $errors = array_merge($errors, $hrc_res[BEE_EI]);
            //add this comb to current structure and save it
            $hive_structure[$comb_name] = array();
            foreach ($sections as $section_name) {
                $hive_structure[$comb_name][$section_name] = hive_run_default_secture();
            }
            //var_dump($hive_structure);
            file_put_contents("bee/".$hive_structure[BEE_FNN], json_encode($hive_structure));
        }
        if(!array_key_exists($comb_name,$hive_structure) && BEE_STRICT_HIVE == true && !$SWO ){
            //we have an error here because the comb doesnot exist and we cannot create one
            array_push($errors,"Comb " . $comb_name . " does not exist");
            return array("",$errors,$hive_structure);
        }
    
        //this if 
        //must come after checking the existence of the comb above

        $sectures = $hive_structure[(($SWO)? $ON : $comb_name)];
        if($node == "*" || (is_array($node) && count($node) == 1 && trim($node[0]) == "*" )  ){
            $sections = array("id");
            //get all the cols for this table including the id
            foreach ($sectures as $section_name => $secture) {
                if(tools_startsWith($section_name,"_") || tools_startsWith($secture,"_")){
                    continue;
                }
                array_push($sections,$section_name);
            }
        }
        //if its empty
        if(count($node) == 0){
            //this means you want to get all the attributes and then delete them 
            //when processing is done
            $sections = array("id");
            //get all the cols for this table including the id
            foreach ($sectures as $section_name => $secture) {
                if(tools_startsWith($section_name,"_") || tools_startsWith($secture,"_")){
                    continue;
                }
                array_push($sections,$section_name);
            }
            //nyd
            //indicate that these attributes/sections will need to be deleted 
            //after processing and actually delete them from the results
        }
    
        //the section must be part of the structure
        //if not create it and alter table when BEE_STRICT_HIVE == false
        $sql = " ";
        $hive_inner_attributes = array(
            "id","guid","inserted_by","is_deleted","last_modified_by","time_inserted","time_last_modified"
        );
        foreach ($sections as $section_name) {
            $do_this_instead = "";
            if(tools_startsWith($section_name,"_")){
                $fall_through = "";
                //fx
                if(tools_startsWith($section_name,"_fx_")){
                    $fx_node = $parent_node[$section_name];
                    $bsfr_res = bee_sqllization_fx_run($fx_node,$comb_name,$hive_structure);
                    $errors = array_merge($errors,$bsfr_res[BEE_EI]);
                    $sectfx = substr($section_name,strlen("_fx_"));
                    $temp_path_to = $path . BEE_SEP . $sectfx;
                    $subsql = $bsfr_res[BEE_RI] . " as " . $temp_path_to;
                    $do_this_instead = $subsql;
                    $fall_through = true;
                }
                
                if($fall_through == false){
                    continue; //just in case anything wired went through
                }
            }
            if(strlen($do_this_instead)== 0 && !array_key_exists($section_name,$sectures) && in_array($section_name,$hive_inner_attributes) == false && BEE_STRICT_HIVE == false && !$SWO ){
                //alter table here and structure
                $section_sql = hive_run_tn($section_name); 
                $hra_res = hive_run_ac($connection,$section_name, $section_sql);
                $errors = array_merge($errors, $hra_res[BEE_EI]);
                //if there were any errors we cannot continue
                if(count($hra_res[BEE_EI]) > 0){
                    return array(null,$errors,$hive_structure);
                }
                //add this section to the structure
                $hive_structure[$comb_name][$section_name] = hive_run_default_secture();
                file_put_contents("bee/".$hive_structure[BEE_FNN], json_encode($hive_structure));
            }
            $temp_path_to = $path . BEE_SEP . $section_name;
            if(strlen($do_this_instead)>0){
                $sql = $sql . " " . $do_this_instead .",";
            }else{
                $sql = $sql . " " . $comb_name . "." . $section_name . " as ". $temp_path_to .",";
            }
        }

        return array($sql,$errors,$hive_structure);
    }

    //processes the where node _w
    function segmentation_run_w($where_array,$node_name,$node,$structure){
        $errors = array();
        $sql = "";
        $comb_name  = Inflect::singularize($node_name);
        for ($i=0 ; $i < count($where_array); $i++ ) { 
            $sql = $sql . " " . segmentation_run_where_entry($where_array[$i],$comb_name,$node_name);
        }
        ///var_dump($sql);
        return array($sql,$errors,$structure);
    }
    
    //process a where entry
    function seg_fx($comb_name,$left,$node_name){
        if(stripos($left,"_fx_") > -1){
            $left = "" . $node_name . BEE_SEP . str_replace("_fx_","",$left);
        }else{
            $left = "" . $comb_name . "." . $left;
        }
        return $left;
    }
    function seg_fxr($comb_name,$right,$node_name){
        if(stripos($right,"_fx_") > -1){
            //tools_dump("seg_fxr ",__FILE__,__LINE__,stripos($right,"_fx_"));
            $right = "" . $node_name . BEE_SEP . str_replace("_fx_","",$right);
        }
        return $right;
    }
    function segmentation_run_where_entry($where_entry,$comb_name,$node_name){
        $left = $where_entry[0];
        $condition = trim($where_entry[1]);
        $right = $where_entry[2];
        $sql = "";
        if($condition == "="){
            //left side =  right side
            $val_left = (is_array($left))? segmentation_run_where_entry($left,$comb_name,$node_name) : (seg_fx($comb_name,$left,$node_name));
            $val_right = (is_array($right))? segmentation_run_where_entry($right,$comb_name,$node_name) : seg_fxr($comb_name,$right,$node_name);
            if(!is_numeric($val_right) && stripos($right,"_fx_") < 0){
                $val_right = "'".$val_right."'";
            }elseif(stripos($left,"_fx_") > -1){
                //having
                $val_right = $comb_name . "." . str_replace("_fx_","",$right);
            }
            $sql = $sql . " " . $val_left . " = " . $val_right;
        }elseif($condition == ">=" || $condition == "gte" ||   $condition == "GTE" ){
            //left side =  right side
            $val_left = (is_array($left))? segmentation_run_where_entry($left,$comb_name,$node_name) : (seg_fx($comb_name,$left,$node_name));
            $val_right = (is_array($right))? segmentation_run_where_entry($right,$comb_name,$node_name) : seg_fxr($comb_name,$right,$node_name);
            if(!is_numeric($val_right) && stripos($right,"_fx_") < 0){
                $val_right = "'".$val_right."'";
            }elseif(stripos($left,"_fx_") > -1){
                //having
                $val_right = $comb_name . "." . str_replace("_fx_","",$right);
            }
            $sql = $sql . " " . $val_left . " >= " . $val_right;
        }elseif($condition == "<=" || $condition == "lte" ||   $condition == "LTE" ){
            //left side =  right side
            $val_left = (is_array($left))? segmentation_run_where_entry($left,$comb_name,$node_name) : (seg_fx($comb_name,$left,$node_name));
            $val_right = (is_array($right))? segmentation_run_where_entry($right,$comb_name,$node_name) : seg_fxr($comb_name,$right,$node_name);
            if(!is_numeric($val_right) && stripos($right,"_fx_") < 0){
                $val_right = "'".$val_right."'";
            }elseif(stripos($left,"_fx_") > -1){
                //having
                $val_right = $comb_name . "." . str_replace("_fx_","",$right);
            }
            $sql = $sql . " " . $val_left . " <= " . $val_right;
        }elseif($condition == "!=" || $condition == "ne" ||   $condition == "NE" ){
            //left side =  right side
            $val_left = (is_array($left))? segmentation_run_where_entry($left,$comb_name,$node_name) : (seg_fx($comb_name,$left,$node_name));
            $val_right = (is_array($right))? segmentation_run_where_entry($right,$comb_name,$node_name) : seg_fxr($comb_name,$right,$node_name);
            if(!is_numeric($val_right) && stripos($right,"_fx_") < 0){
                $val_right = "'".$val_right."'";
            }elseif(stripos($left,"_fx_") > -1){
                //having
                $val_right = $comb_name . "." . str_replace("_fx_","",$right);
            }
            $sql = $sql . " " . $val_left . " != " . $val_right;
        }elseif($condition == ">" || $condition == "gt" ||   $condition == "GT" ){
            //left side =  right side
            $val_left = (is_array($left))? segmentation_run_where_entry($left,$comb_name,$node_name) : (seg_fx($comb_name,$left,$node_name));
            $val_right = (is_array($right))? segmentation_run_where_entry($right,$comb_name,$node_name) : seg_fxr($comb_name,$right,$node_name);
            if(!is_numeric($val_right) && stripos($right,"_fx_") < 0){
                $val_right = "'".$val_right."'";
            }elseif(stripos($left,"_fx_") > -1){
                //having
                $val_right = $comb_name . "." . str_replace("_fx_","",$right);
            }
            $sql = $sql . " " . $val_left . " > " . $val_right;
        }elseif($condition == "<" || $condition == "lt" ||   $condition == "LT" ){
            //left side =  right side
            $val_left = (is_array($left))? segmentation_run_where_entry($left,$comb_name,$node_name) : (seg_fx($comb_name,$left,$node_name));
            $val_right = (is_array($right))? segmentation_run_where_entry($right,$comb_name,$node_name) : seg_fxr($comb_name,$right,$node_name);
            if(!is_numeric($val_right) && stripos($right,"_fx_") < 0){
                $val_right = "'".$val_right."'";
            }elseif(stripos($left,"_fx_") > -1){
                //having
                $val_right = $comb_name . "." . str_replace("_fx_","",$right);
            }
            $sql = $sql . " " . $val_left . " < " . $val_right;
        }elseif($condition == "LIKE" || $condition == "like" || $condition == "lk" || $condition == "~" ){
            //left side =  right side
            $val_left = (is_array($left))? segmentation_run_where_entry($left,$comb_name,$node_name) : (seg_fx($comb_name,$left,$node_name));
            $val_right = (is_array($right))? segmentation_run_where_entry($right,$comb_name,$node_name) : seg_fxr($comb_name,$right,$node_name);
            $sql = $sql . " " . $val_left . " LIKE '%" . $val_right . "%' ";
        }elseif($condition == "OR" || $condition == "or" || $condition == "|" || $condition == "||"){
            //left side =  right side
            $val_left = (is_array($left))? segmentation_run_where_entry($left,$comb_name,$node_name) : (seg_fx($comb_name,$left,$node_name));
            $val_right = (is_array($right))? segmentation_run_where_entry($right,$comb_name,$node_name) :  seg_fxr($comb_name,$right,$node_name);
            $sql = $sql . " (" . $val_left . ") OR (" . $val_right . ")";
        }elseif($condition == "AND" || $condition == "and" || $condition == "&" || $condition == "&&"){
            //left side =  right side
            $val_left = (is_array($left))? segmentation_run_where_entry($left,$comb_name,$node_name) : (seg_fx($comb_name,$left,$node_name));
            $val_right = (is_array($right))? segmentation_run_where_entry($right,$comb_name,$node_name) :  seg_fxr($comb_name,$right,$node_name);
            $sql = $sql . " (" . $val_left . ") AND (" . $val_right . ")";
        }
        return $sql;
    }

    function bee_segmentation_update($nectoroid,$structure,$connection, $user_id){
        $res = array(null,array(),$structure);
        //go through the entire nectorid processing
        //node by node on the root
        $whole_honey = array();
        foreach ($nectoroid as $root_node_name => $root_node) {
            if(tools_startsWith($root_node_name,"_")){
                continue;
            }
            $comb_name  = Inflect::singularize($root_node_name);
            $sects = $structure[$comb_name];
            $bspp_res = bee_segmentation_update_process($root_node_name, $comb_name, $root_node, $structure, $connection, $user_id, $whole_honey);
            //tools_dumpx("bspp_res: ",__FILE__,__LINE__,$bspp_res);
            $whole_honey[$root_node_name] = $bspp_res[BEE_RI][$root_node_name];
            $res[BEE_EI] = array_merge($res[BEE_EI],$bspp_res[BEE_EI]);
        }
        $res[BEE_RI] = $whole_honey;
        return $res;
    }

    function bee_segmentation_update_process($node_name,$comb_name, $node, $structure, $connection, $user_id, $whole_honey){
        $res = array(null,array());
        if($comb_name == $node_name){
            $bspps_res = bee_segmentation_update_process_sqllize($node_name, $comb_name, $node, $structure, $user_id, $whole_honey);
            $res[BEE_RI] = $bspps_res[BEE_RI];
            $res[BEE_EI] = array_merge($res[BEE_EI],$bspps_res[BEE_EI]);
        }else{
            // $res[BEE_RI] = array();
            // $res[BEE_RI][$node_name] = array();
            // //many insertions into the same comb
            // for ($i=0; $i < count($node); $i++) { 
            //     $obj = $node[$i];
            //     $bspps_res = bee_segmentation_update_process_sqllize($node_name, $comb_name, $obj, $structure,$user_id, $whole_honey);
            //     array_push($res[BEE_RI][$node_name],$bspps_res[BEE_RI][$node_name]);
            //     $res[BEE_EI] = array_merge($res[BEE_EI],$bspps_res[BEE_EI]);
            // }
            $bspps_res = bee_segmentation_update_process_sqllize($node_name, $comb_name, $node, $structure, $user_id, $whole_honey);
            $res[BEE_RI] = $bspps_res[BEE_RI];
            $res[BEE_EI] = array_merge($res[BEE_EI],$bspps_res[BEE_EI]);
        }
        return $res;
    }

    function bee_segmentation_update_process_sqllize($node_name, $comb_name, $node, $structure,$user_id, $whole_honey){
        $res = array(null,array());
        $sql = "UPDATE " . $comb_name . " SET ";
        $sections_sql = "";
        $where_sql = "";
        foreach ($node as $key => $value) {
            $values_sql = "";
            $section_name = $key;
            //detect special flags
            if(tools_startsWith($section_name,"_")){
                $handled = false; //allows control to fall trough
                //files
                if(tools_startsWith($section_name,"_file_")){
                    $bsefv_res = bee_segmentation_evaluate_file_value($section_name,$value,$comb_name,$structure);
                    $section_name = $bsefv_res[BEE_RI]; 
                    $res[BEE_EI] = array_merge($res[BEE_EI],$bsefv_res[BEE_EI]); 
                    $handled = true;
                }
                //now
                if(tools_startsWith($section_name,"_now_")){
                    $section_name = str_replace("_now_","",$section_name);
                    $value = time();
                    $handled = true;
                }
                //_fk_
                if(tools_startsWith($section_name,"_fk_")){
               
                    $section_name = str_replace("_fk_","",$section_name);
                    $value = "_fk_" . $value . "_kf_";
                    $handled = true;
                }
                //_w
                if(tools_startsWith($section_name,"_w")){
                    //process the where of updating
                    $srw_res = segmentation_run_w($value,$comb_name,$node,$structure);
                    $where_sql = $srw_res[BEE_RI];
                    $res[BEE_EI] = array_merge($res[BEE_EI], $srw_res[BEE_EI]);
                    $hive_structure = $srw_res[2];
                    if(count($srw_res[BEE_EI])>0){ //dont continue if we have errors with _w processing
                        return $res;
                    }
                    $handled = true;
                    continue;
                }
                if($handled == false){
                    continue;
                }
            }

            if(is_string($value) ){
                $prepared = addslashes(strval($value));
                $values_sql .= " '" . $prepared . "'";
            }else if(is_int($value) || is_float($value)){
                $values_sql .= " " . strval($value);
            }
            $sections_sql .= " `".$section_name."` = " . $values_sql . ", ";
        }
        $sections_sql   .= " `time_last_modified` = ".time().",";
        $sections_sql   .= " `last_modified_by` = ".$user_id." ";
        $sql = $sql .  $sections_sql . ((strlen($where_sql)>0)?" WHERE " . $where_sql: "");
        $res[BEE_RI] = array();
        $res[BEE_RI][$node_name] = $sql;
        return $res;
    }


    
    
    function bee_segmentation_post($nectoroid,$structure,$connection, $user_id){
        $res = array(null,array(),$structure);
        //go through the entire nectorid processing
        //node by node on the root
        $whole_honey = array();
        foreach ($nectoroid as $root_node_name => $root_node) {
            if(tools_startsWith($root_node_name,"_")){
                continue;
            }
            $comb_name  = Inflect::singularize($root_node_name);
            $sects = $structure[$comb_name];
            $bspp_res = bee_segmentation_post_process($root_node_name, $comb_name, $root_node, $structure, $connection, $user_id, $whole_honey);
            //tools_dumpx("bspp_res: ",__FILE__,__LINE__,$bspp_res);
            $whole_honey[$root_node_name] = $bspp_res[BEE_RI][$root_node_name];
            $res[BEE_EI] = array_merge($res[BEE_EI],$bspp_res[BEE_EI]);
        }
        $res[BEE_RI] = $whole_honey;
        return $res;
    }

    function bee_segmentation_post_process($node_name,$comb_name, $node, $structure, $connection, $user_id, $whole_honey){
        $res = array(null,array());
        if($comb_name == $node_name){
            $bspps_res = bee_segmentation_post_process_sqllize($node_name, $comb_name, $node, $structure, $user_id, $whole_honey);
            $res[BEE_RI] = $bspps_res[BEE_RI];
            $res[BEE_EI] = array_merge($res[BEE_EI],$bspps_res[BEE_EI]);
        }else{
            $res[BEE_RI] = array();
            $res[BEE_RI][$node_name] = array();
            //many insertions into the same comb
            for ($i=0; $i < count($node); $i++) { 
                $obj = $node[$i];
                $bspps_res = bee_segmentation_post_process_sqllize($node_name, $comb_name, $obj, $structure,$user_id, $whole_honey);
                array_push($res[BEE_RI][$node_name],$bspps_res[BEE_RI][$node_name]);
                $res[BEE_EI] = array_merge($res[BEE_EI],$bspps_res[BEE_EI]);
            }
        }
        return $res;
    }

    function bee_segmentation_post_process_sqllize($node_name, $comb_name, $node, $structure,$user_id, $whole_honey){
        $res = array(null,array());
        $guid = uniqid() . "-" . rand(1000,2768);
        $sql = "INSERT INTO " . $comb_name . " ( ";
        $sections_sql = "";
        $values_sql = "";
        foreach ($node as $key => $value) {
            $section_name = $key;
            //detect special flags
            if(tools_startsWith($section_name,"_")){
                $handled = false; //allows control to fall trough
                //files
                if(tools_startsWith($section_name,"_file_")){
                    $bsefv_res = bee_segmentation_evaluate_file_value($section_name,$value,$comb_name,$structure);
                    $section_name = $bsefv_res[BEE_RI]; 
                    $res[BEE_EI] = array_merge($res[BEE_EI],$bsefv_res[BEE_EI]); 
                    $handled = true;
                }
                //now
                if(tools_startsWith($section_name,"_now_")){
                    $section_name = str_replace("_now_","",$section_name);
                    $value = time();
                    $handled = true;
                }
                //encrypt
                if(tools_startsWith($section_name,"_encrypt_")){
                    $section_name = str_replace("_encrypt_","",$section_name);
                    $value = password_hash($value, PASSWORD_DEFAULT);
                    $handled = true;
                }
                //_fk_
                if(tools_startsWith($section_name,"_fk_")){
                
                //     //tools_dumpx("whole_honey: ",__FILE__,__LINE__,$whole_honey);
                //     tools_dumpx("res[BEE_RI] : ",__FILE__,__LINE__, $res[BEE_RI] );
                //     //remove the id
                //     //look for this value here
                //     // Note our use of ===.
                //     if (strpos($value, "@") === false) {
                //         //a normal object
                //         $value = intval($whole_honey[$value]);
                //     } else {
                //         //an array of indexes
                //         $prts = explode("@",$value);
                //         $value = intval($whole_honey[$prts[0]][intval($ind)]);
                //     }
                    $section_name = str_replace("_fk_","",$section_name);
                    $value = "_fk_" . $value . "_kf_";
                    $handled = true;
                }
                if($handled == false){
                    continue;
                }
            }

            $sections_sql .= "`".$section_name."`,";
            //nyd
            //apply formating and interpriting
            //of value according to sectures
            //also picking default values etc
            if(is_string($value) ){
                $prepared = addslashes(strval($value));
                $values_sql .= " '" . $prepared . "',";
            }else if(is_int($value) || is_float($value)){
                $values_sql .= " " . strval($value) . ",";
            }
        }
        $sections_sql   .= " `time_inserted`,";
        $values_sql     .= " " . time() . ",";
        $sections_sql   .= " `inserted_by`,";
        $values_sql     .=  " '" . addslashes($user_id) . "',";
        $sections_sql   .= " `time_last_modified`,";
        $values_sql    .=  " " . time() . ",";
        $sections_sql   .= " `last_modified_by`,";
        $values_sql     .=  " '" . $user_id . "',";
        $sections_sql   .= " `is_deleted`,";
        $values_sql     .=  " 0,";
        $sections_sql   .= " `guid`";
        $values_sql     .=  " '". addslashes($guid) ."'";
        $sql = $sql .  $sections_sql . " ) VALUES (" . $values_sql . " )";
        $res[BEE_RI] = array();
        $res[BEE_RI][$node_name] = $sql;
        return $res;
    }

    function bee_segmentation_evaluate_file_value($section_name,$value,$comb_name,$structure){
        $res = array(null,array());
        //check if file exists
        if (!file_exists($value)) {
            array_push($res[BEE_EI],"The file " . $value. " does not exist");
        }else{
            $section_name = str_replace("_file_","",$section_name);
            $final_destination = "bee/uploads/"; //the default uploads location
            //move this file to uploads
            //check if we have an upload rules for this comb
            if(array_key_exists("upload",$structure)){
                $upload_rules = $structure["upload"];
                if(array_key_exists($comb_name,$upload_rules)){
                    $comb_upload_rule = $upload_rules[$comb_name];
                    if(array_key_exists($section_name,$comb_upload_rule)){
                        $section_upload_rule = $comb_upload_rule[$section_name];
                        if(array_key_exists("_to",$section_upload_rule)){
                            $final_destination = $section_upload_rule["_to"];
                        }
                    }
                }
            }
            $target_file = $final_destination . basename($value);
            //move the file
            if (rename($value,$target_file)) {
                $value = $target_file;
            } else {
                array_push($res[BEE_EI],"Sorry, there was an error uploading your file.");
            }
        }
        $res[BEE_RI] = $value;
        return $res;
    }


    function bee_segmentation_delete($nectoroid,$structure,$connection, $user_id,$is_restricted=false){
        $res = array(null,array(),$structure);
        //go through the entire nectorid processing
        //node by node on the root
        $whole_honey = array();
        foreach ($nectoroid as $root_node_name => $root_node) {
            if(tools_startsWith($root_node_name,"_")){
                continue;
            }

            $comb_name  = Inflect::singularize($root_node_name);
            $sects = $structure[$comb_name];
            $sql = "DELETE FROM " . $comb_name . " ";
            if(BEE_SUDO_DELETE){
                $sql = "UPDATE " . $comb_name . " SET ";
                $sql .= " is_deleted = 1, ";
                $sql .= " last_modified_by = " . $user_id . ", ";
                $sql .= " last_modified_by = " . $user_id . ", ";
                $sql .= " time_last_modified = " . time() . " ";
            }
            $bsdw_res = bee_segmentation_delete_where($comb_name,$root_node_name,$root_node,$structure,$connection);
            $res[BEE_EI] = array_merge($res[BEE_EI], $bsdw_res[BEE_EI]);
            $condition = $bsdw_res[BEE_RI];
            //tools_dumpx("bsdw_res",__FILE__,__LINE__,$condition);
            $pids = $bsdw_res[3];
            $sql = $sql . ((strlen($condition)>0)? " WHERE " . $condition :  " ");
            $whole_honey[$root_node_name] = array(
                "sql" => $sql,
                "children_sqls" => array()
            );

            
            //get the deletes sqls for related child records
            //if restricted is true
            //and enforce_relationships = false
            //restricted just means that it cannot create missing columns or tables
            //restricted it doesnot affect relationships
            //also it affects data integrity, deleting, inserting and updating records
            if($is_restricted==true && BEE_ENFORCE_RELATIONSHIPS == false && count($pids) > 0){
                $bsdcs_res = bee_segmentation_delete_child_sqls(array(),$pids,$comb_name,$structure,$connection,$user_id);  
                $whole_honey[$root_node_name]["children_sqls"] = $bsdcs_res[BEE_RI];
                $res[BEE_EI] = array_merge($res[BEE_EI], $bsdcs_res[BEE_EI]);
                $res[2] = $bsdcs_res[2];
                //tools_dumpx("bsdcs_res: ",__FILE__,__LINE__,$bsdcs_res);  
            }
        }
        $res[BEE_RI] = $whole_honey;
        //nyd test results like on other deeply related tables
        //and multiple parent tables
        //tools_dumpx("whole_honey: ",__FILE__,__LINE__,$whole_honey);
        return $res;
    }

    function bee_segmentation_delete_child_sqls($csqls,$pids,$comb_name,$structure,$connection,$user_id){
        $res = array(null,array(),$structure);
        foreach ($structure as $section_name => $section_def) {
            if(tools_startsWith($section_name,"_")){
                continue;
            }
            $parent_key = $comb_name . "_id";
            $parent_key2 = "other_" .$comb_name . "_id";
            if(array_key_exists($parent_key,$section_def) || array_key_exists($parent_key2,$section_def)){
                $parent_k = "";
                if(array_key_exists($parent_key,$section_def)){
                    $parent_k = $parent_key;
                }elseif(array_key_exists($parent_key2,$section_def)){
                    $parent_k = $parent_key2;
                }
                $csql = "DELETE FROM " . $section_name ;
                if(BEE_SUDO_DELETE){
                    $csql = "UPDATE " . $section_name . " SET ";
                    $csql .= " is_deleted = 1, ";
                    $csql .= " last_modified_by = " . $user_id . ", ";
                    $csql .= " last_modified_by = " . $user_id . ", ";
                    $csql .= " time_last_modified = " . time() . " ";
                }
                $csql_w = "";
                foreach ($pids as $pid) {
                    $csql_w .= " " . $parent_k . " = " . $pid . " OR";
                }
                $csql_w = trim($csql_w,"OR");
                $csql = $csql . ((strlen($csql_w)>0)? " WHERE " . $csql_w :"");
                array_push($csqls,$csql);
                
                $sqlxy = "SELECT id FROM " . $section_name . " WHERE " . $csql_w;
                
                $hr_res = hive_run($sqlxy,$connection);
                $to_be_ids = array();
                foreach ($hr_res[BEE_RI]["data"] as $ind => $obj) {
                    array_push($to_be_ids,$obj["id"]);
                }
                if(count($to_be_ids)>0){
                    $bsdcs_res = bee_segmentation_delete_child_sqls($csqls,$to_be_ids,$section_name,$structure,$connection,$user_id);
                    $csqls = $bsdcs_res[BEE_RI];
                    $res[2] = $structure;
                    $res[BEE_EI] = array_merge($res[BEE_EI], $bsdcs_res[BEE_EI]);
                    //tools_dumpx("bsdcs_res",__FILE__,__LINE__,$bsdcs_res);
                }
            }
        }
        $res[BEE_RI] = $csqls;
        return $res;
    }


    function bee_segmentation_delete_where($comb_name,$root_node_name,$root_node,$structure,$connection){
        $res = array(null,array(),$structure,array());
        $condition = "";
        if($comb_name == $root_node_name){ //is a single entry
            //if it contains a where
            if(array_key_exists("_w", $root_node)){
                $where_array = $root_node["_w"];
                $srw_res = segmentation_run_w($where_array,$root_node_name,$root_node,$structure);
                //tools_dumpx("srw_res: ",__FILE__,__LINE__,$srw_res);
                $res[BEE_EI] = array_merge($res[BEE_EI],$srw_res[BEE_EI]);
                $condition .= $srw_res[BEE_RI];
                $structure = $srw_res[2];
                //make a selection here to get the ids of the records that will be deleted
                $res = bee_segmentation_get_ids_tobe_deleted($res,$comb_name,$root_node_name,$where_array,$structure,$connection);
            }else{
                //if one specified an array of ids, then we still pick one
                if(is_array($root_node) && count($root_node) > 0){
                    $condition .= " id = " . $root_node[0];
                    array_push($res[3],$root_node[0]);
                }elseif(is_numeric($root_node)){
                    $condition .= " id = " . $root_node;
                    array_push($res[3],$root_node);
                }else{
                    array_push($res[BEE_EI],"Wrong delete condition");
                }
            }
        }else{
            //its an array of things to be deleted
            //if it contains a where
            if(array_key_exists("_w", $root_node)){
                $where_array = $root_node["_w"];
                $srw_res = segmentation_run_w($where_array,$root_node_name,$root_node,$structure);
                //tools_dumpx("srw_res array section: ",__FILE__,__LINE__,$srw_res);
                $res[BEE_EI] = array_merge($res[BEE_EI],$srw_res[BEE_EI]);
                $condition .= $srw_res[BEE_RI];
                $structure = $srw_res[2];
                //make a selection here to get the ids of the records that will be deleted
                $res = bee_segmentation_get_ids_tobe_deleted($res,$comb_name,$root_node_name,$where_array,$structure,$connection);
            }else{
                //if one specified an array of ids, then we pick all
                if(is_array($root_node) && count($root_node) > 0){
                    for ($i=0; $i < count($root_node); $i++) { 
                        $condition .= " id = " . $root_node[$i];
                        array_push($res[3],$root_node[$i]);
                        if($i+1 < count($root_node)){ //if we still have another possible iteration
                            $condition .= " OR ";
                        }
                    }
                }elseif(is_numeric($root_node)){
                    $condition .= " id = " . $root_node;
                    array_push($res[3],$root_node);
                }else{
                    array_push($res[BEE_EI],"Wrong delete condition");
                }
            }
        }
        $res[BEE_RI] = $condition;
        return $res;
    }

    function bee_segmentation_get_tobe_deleted($comb_name,$where,$structure,$connection){
        $res = array(null,array(),$structure);
        $nectoroid = array();
        $nectoroid[$comb_name] = array(
            "_w" => $where
        );
        $sr_res = segmentation_run($nectoroid,$structure,$connection);
        //tools_dump("@1 segmentation_run res: ",__FILE__,__LINE__,$sr_res[BEE_RI]);
        $hasr_res = hive_after_segmentation_run($sr_res,$nectoroid,$structure,$connection);
        $res[BEE_RI] = $hasr_res[BEE_RI];
        $res[BEE_EI] = array_merge($res[BEE_EI],$hasr_res[BEE_EI]);
        $res[2] = $hasr_res[2];
        return $res; 
    }
    
    function bee_segmentation_get_ids_tobe_deleted($res,$comb_name,$root_node_name,$where_array,$structure,$connection){
        $bsgtd = bee_segmentation_get_tobe_deleted($root_node_name,$where_array,$structure,$connection);
        //tools_dumpx("bsgtd",__FILE__,__LINE__,$bsgtd);
        $res[BEE_EI] = array_merge($res[BEE_EI],$bsgtd[BEE_EI]);
        $res[2] = $bsgtd[2]; //structure
        $tobe = $bsgtd[BEE_RI][$root_node_name];;
        //tools_dumpx("tobe",__FILE__,__LINE__,$tobe[$root_node_name]);
        if($comb_name == $root_node_name){
            //a single object
            array_push($res[3],$tobe["id"]);
        }else{
            //an array of objects
            foreach ($tobe as $tobe_ind => $tobe_obj) {
                array_push($res[3],$tobe_obj["id"]);
            }
        }
        //tools_dumpx("res[3]",__FILE__,__LINE__,$res[3]);
        return $res;
    }

    
?>
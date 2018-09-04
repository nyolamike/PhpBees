<?php

    //the path to the value really means that there is an object that must hold this value
    //
    function  packaging_run($raw_honeys,$nectoroid,$structure,$connection){
        //tools_dump("testing honey",__FILE__,__LINE__,$raw_honeys);
        $res = array(array(),array(),$structure);
        $honey = array();
        foreach ($raw_honeys as $raw_honey_index => $raw_honey_group) {
            //the raw_honey_group contains
            //raw_honey, paths_to_clean, children
            $raw_honey = $raw_honey_group["raw_honey"];
            //tools_dump("raw_honey_index ",__FILE__,__LINE__,$raw_honey_index );

            //edge case of no results returned as in empty results
            if(count($raw_honey) == 0){
                //every index indicates a root node in the nectoroid
                $ti = 0; //target index
                foreach ($nectoroid as $rti => $rtv) {//root node index, root node value
                    if(tools_startsWith($rti, "_")){
                        continue;
                    }
                    if($ti == $raw_honey_index){
                        $cn  = Inflect::singularize($rti);
                        if($cn == $rti){
                            //an object was supposed to be here so we get null
                            $honey[$rti] = null;
                        }else{
                            //an empty array of results
                            $honey[$rti] = array();
                        }
                        //we dont need to mind bout children at this point
                        break; //we got what we come for here
                    }
                    $ti = $ti + 1; //continue the search
                }
                continue;
            }
            foreach ($raw_honey as $row_index => $row_value) {
                $next_index = true;
                //tools_dump("row value",__FILE__,__LINE__,$row_value);
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
                                    $honey_ref[$path_part] = array();
                                    $honey_ref = &$honey_ref[$path_part];
                                }else{
                                    //create an array then create and object array inside this one
                                    $honey_ref[$path_part] = array(array());
                                    $honey_ref = &$honey_ref[$path_part][0];
                                    $next_index = false;
                                }
                            }else{
                                //the key exists
                                //determine if its a collection or an object
                                $singular  = Inflect::singularize($path_part);
                                if($singular == $path_part){//then path_part was singular
                                    //get a refrence to this object
                                    $honey_ref = &$honey_ref[$path_part];
                                }else{
                                    if($next_index){
                                        //echo "inserting a new object <br/>";
                                        //insert a new object and get a reference
                                        array_push($honey_ref[$path_part],array());
                                        $honey_ref = &$honey_ref[$path_part][count($honey_ref[$path_part])-1];
                                        $next_index = false;
                                    }else{
                                        //get the reference to the last element of the array
                                        $honey_ref = &$honey_ref[$path_part][count($honey_ref[$path_part])-1];
                                    }
                                }
                            }
                        }                           
                    }
                }
            } 
            //tools_dump("@4 production_run current honey",__FILE__,__LINE__,$honey);

            //process the children of this path_route
            //get the child queries at every node of the query
            //using the honey as parent refreneces for where clauses
            $child_paths = $raw_honey_group["children"];
            //tools_dump("@4.1 child_paths: ",__FILE__,__LINE__,$child_paths);
            //tools_dump("@4.2 nectoroid: ",__FILE__,__LINE__,$nectoroid);
            //process the children of this path_route
            //get the child queries at every node of the query
            //using the honey as parent refreneces for where clauses
            $child_paths = $raw_honey_group["children"];
            //cbh
            if(count($child_paths)>0){
                $prc_res = packaging_run_children($child_paths,$honey,$nectoroid,$structure,$connection);
                $honey = $prc_res[BEE_RI];
                $res[BEE_RI] = array_merge($res[BEE_RI],$prc_res[BEE_RI]);
                $res[2] = $prc_res[2];
                //tools_dumpx("#children honey: ",__FILE__,__LINE__,$prc_res);
            }else{

            } 
        }    
        //tools_dumpx("kafulaka honey",__FILE__,__LINE__,$honey);
        $res[BEE_RI] = $honey;
        return $res;
    }
    
    function packaging_run_children($chilren_paths,$current_honey,$nectoroid,$structure,$connection){       
        $res = array($current_honey,array(),$structure);  
        for($c=0; $c < count($chilren_paths); $c++) {
            $chilren_path = $chilren_paths[$c];
            //tools_dump("chilren_path",__FILE__,__LINE__,$chilren_path);
            $source_of_truth = $current_honey;
            $truth_identity = array();
            $query_source = $nectoroid;
            $former_ref_kind = "none";
            //desconstruct the path to the children
            $children_path_parts = explode(BEE_SEP,$chilren_path);
            for ($i=0; $i < count($children_path_parts); $i++) {
                $children_path_part = $children_path_parts[$i];

                //detect if its the last part which indicates evaluation
                if($i+1 == count($children_path_parts)){
                    $child_comb_name  = Inflect::singularize($children_path_part);
                    //the source of truth has to contibute parent ids
                    $id_sql = array();
                    if($former_ref_kind == "array"){
                        //tools_dump("source_of_truth: ",__FILE__,__LINE__,$source_of_truth);
                        $parent_comb_name  = Inflect::singularize($children_path_parts[$i-1]);
                        foreach ($source_of_truth as $obj) {
                            if(count($id_sql)==0){
                                array_push($id_sql,array($parent_comb_name . "_id","=", $obj["id"]));
                            }else{
                                //get the former entry
                                $entry = $id_sql[0];
                                $id_sql[0] = array($entry,"OR",array($parent_comb_name . "_id","=", $obj["id"]));
                            }
                        }
                    }elseif($former_ref_kind == "object"){
                        $obj = $source_of_truth;
                        if(count($id_sql)==0){
                            $id_sql[0] = array($parent_comb_name . "_id","=",$obj["id"]);
                        }else{
                            //get the former entry
                            $entry = $id_sql[0];
                            $id_sql[0] = array($entry,"OR",array($parent_comb_name . "_id","=",$obj["id"]));
                        }
                    }
                    
                    //get honey of these children
                    $child_node_name = $children_path_part;
                    $child_query = $query_source[$children_path_part];
                    $config = array(
                        "path" => "",
                        "node_name" => $child_node_name,
                        "parents_w" => $id_sql,
                        "hive_structure" => $structure,
                        "children" => array(),
                        "is_child_segmentation_run" => true
                    );
                    //tools_dump("@5 packaging_run_children child query and config: ",__FILE__,__LINE__,array($child_query,$config));
                    $srp_res = segmentation_run_process($child_query,$config,$connection);
                    $temp_res = $srp_res[BEE_RI];
                    $srp_res[BEE_RI] = array();
                    $srp_res[BEE_RI][$child_node_name] = $temp_res;
                    //tools_dump("@6 segmentation_run_process res: ",__FILE__,__LINE__,$srp_res[BEE_RI]);
                    $tempx = array();
                    $tempx[$children_path_part] = $child_query;
                    $child_query = $tempx;
                    //tools_dump("@6.1 prepared child query: ",__FILE__,__LINE__,$child_query);
                    $hasr_res = hive_after_segmentation_run($srp_res,$child_query,$structure,$connection);
                    $res[2] = $hasr_res[2];
                    // $res[BEE_EI] = array_merge($res[BEE_EI],$hasr_res[BEE_EI]);
                    // $res[2] = $hasr_res[2];
                   // tools_dumpx("child res ",__FILE__,__LINE__,$hasr_res[BEE_RI]);

                    //nyd
                    //inject that honey into current honey structure
                    //retraverse
                    //tools_dump("#tag current_honey ",__FILE__,__LINE__,$current_honey);
                    $i_res = packaging_inject($current_honey,$children_path_parts,$hasr_res[BEE_RI],$children_path_part);
                    //tools_dump("#i_res ",__FILE__,__LINE__,$i_res);
                    $current_honey = $i_res;
                    //tools_dump("injectables",__FILE__,__LINE__,array(
                    //    $children_path_parts,
                    //    $current_honey
                    //));
                    $res[BEE_RI] = $current_honey;
                    $current_honey = $current_honey;

                    unset($source_of_truth);
                    //nyd
                    //just in case some thing is missing here
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
                        $truth_identity = array();
                        foreach ($source_of_truth as $obj) {
                            $temp_ref = $obj[$children_path_part];
                            if(!in_array($temp_ref["id"],$truth_identity)){
                                array_push($temp_sot,$temp_ref);
                                array_push($truth_identity, $temp_ref["id"]);
                            }
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
                        $truth_identity = array();
                        $temp_sot = array();
                        //var_dump($source_of_truth);
                        $temp_ref = $source_of_truth[$children_path_part];
                        foreach ($temp_ref as $obj) {
                            if(!in_array($obj["id"],$truth_identity)){
                                array_push($temp_sot,$obj);
                                array_push($truth_identity, $obj["id"]);
                            }
                        }
                        $source_of_truth = $temp_sot;
                        $former_ref_kind = "array";
                        $query_source = $query_source[$children_path_part];
                        //tools_dump("source_of_truth none: ",__FILE__,__LINE__,$source_of_truth);
                    }elseif($this_is_an_array && $former_ref_kind == "object"){
                        //the sot is an object which has an array that
                        //has objects that are to be the new source of truth
                        $truth_identity = array();
                        $temp_sot = array();
                        $temp_ref = $source_of_truth[$children_path_part];
                        foreach ($temp_ref as $obj) {
                            if(!in_array($obj["id"],$truth_identity)){
                                array_push($temp_sot,$obj);
                                array_push($truth_identity, $obj["id"]);
                            }
                        }
                        $source_of_truth = $temp_sot;
                        $former_ref_kind = "array";
                        $query_source = $query_source[$children_path_part];
                    }elseif($this_is_an_array && $former_ref_kind == "array"){
                        //the sot is an array of objects where each  has an array that
                        //has to be the new source of truth
                        $temp_sot = array();
                        $truth_identity = array();
                        foreach ($source_of_truth as $obj) {
                            $inner_array = $obj[$children_path_part];
                            foreach ($inner_array as $obj_sot) {
                                if(!in_array($obj_sot["id"],$truth_identity)){
                                    array_push($temp_sot,$obj_sot);
                                    array_push($truth_identity, $obj_sot["id"]);
                                }
                            }
                        }
                        $source_of_truth = $temp_sot;
                        $former_ref_kind = "array";
                        $query_source = $query_source[$children_path_part];
                    }
                }

            }
        }
        return $res;
    }

    function packaging_inject($objT,$path_parts,$value,$parent_node_name){
        if(count($path_parts)==1){
            $ipp = $path_parts[0];
            //reached value
            //value has a node with an array of parents
            //so we can now only get those values which
            //childern of this object
            $children = $value[$ipp];
            $my_kids = array();
            $parent_node_name_singular  = Inflect::singularize($parent_node_name);
            for ($c=0; $c < count($children) ; $c++) { 
                $child = $children[$c];
                //echo $parent_node_name . "<br/>";
                $fk = $parent_node_name_singular."_id";
                if($objT["id"] == $child[$fk]){
                    array_push($my_kids,$child);
                }
            }
            $objT[$ipp] = $my_kids;

            return $objT;
        }else{
            //get the part to process
            $ipp = $path_parts[0];
            $remains = array_slice($path_parts,1);
            $sipp  = Inflect::singularize($ipp);
            if($sipp == $ipp){
                $node = $objT[$ipp];
                $vo = packaging_inject($node,$remains,$value,$ipp);
                //tools_dump("#vo ",__FILE__,__LINE__,$vo);
                $objT[$ipp] = $vo;
                return $objT;
            }else{
                $node = $objT[$ipp];
                for ($ix=0; $ix < count($node); $ix++) { 
                    $t = $node[$ix];
                    $vi = packaging_inject($t,$remains,$value,$ipp);
                    //tools_dump("#vi ",__FILE__,__LINE__,$vi);
                    $node[$ix] = $vi;
                }
                $objT[$ipp] = $node;
                return $objT;
            }
        }
    }


    function packaging_post($raw_honeys,$structure,$connection){
        //tools_dumpx("testing honey",__FILE__,__LINE__,$raw_honeys);
        $res = array(array(),array(),$structure);
        $honey = array();
        foreach ($raw_honeys as $raw_honey_index => $raw_honey_group){
            $honey[$raw_honey_index] = array();
            $singular = Inflect::singularize($raw_honey_index);  
            if($singular == $raw_honey_index){
                //we have an object here
                $honey[$raw_honey_index] = intval($raw_honey_group["id"]);
            }else{
                //we have an array
                foreach ($raw_honey_group as $obj) {
                    array_push($honey[$raw_honey_index],intval($obj["id"]));
                }
            } 
        }
        //tools_dumpx("kafulaka honey",__FILE__,__LINE__,$honey);
        $res[BEE_RI] = $honey;
        return $res;
    }
?>
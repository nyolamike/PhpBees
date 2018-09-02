<?php

    //construct an sql query from these segements
    function sqllization_run($sql_segments){
        $sqls = array();
        foreach ($sql_segments as $root_node_name => $segmentation) {
            $comb_name = Inflect::singularize($root_node_name);
            $sections_sql = rtrim(trim($segmentation["temp_sections_sql"]), ',');
            $where_sql = trim($segmentation["temp_where_sql"]);
            $inner_joins_sql = $segmentation["temp_inner_join_sql"];
            $sql = "SELECT " . $sections_sql . " FROM " .  $comb_name . " " . $inner_joins_sql . " ";
            if(strlen($where_sql)>0){
                $sql = $sql . " WHERE " . $where_sql;
                //tools_dump("sql for " . $root_node_name,__FILE__,__LINE__,$sql);
            }
            //nyd
            //walk backwards to include code to generate paths to clean
            //and delete this if below
            if(!isset($segmentation["paths_to_clean"])){
                $segmentation["paths_to_clean"] = array();
            }
            array_push($sqls,array(
                "sql" => $sql,
                "paths_to_clean" => $segmentation["paths_to_clean"],
                "children" => $segmentation["temp_children"]
            ));
        }
        return  array($sqls,array());
    }
?>
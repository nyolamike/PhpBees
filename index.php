<?php

    //by including this in your script
    //it will create a garden for your application if one has not yet been created
    //reads in the state of the garden
    include("bee/run.php");

    //tools_dumpx("temp_postdata",__FILE__,__LINE__,"hi");
    //$token = new Emarref\Jwt\Token();
    //tools_dumpx("token",__FILE__,__LINE__,$token);
    
    //tools_dumpx("BEE",__FILE__,__LINE__,$BEE);
    //tools_dumpx("BEE",__FILE__,__LINE__,$BEE["BEE_GARDEN"]["hives"]);
  

    //tools_reply($BEE,array(),array($BEE_GARDEN_CONNECTION,$BEE_HIVE_CONNECTION));

    //tools_dumpx("BEE",__FILE__,__LINE__,$BEE["BEE_HIVE_CONNECTION"]);
    bee_handle_requests($BEE);

    //FEEBEE
    //BeeFee

    // $str = "'_fsk_store@1_ksf_','kafko wilson','_fsk_store@2_ksf_'";
    // $foo  = tools_get_in_between_strings("_fk_", "_kf_", $str);
    // tools_dumpx("foo",__FILE__,__LINE__,$foo);

    /*
    $temp_postdata = file_get_contents("php://input");
    var_dump($temp_postdata);

    //tools_dump("temp_postdata",__FILE__,__LINE__,$temp_postdata);
    var_dump($_FILES);

    var_dump($_POST); */

    /*
        //find the right price package for our print job
        print_both_sides =true,
        pages = 450,
        copies = 100,

        paper_type = 1.rotatrim -- list
        paper_size = 2.a4 --list
        colored = 3.true --list
        
        .......
        print_aspect_kind  *list
            print_aspect  *paper size  
                print_aspect_value  *a4
                    print_package_aspect  *xmas--a4
        print_package *xmas@400
                    print_package_aspect  *xmas--a4

        find print_package_aspects where 

        print_packages
            print_package_aspects
                print_aspect_value

        {
            _xtu_print_packages:{
                print_aspect_values: {
                    print_package_aspects: {
                        print_package: {}
                    },
                    _w:[
                        [
                            [ ["id","=",1], "OR", ["id","=",2] ],
                            "OR",
                            ["id","=",3]
                        ]
                    ]
                },
                _at:"print_aspect_values.print_package_aspects",
            }
        }


        /////////////////////
        $prv = "none";
        $hny = $res[BEE_RI];
        
        for ($i=0; $i < count($xtu_path_parts); $i++) {
            $xtu_path_part = $xtu_path_parts[$i];
            $singular = Inflect::singularize($xtu_path_part); 

            if($i+1 == count($xtu_path_parts)){ //the last part 
                //check if xtu key is an object
                tools_dump("xtu nhy",__FILE__,__LINE__,$hny);
                
                if($singular == $xtu_path_part){
                    //this is an object
                    if($prv == "array"){
                        $temp = array();
                        $temp_keys = array();
                        foreach ($hny as $list_item) {
                            $obj = $list_item[$xtu_path_part];
                            if(!in_array($obj["id"],$temp_keys)){
                                array_push($temp_keys,$obj["id"]);
                                array_push($temp,$obj[$xtu_key]);
                            }
                        }
                        $hny = $temp;
                        $prv = "none";
                    }elseif($prv == "none" || $prv == "object"){
                        //this is an object
                        $temp = $hny[$xtu_path_part][$xtu_key];
                        $prv = "none";
                    }
                }else{
                    if($prv == "array"){
                        $temp = array();
                        $temp_keys = array();
                        foreach ($hny as $list_item) {
                            $obj = $list_item[$xtu_path_part];
                            if(!in_array($obj["id"],$temp_keys)){
                                array_push($temp_keys,$obj["id"]);
                                array_push($temp,$obj[$xtu_key]);
                            }
                        }
                        $hny = $temp;
                        $prv = "none";
                    }elseif($prv == "none" || $prv == "object"){
                        //this is an object
                        $temp = $hny[$xtu_path_part][$xtu_key];
                        $prv = "none";
                    }
                }
            }

                
            if($singular == $xtu_path_part){
                if($prv == "array"){
                    $temp = array();
                    $temp_keys = array();
                    foreach ($hny as $list_item) {
                        $obj = $list_item[$xtu_path_part];
                        if(!in_array($obj["id"],$temp_keys)){
                            array_push($temp_keys,$obj["id"]);
                            array_push($temp,$obj);
                        }
                    }
                    $hny = $temp;
                    $prv = "array";
                }elseif($prv == "none" || $prv == "object"){
                    //this is an object
                    $hny = $hny[$xtu_path_part];
                    $prv = "object";
                }
            }else{
                if($prv == "none" || $prv == "object" ){
                    //its an array or list of values
                    $temp = array();
                    $temp_keys = array();
                    $list = $hny[$xtu_path_part];
                    foreach ($list as $list_index => $list_item) {
                        if(!in_array($list_item["id"],$temp_keys)){
                            array_push($temp_keys,$list_item["id"]);
                            array_push($temp,$list_item);
                        }
                    }
                    $hny = $temp;
                    $prv = "array";
                }elseif($prv == "array"){
                    $temp = array();
                    $temp_keys = array();
                    foreach ($hny as $hny_index => $hny_item) {
                        $list = $hny_item[$xtu_path_part];
                        foreach ($list as $list_index => $list_item) {
                            if(!in_array($list_item["id"],$temp_keys)){
                                array_push($temp_keys,$list_item["id"]);
                                array_push($temp,$list_item);
                            }
                        }
                    }
                    $hny = $temp;
                    $prv = "array";
                }
            }
        }
        
     

    //delete design
    {
        "stockin":4,
	    "_comment":"Deletes stockin of id 4"
    }

    {
        "stockins":[5,6,7],
        "_comment2":"Deletes stockin of id 5 6 7"
    }
    
    {
        "stockins":{
            "_w":[
                [["store_id","=",45], "AND" , ["section_id","=",45]]
            ]	
        },
        "_comment2":"Deletes has a _w flag"
    }

    print_aspect: {
            print_aspect_kind:{}
        }

    By default, the limit() method starts from the first record in the table. You can use the offset()
    method to change the starting record. For example, to ignore the first record and return the next three
    records matching the condition, pass to the offset() method a value of 1.
    mysql-js> db.country.select(["Code", "Name"]).orderBy(["Name desc"]).limit(3).offset(1)
    +------+------------+

    //juicy joan idea
    //bakuwekyonya
    //how could i let you leave me, now its too late to, i need you back but you are gone
    */
    
?>
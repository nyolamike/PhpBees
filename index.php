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
    
?>
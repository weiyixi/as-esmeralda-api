<?php

$container['slim']->get("$prefix/:id/favorite", function($id) use ($container){
    //get all user favorite 
});

$container['slim']->post("$prefix/:id/favorite", function($id) use ($container){
    //create user favorite 
});

$container['slim']->delete("$prefix/:id/favorite/:fid", function($id, $fid) use ($container){
    //delete user favorite 
});

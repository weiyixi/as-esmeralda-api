<?

$container['slim']->get("$prefix/:id/address", function($id) use ($container){
    //get all user addresses
});

$container['slim']->get("$prefix/:id/address/:aid", function($id) use ($container){
    //get user address
});

$container['slim']->post("$prefix/:id/address", function($id) use ($container){
    //create user address
});

$container['slim']->post("$prefix/:id/address/:aid", function($id, $aid) use ($container){
    //update user address
});

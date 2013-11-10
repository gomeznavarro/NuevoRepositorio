<?php

require_once( "common.inc.php" );
require_once( "config.php" );
require_once( "Member.class.php" );
require_once( "Amigo.class.php" );


$members= Member::getMembersNormal(  );


foreach ($members as $member){
$amigos=Amigo::getAmigos($member['id'] );
$member['amigos']=$amigos;
$completo[]=$member;
}

echo json_encode($completo);

/*
$arr = array (
array('id'=>1,'firstName'=>"Silvia",'amigos'=>array(array('id'=>1,'firstName'=>"Alicia", 'idmember'=>1), array('id'=>2,'firstName'=>"Elena", 'idmember'=>1))),
array('id'=>2,'firstName'=>"Spiros",'amigos'=>array(array('id'=>3,'firstName'=>"Michael", 'idmember'=>2), array('id'=>4,'firstName'=>"Willliam", 'idmember'=>2))),
);  
echo json_encode($arr); 
*/

?>
<?php

require_once( "common.inc.php" );
require_once( "config.php" );
require_once( "Member.class.php" );
require_once( "Amigo.class.php" );


$start = isset( $_GET["start"] ) ? (int)$_GET["start"] : 0;
$order = isset( $_GET["order"] ) ? preg_replace( "/[^ a-zA-Z]/", "", $_GET["order"] ) : "username";
//list( $members, $totalRows ) = Member::getMembers( $start, PAGE_SIZE, $order );
$members= Member::getMembers( $start, PAGE_SIZE, $order );


echo "<br/>";foreach ($members as $member){
$amigos=Amigo::getAmigos($member['id'] );
$member['amigos']=$amigos;
$completo[]=$member;
}
echo "<br/>";
echo "COMPLETO";
echo "<br/>";
echo "<hr>";

echo json_encode($completo);


/*foreach ( $members as $member ) {
	//$idmember=$member->getValueEncoded( "id" );
	$idmember=$member['id'];
$member['amigos']=array();
  $rowCount++;
 
	
     /*  echo " - ".$member['id']. " - ";
	   echo " - ".$member['username']." - ";
       echo " - ". $member['firstName']." - ";
       echo " - ".$member['lastName']." - ";
	  echo " - ". $member['amigos']." - ";*/
	   echo "<br/>";
   
	/*$amigos=Amigo::getAmigos($idmember );
	
	foreach($amigos as $amigo){
		
		$member['amigos']=$amigo;
			 echo "<br/>";

		 echo "Imprimo los amigos";
		print_r($member['amigos']);
	
	echo "<hr>";
	
		
		}
	 echo "<br/>";
	  echo "Imprimo los member";
	print_r($member);

}
*/
 echo "<br/>";
echo "_______________________________________";
 echo "<br/>";
//print_r($members);
 echo "<br/>";
echo "__________________________________________________";
 echo "<br/>";
//print_r($members);
//echo json_encode($members); 
//print_r($members);
displayPageHeader( "View book club members" );

?>
    <h2>Displaying members <?php echo $start + 1 ?> - <?php echo min( $start +  PAGE_SIZE, $totalRows ) ?> of <?php echo $totalRows ?></h2>

    <table cellspacing="0" style="width: 30em; border: 1px solid #666;">
      <tr>
        <th><?php if ( $order != "username" ) { ?><a href="view_members.php?order=username"><?php } ?>Username<?php if ( $order != "username" ) { ?></a><?php } ?></th>
        <th><?php if ( $order != "firstName" ) { ?><a href="view_members.php?order=firstName"><?php } ?>First name<?php if ( $order != "firstName" ) { ?></a><?php } ?></th>
        <th><?php if ( $order != "lastName" ) { ?><a href="view_members.php?order=lastName"><?php } ?>Last name<?php if ( $order != "lastName" ) { ?></a><?php } ?></th>
      </tr>
<?php
$rowCount = 0;
foreach ( $members as $member ) {
	//$idmember=$member->getValueEncoded( "id" );
	$idmember=$member['id'];

	
	

  $rowCount++;
  /*
?>
      <tr<?php if ( $rowCount % 2 == 0 ) echo ' class="alt"' ?>>
        <td><a href="view_member.php?memberId=<?php echo $member->getValueEncoded( "id" ) ?>&amp;start=<?php echo $start ?>&amp;order=<?php echo $order ?>"><?php echo $member->getValueEncoded( "username" ) ?></a></td>
        <td><?php echo $member->getValueEncoded( "firstName" ) ?></td>
        <td><?php echo $member->getValueEncoded( "lastName" ) ?></td>
      </tr>
	  <?php 
	  */
	?> <tr<?php if ( $rowCount % 2 == 0 ) echo ' class="alt"' ?>>
        <td><a href="view_member.php?memberId=<?php echo $member['id'] ?>&amp;start=<?php echo $start ?>&amp;order=<?php echo $order ?>"><?php echo $member['username'] ?></a></td>
        <td><?php echo $member['firstName']  ?></td>
        <td><?php echo $member['lastName'] ?></td>
      </tr>  
	  <?php
	$amigos=Amigo::getAmigos($idmember );
	foreach($amigos as $amigo){
		
		/* ?>
	
	  <tr>
	  <td>Array de amigos: <?php echo $amigo->getValueEncoded( "id" ) ?></td>
 		<td>Array de amigos: <?php echo $amigo->getValueEncoded( "firstName" ) ?></td>	  
 		<td>Array de amigos: <?php echo $amigo->getValueEncoded( "idmember" ) ?></td>	  
		</tr>
		<?php
		
		*/
		?> <tr>
	  <td>Array de amigos: <?php echo $amigo['id'] ?></td>
 		<td>Array de amigos: <?php echo $amigo['firstName'] ?></td>	  
 		<td>Array de amigos: <?php echo $amigo['idmember'] ?></td>	  
		</tr>
		<?php
		
		}
	 

}
?>
    </table>

    <div style="width: 30em; margin-top: 20px; text-align: center;">
<?php if ( $start > 0 ) { ?>
      <a href="view_members.php?start=<?php echo max( $start - PAGE_SIZE, 0 ) ?>&amp;order=<?php echo $order ?>">Previous page</a>
<?php } ?>
&nbsp;
<?php if ( $start + PAGE_SIZE < $totalRows ) { ?>
      <a href="view_members.php?start=<?php echo min( $start + PAGE_SIZE, $totalRows ) ?>&amp;order=<?php echo $order ?>">Next page</a>
<?php } ?>
    </div>

<?php
displayPageFooter();




//echo json_encode($members);
//echo json_encode($amigos);

//print_r($members);
/*echo "<br/>";
echo "Desde aqui, member";
echo "<br/>";
$idmember=6;
foreach ($members as $member){
$amigos=Amigo::getAmigos($member['id'] );
$member['amigos']=$amigos;

print_r($member);
echo "<br/>";

/*for ($i=0; $i<count($member); $i++){
	echo "SOy el id".$member['id'];
	$amigos=Amigo::getAmigos($i );
$members[$i]['amigos']=$amigos;
}*/
//}
/*echo "<br/>";
echo "Desde aqui";
echo "<br/>";
//print_r($members);
print_r( $members);
//echo json_encode($member);
echo "<br/>";
echo "Desde aqui, json";
echo "<br/>";

//echo json_encode($members);*/

/*
$arr = array ('a'=>1,'b'=>2,'c'=>3,'d'=>4,'e'=>5);  
echo json_encode($arr); 
*/
?>


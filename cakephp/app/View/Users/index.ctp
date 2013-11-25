<h1>LISTA DE USUARIOS</h1>
<?php foreach($users as $user): ?>
<h2><?php echo $user['User']['username'] ?></h2>
<hr />
<?php 
echo $this->html->link('Logout', array('action'=>'logout'));
endforeach;

 ?>
<br /> 
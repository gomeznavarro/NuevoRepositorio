<h1>LISTA DE USUARIOS</h1>
<?php foreach($users as $user): ?>
<h2><?php echo $user['User']['username'] ?></h2>
<hr />
<?php 
//si el id del usuario es el que está logado, mostramos el botón para hacer logout
if ($user['User']['id']==$this->Session->read('Auth.User.id'))
echo $this->html->link('Logout', array('action'=>'logout'));
endforeach;

 ?>
<br /> 
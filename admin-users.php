<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;


// Rota da tela que vai listar todos os usuários. Será tipo uma tabela.
$app->get("/admin/users", function(){

	User::verifyLogin(); // Verifica se a pessoa está logada no sistema e tenha acesso ao administrativo

	$users = User::listAll();

	$page = new PageAdmin();

	$page->setTpl("users", array(
		"users"=>$users
		));	

});


// Rota da tela que vai criar usuário, ou seja, ele vai gerar um posto para ser salvo.
$app->get('/admin/users/create', function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("users-create");	

});


// Rota para excluírmos um usuário do banco.
$app->get('/admin/users/:iduser/delete', function($iduser){

	User::verifyLogin();

	$user = new User;

	$user->get((int)$iduser);

	$user->delete();

	header("Location: /admin/users");
	exit;

});


// Rota para tela do usuário só que preenchida com o usuário passado como parâmetro para atualização.
$app->get('/admin/users/:iduser', function($iduser){

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array(
		"user"=>$user->getValues()
	));	

});


// Rota que vai recepeber o post gerado pela mesma rota com o método GET. A diferença entre as rotas é justamente o método.
// Essa será a rota que vai salvar os dados recebidos por POST no bancos de dados.
$app->post('/admin/users/create', function(){

	// Aqui será a parte onde vamos programar o insert do usuário no banco de dados.

	//  Verifica se o usuário está logada no sistema e tenha acesso ao administrativo
	User::verifyLogin();

	$user = new User();

	// Se o campo inadmin foi marcado então o valor é 1, senão 0
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->setData($_POST);

	$user->save();

	header("Location: /admin/users");
	exit;

});


// Rota para salvarmos a edição da mesma rota com o método GET.
$app->post('/admin/users/:iduser', function($iduser){

	User::verifyLogin();

	$user = new User();

	// Se o campo inadmin foi marcado então o valor é 1, senão 0
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->get((int)$iduser);

	$user->setData($_POST);

	$user->update();

	header("Location: /admin/users");
	exit;

});



 ?>
<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
    
    $page = new Page();

    $page->setTpl("index");

});

$app->get('/admin', function() {
    
    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("index");

});

$app->get('/admin/login', function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false

	]);

	$page->setTpl("login");

});

$app->post('/admin/login', function(){

	User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin");
	exit;

});

$app->get('/admin/logout', function(){

	User::logout();

	header("Location: /admin/login");
	exit();
});


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

$app->get("/admin/forgot", function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot");

});

$app->post("/admin/forgot", function(){

	$user = User::getForgot($_POST["email"]);

	header("Location: /admin/forgot/sent");
	exit;

});


$app->get("/admin/forgot/sent", function(){

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-sent");

});


$app->get("/admin/forgot/reset", function(){

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]

	));	

});

$app->post("/admin/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	// Cria o hash do password pra gravar no banco
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, ["cost" => 12]);

	$user->setPassword($password);

	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset-success");	


});


$app->get("/admin/categories", function(){

	User::verifyLogin();

	$categories = Category::listAll();

	$page = new PageAdmin();

	$page->setTpl("categories", [
		"categories" => $categories
	]);	

});


// Rota exibir a tela de criação de categorias
$app->get("/admin/categories/create", function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("categories-create");

});

// Rota inserção no banco da nova categorias
$app->post("/admin/categories/create", function(){

	User::verifyLogin();

	$category = new Category();

	$category->setData($_POST);

	$category->save();

	header("Location: /admin/categories");
	exit();

});


$app->get("/admin/categories/:idcategory/delete", function($idcategory){

	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$category->delete();

	header("Location: /admin/categories");
	exit();

});


$app->get("/admin/categories/:idcategory", function($idcategory){

	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-update", [
		"category" => $category->getValues()
	]);	

});


$app->post("/admin/categories/:idcategory", function($idcategory){

	$category = new Category();

	$category->get((int)$idcategory);

	$category->setData($_POST);

	$category->save();

	header("Location: /admin/categories");
	exit();


});

// Cria as páginas, ou seja, mada executar o link digitado no browser.
$app->run();

 ?>
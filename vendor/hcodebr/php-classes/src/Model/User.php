<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use Hcode\Model;

class User extends Model{

	const SESSION = "User";
	
	public static function login($Login, $password){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN"=>$Login
		));

		if (count($results) === 0){
			throw new \Exception("Usuário inexistente ou senha inválida", 1);
		}

		$data = $results[0];

		// Verifica se o hash do password passado é igual ao do banco. A função password_verify faz essa comparação.
		if(password_verify($password, $data["despassword"]) === true){
			
			$user = new User(); // Como criamos uma classe estática podemos instanciá-la dentro dela mesmo.

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;
			
			
		} else {
			throw new \Exception("Usuário inexistente ou senha inválida", 1);
			
		}

	}


	public static function verifyLogin($inadmin = true){

		if (
			!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
			||
			(bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin

		) {

			header("Location: /admin/login");
			exit();

		}


	}

	public static function logout(){

		$_SESSION[User::SESSION] = NULL;

	}

}


?>
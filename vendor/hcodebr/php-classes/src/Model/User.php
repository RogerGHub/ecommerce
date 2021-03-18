<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use Hcode\Model;
use \Hcode\Mailer;

class User extends Model{

	const SESSION = "User";

	// Essa é a chave que criamos para utilizar na criptografia. Tem que ser no mínimo com 16 caracteres ou mais sendo que devem ser fixo com 16 ou 24 ou 32
	const SECRET = "HcodePhp7_Secret"; 
	const SECRET_IV = "HcodePhp7_Secret_IV";

	public static function getFromSession()
	{

		$user = new User();

		if(isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0){

			$user->setData($_SESSION[User::SESSION]);
		}
 
		return $user;

	}


	public static function checkLogin($inadmin = true)
	{

		if (
			!isset($_SESSION[User::SESSION])
			||
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
		) {
			//Não está logado
			return false;

		} else {

			if ($inadmin === true && (bool)$_SESSION[User::SESSION]['inadmin'] === true) {

				return true;

			} else if ($inadmin === false) {

				return true;

			} else {

				return false;

			}

		}

	}	

	
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

		if (!User::checkLogin($inadmin)) {

			if ($inadmin) {
				header("Location: /admin/login");
			} else {
				header("Location: /login");
			}
			exit;

		}


	}

	public static function logout(){

		$_SESSION[User::SESSION] = NULL;

	}

	public static function listAll(){

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

	}

	// Método para inserção do usuário no banco de dados. Não pode ser estático.
	public function save(){

		// Intancio a classe Sql
		$sql = new Sql();

		// Chamo a procedure que vai faz a inserção na tabela tb_person, pega o id e inseri em tb_users e depois retorna o usuário inserido.
		$resultado = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin) ", array(

			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()

		));

		$this->setData($resultado[0]);

	}

	public function get($iduser){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(

			":iduser"=>$iduser

		));

		$this->setData($results[0]);

	}


	public function update(){

		// Intancio a classe Sql
		$sql = new Sql();

		// Chamo a procedure que vai faz a edição na tabela tb_person e na tabela tb_users e depois retorna o usuário editado.
		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin) ", array(

			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()

		));

		$this->setData($results[0]);

	}


	public function delete(){

		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", array(
			":iduser"=>$this->getiduser()
		));

	}


	public static function getForgot($email)
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * 
								 FROM tb_persons a
								 JOIN tb_users b USING(idperson)
								 WHERE a.desemail = :email",

								 array(
								 	":email"=>$email
								 ));

		if (count($results) === 0){
			throw new \Exception("Não foi possível recuperar a senha.");
			
		} else {

			$data = $results[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				"iduser"=>$data["iduser"],
				"desip"=>$_SERVER["REMOTE_ADDR"]

			));

			if (count($results2) === 0){
				throw new \Exception("Não foi possível recuperar a senha.");
			} else {

				$dataRecovery = $results2[0];

				// Encripita a mensagem em 128 bits na base 64 que vai para o e-mail do usuário para recuperar a senha
				//$code = base64_encode(openssl_encrypt($dataRecovery["idrecovery"], "aes-128-gcm", User::SECRET, OPENSSL_RAW_DATA, MCRYPT_MODE_ECB));
				
				$code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

				$code = base64_encode($code);


				// Monta o link que é o endereço que vai receber esse código que vai para o e-mail
				$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

				// Vamos agora enviar o link para o e-mail do usuário para ele criar uma nova senha
				$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha da Roger Store", "forgot", 
					array(
						"name"=>$data["desperson"],
						"link"=>$link
				));

				$mailer->send();

				return $data;

			}

		}

	}

	public static function validForgotDecrypt($code)
	{

		
		//$idrecovery = openssl_decrypt(MCRYPT_RIJNDAEL_128, User::SECRET, base64_decode($code), MCRYPT_MODE_ECB);

		$code = base64_decode($code);

		$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));

		$sql = new Sql();

		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			JOIN tb_users b USING(iduser)
			JOIN tb_persons c USING(idperson)
			WHERE
				a.idrecovery = :idrecovery
			AND a.dtrecovery IS NULL
			AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
		", array(

			"idrecovery"=>$idrecovery
		));

		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha");
			
		}
		else
		{
			return $results[0];
		}

	}

	public static function setForgotUsed($idrecovery)
	{

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(

			":idrecovery"=>$idrecovery
		));

	}

	public function setPassword($password){

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(

			":password"=>$password,
			":iduser"=>$this->getiduser()
		));

	}

}


?>
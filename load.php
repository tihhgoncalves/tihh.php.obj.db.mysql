<?

/**
 * Objeto de PHP que administra conexões de um site com o banco de dados de MySQL.
 *
 * versão 1.0
 *
 */
class tihh_db_mysql {

  const version = '1.0';

  public $errorNumber;
  public $errorMsg;
  public $sql;

  private $conn;

  private function _setErrorMsg()
  {
    $this->errorMsg = mysqli_error($this->conn);
    $this->errorNumber = mysqli_errno($this->conn);
  }

  private function _connect($host, $user, $pass, $database){


    //Verifica se extensão está habilitada no PHP
    if(!function_exists('mysqli_connect'))
      throw new Exception('[tihh_db_mysql] A extensão mysqli de conexão com o banco de dados não está habilitada no seu servidor.');

    $this->conn = mysqli_connect($host, $user, $pass);

    if($this->conn === false){
      $this->_setErrorMsg();
      throw new Exception('[tihh_db_mysql] Ocorreu um erro ao tentar conectar a seu banco de dados MySql.');
    }

    $selectionDb = mysqli_select_db($this->conn, $database);

    //força o resultado em UTF8
    mysqli_query($this->conn, "SET NAMES 'utf8'");

    if($selectionDb === false){
      $this->_setErrorMsg();
      throw new Exception('[tihh_db_mysql] Ocorreu um erro ao tentar selecionar um database ao seu banco de dados.');
    }

  }

  public function _read($sql){

    //Verifica se é um sql de SELECT
    if((strpos(strtolower($sql), 'select') === false) && (strpos(strtolower($sql), 'call') === false))
      throw new Exception('[tihh_db_mysql] Você só pode fazer uma consulta ao banco dados utilizando um sql de SELECT');

    return mysqli_query($this->conn, $sql);

  }

  public function __construct($host, $user, $pass, $database, $persistent = true){

    //Faz conexão
    $this->_connect($host, $user, $pass, $database);

  }

  public function Execute($sql){

    $this->sql = $sql;

    $resource = mysqli_query($this->conn, $sql);

    if($resource === false) {
      $this->_setErrorMsg();
      throw new Exception('[tihh_db_mysql] Não foi possível concluir a execução do comando enviado ao banco de dados:' . $sql);
    }

    return true;

  }

  public function GetLastIdInsert() {

    //Verifica se última ação foi um insert
    if(strpos(strtoupper($this->sql), 'INSERT') === false)
      throw new Exception('[tihh_db_mysql] Você só pode solicitar o ID inserido após ter executado um comando INSERT no banco de dados.');

    return mysqli_insert_id($this->conn);

  }

  public function LoadArrays($sql) {

    $resource = $this->_read($sql);

    $value = array();

    while($rw = mysqli_fetch_array($resource)) {
      array_push($value, $rw);
    }

    return $value;

  }

  public function LoadObjects($sql){

    $resource = $this->_read($sql);

    $value = array();
    while($rw = mysqli_fetch_object($resource)) {
      array_push($value, $rw);
    }

    return $value;

  }

  public function Close() {

    mysqli_close($this->conn);

  }

  public function LoadSqlFile($sqlFile){

    if(!file_exists($sqlFile)){
      throw new Exception('Arquivo SQL não foi encontrado.');
    }

    //Carrega arquivo...
    $arquivo = Array();
    $arquivo = file($sqlFile);
    $prepara = '';  // Cria a Variavel $prepara

    foreach($arquivo as $v){
      $prepara .= $v;
    }

    $sql = explode(';', $prepara);

    //executa comandos SQL...
    foreach($sql as $v) {

      $v = utf8_decode($v);
      $v = trim($v);

      if(!empty($v))
        $this->Execute($v);
    }
  }
}
?>
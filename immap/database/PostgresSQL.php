<?php namespace Immap\Database;

class PostgresSQL {
  public $db_conn;
  public $conn_string;
  public $reserved_word = array("gps", 
							    "meta/instanceID", 
								"uuid", "_attachments", 
								"formhub/uuid", 
								"meta/deprecatedID"
							    );

  public function __construct() {
  }

  function create_table_if_not_exits($table_name = null, $fileds = null) {
    $pgsql = <<<EOD
          CREATE TABLE  IF NOT EXISTS {$table_name} 
          ( 
            {$fileds}
          )
          WITH (
            OIDS=FALSE
          );
EOD;
    $result = pg_query($this->db_conn, $pgsql);
    if (!$result) {
      echo pg_last_error();
      exit;
    }
  }

  function insert_into_table($table_name, $colums_str, $value_str, $params_arr) {
    $pgsql = "INSERT INTO public.{$table_name} ($colums_str) VALUES($value_str);";
    $result = pg_query_params($this->db_conn, $pgsql, $params_arr);
  }

  function save($json) {
    $json = json_decode($json, true);
    $is_create_table = false;
    $schema_table = "";
    $tmp_schema_table = "";
    $table_name = "";
    foreach ($json as $data) {
      $colums_str = "";
      $value_str = "";
      $param_i = 0;
      $param_arr = array();
      foreach ($data as $key => $value) {
        $data_type = "character varying(255)";
        $extra = "";
        if ($key === "_uuid") {
          $extra = "NOT NULL";
        }
        if ($key === "_id") {
          $data_type = "integer";
        }
        if ($key === "_xform_id_string") {
          $table_name = $value;
        }
        if (!in_array($key, $this->reserved_word)) {
          if ($is_create_table === false) {
            if (mb_strlen($tmp_schema_table) === 0) {
              $tmp_schema_table .= "{$key} {$data_type} {$extra}";
            }
            else {
              $tmp_schema_table .= ",{$key} {$data_type} {$extra}";
            }
          }
          if (mb_strlen($colums_str) === 0) {
            $colums_str .= $key;
            $value_str .= "$" . ++$param_i;
          }
          else {
            $colums_str .= ",$key";
            $value_str .= ",$" . ++$param_i;
          }

          if ($key === "_geolocation") {
            $value = "$value[0],$value[1]";
          }
          array_push($param_arr, $value);
        }

        if ($key === 'formhub/uuid') {
          $schema_table = $tmp_schema_table;
          break;
        }
      }
      if ($is_create_table === false) {
        $this->create_table_if_not_exits($table_name, $schema_table);
        $is_create_table = true;
      }
      $this->insert_into_table($table_name, $colums_str, $value_str, $param_arr);
    }
  }

  function underscore_case($str) {
    $str = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    return $str;
  }

  function is_table_exists($tablename, $schemaname = 'public') {
    $pgsql = <<<EOD
    SELECT * FROM pg_catalog.pg_tables
    WHERE schemaname = $1
    AND tablename = $2;
EOD;
    $result = pg_query_params($this->db_conn, $pgsql, array($schemaname,
        $tablename));
    $arr = pg_fetch_all($result);
    return empty($arr);
  }

  function db_connect($x_dbname = "postgres") {
    $server = "";
    $port = "";
    $dbname = $x_dbname;
    $user = "";
    $password = "";
    $conn_string = 'host=' . $server .
            ' port=' . $port .
            ' dbname=' . $dbname .
            ' user=' . $user .
            ' password=' . $password;
    $this->db_conn = pg_connect($conn_string) or die(pg_last_error());
    return $this->db_conn;
  }

  function db_close($db_conn = null) {
    if ($db_conn === null) {
      $db_conn = $this->db_conn;
    }
    pg_close($db_conn);
  }

}

?>

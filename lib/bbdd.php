<?php
// Conexion a MYSQL
$db = @mysql_pconnect("localhost", "xxxxxxxx", "xxxxxxxx");
if(!$db) {
   $json = array('status' => 11);
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}

// Seleccion de la tabla
if(!@mysql_select_db("alergant")) {
   $json = array('status' => 12);
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}
?>

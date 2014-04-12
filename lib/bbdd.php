<?php
// Conexion a MYSQL
$db = new mysqli("localhost", "xxxxxxxx", "xxxxxxxx");
if(mysqli_connect_errno()) {
   $json = array('status' => 11);
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}

// Seleccion de la tabla
if(!$db->select_db("alergant")) {
   $json = array('status' => 12);
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}
?>

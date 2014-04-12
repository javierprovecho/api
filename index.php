<?php

/////////////////////
// COMIENZA LA API //
/////////////////////

// Controlo que me llega la peticion correctamente y no genere warnings.
if(isset($_GET['app'])) {
   $app = $_GET['app'];
   } else {
   $app = "";
}

switch ($app) {
/* ****************************
 * FUNCIONES UTILES PARA LA API
 * 
 * authip(); <-- Funcion para controlar que las peticiones se hacen unicamente desde el propio servidor o IPs autorizadas
 * auth(); <---- Funcion para autenticarse contra la API. Si la autenticacion es correcta continua la ejecucion del codigo. 
 * 
 *************************** */

   // General
   case "error":
      error();
      break;

   // Login
   case "authcheck":
      authcheck(1); // Muestro la salida json
      break;
   case "writetoken":
      authip();
      writetoken();
      break;
   case "gettoken":
      authip();
      gettoken();
      break;

   // WEB: Administradores
   case "listarusuarios":
      authip();
      listarusuarios();
      break;
   case "datosusuario":
      authip();
      datosusuario();
      break;
   case "updateusuario":
      authip();
      updateusuario();
      break;

   case "listarestablecimientos":
      authip();
      listarestablecimientos();
      break;
   case "datosestablecimientos":
      datosestablecimientos();
      break;
   case "updateestablecimiento":
      authip();
      updateestablecimiento();
      break;

   case "estadosusuario":
      estadosusuario();
      break;
   case "alergias":
      alergias();
      break;

   // WEB: Usuarios
   case "misestablecimientos":
      authip();
      misestablecimientos();
      break;
   case "userid":
      userid();
      break;

   // Alergant
   case "registro":
      registro();
      break;
   case "preferencias":
      preferencias();
      break;
   case "coordenadas":
      coordenadas();
      break;
   case "buscar":
      buscar();
      break;
   case "recomendacion":
      recomendacion();
      break;
   case "detalles":
      detalles();
      break;
   case "votacion":
      votacion();
      break;

   // Default
   default:
      html_api();
      exit;
   }

$db->close(); // Esto cerrará la conexión a la bbdd después de cada petición a la API.
exit;


///////////////////////////////////////////
// Funciones, clases y metodos de la API //
///////////////////////////////////////////


// Funcion para mostrar pagina HTML si no hay ningun parametro a la Web de la API.
function html_api() {
   //Header("Location: http://www.alergant.es/");
   include("./html/api-index.php");
   exit;
}


// Funcion para calcular la distancia entre dos coordenadas
function distancia($lat1, $lon1, $lat2, $lon2, $unit) { 
   $theta = $lon1 - $lon2;
   $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
   $dist = acos($dist);
   $dist = rad2deg($dist);
   $miles = $dist * 60 * 1.1515;
   $unit = strtoupper($unit);
   if ($unit == "K") {
      return ($miles * 1.609344);
   } else {
      return $miles;
   }
}

// Funcion para coger coordenadas delimitadoras para hacer busqueda limitada en la BBDD
// http://www.michael-pratt.com/blog/7/Encontrar-Lugares-cercanos-con-MySQL-y-PHP/
function getBoundaries($lat, $lng, $distance = 1, $earthRadius = 6371) {
    $return = array();
   // Los angulos para cada dirección
   $cardinalCoords = array('north' => '0',
                           'south' => '180',
                           'east' => '90',
                           'west' => '270');
 
   $rLat = deg2rad($lat);
   $rLng = deg2rad($lng);
   $rAngDist = $distance/$earthRadius;
 
   foreach ($cardinalCoords as $name => $angle) {
      $rAngle = deg2rad($angle);
      $rLatB = asin(sin($rLat) * cos($rAngDist) + cos($rLat) * sin($rAngDist) * cos($rAngle));
      $rLonB = $rLng + atan2(sin($rAngle) * sin($rAngDist) * cos($rLat), cos($rAngDist) - sin($rLat) * sin($rLatB));
 
      $return[$name] = array('lat' => (float) rad2deg($rLatB),
                             'lng' => (float) rad2deg($rLonB));
   }
 
   return array('min_lat' => $return['south']['lat'],
                'max_lat' => $return['north']['lat'],
                'min_lng' => $return['west']['lng'],
                'max_lng' => $return['east']['lng']);
}


// Funcion para decodificar todas las alergias/intolerancias dado un ID de Alergia
function alergantdecode($idalergias) {
   $pow2 = array("8192", "4096", "2048", "1024", "512", "256", "128", "64", "32", "16", "8", "4", "2", "1");
   $alergant = array();

   $j=0;
   for($i=0; $i<count($pow2); $i++) {
      if($idalergias>=$pow2[$i]) {
	   $idalergias=$idalergias-$pow2[$i];
           $alergant[$j]=$pow2[$i];
	   $j++;
        }
   }
   return $alergant;

}


// Funcion para mostrar codigos de error.
function error() {
   // Si el navegador no es Alergant (Useragent), le mando a la pagina principal, sino genero error JSON.
   if($_SERVER['HTTP_USER_AGENT']!='Alergant') {
      Header("Location: /");
      exit;
   }

   if(!isset($_GET['id'])) {
      $json = array('status' => 510);
   } else {
      $json = array('status' => $_GET['id']);
   }
   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para controlar que las peticiones se hacen unicamente desde el propio servidor o IPs autorizadas
function authip() {
   // Posibles IPs de acceso a esta parte de la API
   $ips = Array("127.0.0.1", "87.98.228.243", "2001:41d0:2:eee5::300:1");

   $ipautorizada = false;
   for($i=0;$i<count($ips);$i++) {
      if($ips[$i]==$_SERVER["REMOTE_ADDR"]) {
         $ipautorizada = true;
         break;
      }
   }

   if(!$ipautorizada) {
      Header("Content-Type: application/json");
      $json = array('status' => 2, 'ip' => $_SERVER["REMOTE_ADDR"]);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Continua, las IPs son autorizadas para continuar
}


// Funcion de autenticacion de usuarios. Devuelve status = 1 si el usuario y el password son correctos
function authcheck($salida) {
   // username: Nombre de usuario
   // password: Password en MD5

   // salida: Si es 1, muestro la salida json, sino, no.
   require("./lib/bbdd.php");

   $login = false;
   if((!isset($_POST["username"]))||(!isset($_POST["password"]))) {
      // Error, no se han pasado por POST el usuario o el password
      Header("Content-Type: application/json");
      $json = array('status' => 1001);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Consulto a la BBDD
   if(!$stmt = $db->prepare("SELECT Usuario,Password FROM Usuarios")){
   	// algo fue mal
   }
   if(!$stmt->execute()){
      // Error en la consulta en la tabla de usuarios.
      Header("Content-Type: application/json");
      $json = array('status' => 101);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }
   $datos_bbdd = array();
   $stmt->bind_result($datos_bbdd['Usuario'], $datos_bbdd['Password']);

   // Tenemos resultados
   while($stmt->fetch()) {
      // Si el usuario y password coinciden, cambio variable y salgo del bucle.
      if(($_POST['username'] == $datos_bbdd['Usuario'])&&($_POST['password'] == $datos_bbdd['Password'])) {
         $login = true;
         break; // Salimos del bucle
      }
   }
   
   $stmt->close();
   
   if(!$login) {
      // Usuario y/o password incorrectos.
      $json = array('status' => 1002);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
   } else {
      // Usuario y/o password correctos.
      $json = array('status' => 1);
      if($salida) {
         Header("Content-Type: application/json");
         echo json_encode($json,JSON_UNESCAPED_UNICODE);
      }
   }
}

// Funcion para autenticarse contra la API. Si la autenticacion es correcta continua la ejecucion del codigo.
function auth() {
   // username: Nombre de usuario
   // password: Password en MD5
   require("./lib/bbdd.php");
   Header("Content-Type: application/json");

   $login = false;
   if((!isset($_POST["username"]))||(!isset($_POST["password"]))) {
      // Error, no se han pasado por POST el usuario o el password
      $json = array('status' => 1001);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Consulto a la BBDD
   $consulta = "SELECT Usuario,Password FROM Usuarios";
   $resultado = mysql_query($consulta, $db);
   if(!$resultado) {
      // Error en la consulta en la tabla de usuarios.
      $json = array('status' => 101);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Tenemos resultados
   while($datos_bbdd = mysql_fetch_array($resultado)) {
      // Si el usuario y password coinciden, cambio variable y salgo del bucle.
      if(($_POST['username'] == $datos_bbdd['Usuario'])&&($_POST['password'] == $datos_bbdd['Password'])) {
         $login = true;
         break; // Salimos del bucle
      }
   }

   if(!$login) {
      // Usuario y/o password incorrectos.
      $json = array('status' => 1002);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Autenticacion correcta, continuamos con el codigo
}


// Funcion para escribir el token de autenticacion en la BBDD
function writetoken() {
   // username: Nombre de usuario
   // token: Token de la sesion
   // ip: IP del usuario
   require("./lib/bbdd.php");
   $fecha = Date("d/m/Y H:i");

   // Actualizo la BBDD con el Token, la IP del usuario, asi como la dia y hora actual.
   $consulta = "UPDATE Usuarios SET Token='".$_POST["token"]."', IP='".$_POST["ip"]."', UltimaSesion='".$fecha."' WHERE Usuario='".$_POST["username"]."'";
   $resultado = mysql_query($consulta, $db);
   if((!$resultado)||(mysql_affected_rows()==0)) {
      // Si no se ha podido actualizar la BBDD con el Token, genero error.
      $json = array('status' => 101);
   } else {
      $json = array('status' => 1);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para coger el token de autenticacion en la BBDD
function gettoken() {
   // username: Nombre de usuario
   require("./lib/bbdd.php");

   // Consulto a la BBDD
   $consulta = "SELECT Token, Estado FROM Usuarios WHERE Usuario='".$_POST["username"]."'";
   $resultado = mysql_query($consulta, $db);
   if($resultado) {
      $datos_bbdd = mysql_fetch_array($resultado);
      $json = array('status' => 1, 'token' => $datos_bbdd['Token'], 'cuenta' => $datos_bbdd['Estado']);
   } else {
      $json = array('status' => 100);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para coger los establecimientos añadidos por un usuario
function misestablecimientos() {
   // username: Nombre de usuario
   // token: Token de la sesion
   require("./lib/bbdd.php");

   // Consulto a la BBDD
   $consulta = "SELECT ID, Nombre, Poblacion, Provincia FROM Establecimientos WHERE IDUsuario=(SELECT ID FROM Usuarios WHERE Usuario='".$_POST["username"]."')";
   $resultado = mysql_query($consulta, $db);
   if($resultado) {
      $json[0] = array('status' => 1, 'longitud' => mysql_num_rows($resultado)); 
      $i = 1;
      while($datos_bbdd = mysql_fetch_array($resultado)) {
         $json[$i] = array('ID' => $datos_bbdd['ID'], 
                           'Nombre' => $datos_bbdd['Nombre'], 
	                   'Poblacion' => $datos_bbdd['Poblacion'],
			   'Provincia' => $datos_bbdd['Provincia']
			  );
	 $i++;
      }

   } else {
      $json = array('status' => 100);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para listar los usuarios
function listarusuarios() {
   // username: Nombre de usuario
   // token: Token de la sesion del usuario
   require("./lib/bbdd.php");

   // Controlo que solo un usuario administrador puede acceder a la lista de usuarios
   $consulta = "SELECT Estado FROM Usuarios WHERE Usuario='".$_POST["username"]."' AND Token='".$_POST["token"]."'";
   $resultado = mysql_query($consulta, $db);
   if(!$resultado) {
      $json = array('status' => 100);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Consulto a la BBDD la lista de usuarios
   $consulta = "SELECT ID, Nombre, Apellidos, Usuario, Estado FROM Usuarios";
   $resultado = mysql_query($consulta, $db);
   if($resultado) {
      $json[0] = array('status' => 1, 'longitud' => mysql_num_rows($resultado)); 
      $i = 1;
      while($datos_bbdd = mysql_fetch_array($resultado)) {
         $json[$i] = array('ID' => $datos_bbdd['ID'], 
                           'Nombre' => $datos_bbdd['Nombre'], 
	                   'Apellidos' => $datos_bbdd['Apellidos'],
			   'Usuario' => $datos_bbdd['Usuario'],
			   'Estado' => $datos_bbdd['Estado']
			  );
	 $i++;
      }

   } else {
      $json = array('status' => 100);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para listar los establecimientos
function listarestablecimientos() {
   // username: Nombre de usuario
   // token: Token de la sesion del usuario
   require("./lib/bbdd.php");

   // Consulto a la BBDD la lista de establecimientos
   $consulta = "SELECT ID, Tipo, Lat, Lng, Nombre, Poblacion, Provincia, Estado FROM Establecimientos";
   $resultado = mysql_query($consulta, $db);
   if($resultado) {
      $json[0] = array('status' => 1, 'longitud' => mysql_num_rows($resultado)); 
      $i = 1;
      while($datos_bbdd = mysql_fetch_array($resultado)) {
         $json[$i] = array('ID' => $datos_bbdd['ID'], 
                           'Tipo' => $datos_bbdd['Tipo'], 
                           'Nombre' => $datos_bbdd['Nombre'], 
	                   'Poblacion' => $datos_bbdd['Poblacion'],
			   'Provincia' => $datos_bbdd['Provincia'],
			   'Estado' => $datos_bbdd['Estado'],
			   'Lat' => $datos_bbdd['Lat'],
			   'Lng' => $datos_bbdd['Lng']
			  );
	 $i++;
      }

   } else {
      $json = array('status' => 100);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para actualizar los datos del establecimiento
function updateestablecimiento() {
   //
   require("./lib/bbdd.php");

   $consulta = "UPDATE Establecimientos SET 
Tipo='".$_POST["tipo"]."',
Estado='".$_POST["estado"]."',
Nombre='".$_POST["nombre"]."',
Direccion='".$_POST["direccion"]."',
CP='".$_POST["cp"]."',
Poblacion='".$_POST["poblacion"]."',
Provincia='".$_POST["provincia"]."',
Pais='".$_POST["pais"]."',
Telefono='".$_POST["telefono"]."',
Lat='".$_POST["lat"]."',
Lng='".$_POST["lng"]."',
Web='".$_POST["web"]."',
WebReserva='".$_POST["webreserva"]."',
Carta='".$_POST["carta"]."',
Foto='".$_POST["foto"]."',
Descripcion='".$_POST["descripcion"]."',
AcuerdoAsociacion='".$_POST["acuerdoasociacion"]."',
Alergant='".$_POST["alergant"]."',
Propietario='".$_POST["propietario"]."',
IDAlergia='".$_POST["idalergia"]."'
WHERE ID='".$_POST["id"]."'";
   $resultado = mysql_query($consulta, $db);
   if(!$resultado) {
      // Si no se ha podido insertar el registro en la BBDD, genero error.
      $json = array('status' => 102);
   } else {
      $json = array('status' => 1);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para actualizar los datos del usuario
function updateusuario() {
   // username: Usuario
   // token: Token de sesion del usuario
   // id: ID del usuario que quiero cambiar
   // password: Nuevo password
   // nombre: Nombre del usuario
   // apellidos: Apellidos del usuario
   // correo: Correo del usuario
   // estado: Estado de la cuenta del usuario
   require("./lib/bbdd.php");

   if(isset($_POST["apellidos"])) {
      $apellidos = $_POST["apellidos"];
   } else {
      $apellidos = "";
   }

   if(isset($_POST["estado"])) {
      $estado = " Estado='".$_POST["estado"]."',";
   } else {
      $estado = "";
   }

   if(isset($_POST["password"])) {
      // El password si se actualiza
      $consulta = "UPDATE Usuarios SET Password='".$_POST["password"]."', Nombre='".$_POST["nombre"]."', Apellidos='".$apellidos."', Correo='".$_POST["correo"]."', ".$estado." Alergias='".$_POST["alergias"]."' WHERE ID='".$_POST["id"]."'";
   } else {
      // Password en blanco, no se actualiza.
      $consulta = "UPDATE Usuarios SET Nombre='".$_POST["nombre"]."', Apellidos='".$_POST["apellidos"]."', Correo='".$_POST["correo"]."', ".$estado." Alergias='".$_POST["alergias"]."' WHERE ID='".$_POST["id"]."'";
   }
   $resultado = mysql_query($consulta, $db);
   if(!$resultado) {
      // Si no se ha podido insertar el registro en la BBDD, genero error.
      $json = array('status' => 101);
   } else {
      $json = array('status' => 1);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para devolver los datos de un usuario
function datosusuario() {
   // username: Nombre de usuario
   // token: Token de la sesion del usuario
   // id: ID del usuario del que queremos los datos
   require("./lib/bbdd.php");

   // Controlo que solo un usuario administrador puede acceder a los datos del usuario o el propio usuario
   $consulta = "SELECT Estado FROM Usuarios WHERE Usuario='".$_POST["username"]."' AND Token='".$_POST["token"]."'";
   $resultado = mysql_query($consulta, $db);
   $datos1 = mysql_fetch_array($resultado);

   $consulta = "SELECT Usuario FROM Usuarios WHERE ID='".$_POST["id"]."'";
   $resultado = mysql_query($consulta, $db);
   $datos2 = mysql_fetch_array($resultado);

   if(($datos1['Estado']!=1)&&($datos2['Usuario']!=$_POST["username"])) {
      $json = array('status' => 100);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Consulto a la BBDD los datos del usuario
   $consulta = "SELECT ID, Nombre, Apellidos, Usuario, Correo, Estado, UltimaSesion, IP, Alergias FROM Usuarios WHERE ID='".$_POST["id"]."'";
   $resultado = mysql_query($consulta, $db);
   if($resultado) {
      $datos_bbdd = mysql_fetch_array($resultado);
      $json[0] = array('status' => 1);
      $json[1] = array('ID' => $datos_bbdd['ID'],
                           'Nombre' => $datos_bbdd['Nombre'],
                           'Apellidos' => $datos_bbdd['Apellidos'],
                           'Usuario' => $datos_bbdd['Usuario'],
                           'Estado' => $datos_bbdd['Estado'],
                           'Correo' => $datos_bbdd['Correo'],
                           'UltimaSesion' => $datos_bbdd['UltimaSesion'],
                           'IP' => $datos_bbdd['IP'],
                           'Alergias' => $datos_bbdd['Alergias']
                          );

   } else {
      $json = array('status' => 100);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}



// Funcion que devuelve el id de un usuario
function userid() {
   require("./lib/bbdd.php");

   // Controlo que solo un usuario administrador puede acceder a los datos del usuario o el propio usuario

   $consulta = "SELECT ID FROM Usuarios WHERE Usuario='".$_POST["username"]."'";
   $resultado = mysql_query($consulta, $db);
   if(!$resultado) {
      $json = array('status' => 101);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }
   // Consulto a la BBDD los datos del usuario
   $datos_bbdd = mysql_fetch_array($resultado);
   $json[0] = array('status' => 1);
   $json[1] = array('ID' => $datos_bbdd['ID']);
   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para devolver los estados de un usuario
function alergias() {
   require("./lib/bbdd.php");

   // Controlo que solo un usuario administrador puede acceder a los datos del usuario o el propio usuario

   $consulta = "SELECT ID, Descripcion FROM Alergias ORDER BY ID ASC";
   $resultado = mysql_query($consulta, $db);
   if(!$resultado) {
      $json = array('status' => 103);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Consulto a la BBDD los datos del usuario
   $json[0] = array('status' => 1, 'longitud' => 12);
   $i=1;
   while($datos_bbdd = mysql_fetch_array($resultado)) {
      $json[$i] = array('ID' => $datos_bbdd['ID'],
                        'Descripcion' => $datos_bbdd['Descripcion']
                        );
      $i++;
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion para devolver los estados de un usuario
function estadosusuario() {
   require("./lib/bbdd.php");

   // Controlo que solo un usuario administrador puede acceder a los datos del usuario o el propio usuario

   $consulta = "SELECT ID, Descripcion FROM UsuariosEstado ORDER BY ID ASC";
   $resultado = mysql_query($consulta, $db);
   if(!$resultado) {
      $json = array('status' => 100);
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Consulto a la BBDD los datos del usuario
   $json[0] = array('status' => 1, 'longitud' => 5);
   $i=1;
   while($datos_bbdd = mysql_fetch_array($resultado)) {
      $json[$i] = array('ID' => $datos_bbdd['ID'],
                        'Descripcion' => $datos_bbdd['Descripcion']
                        );
      $i++;
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion que registra a un usuario en la app
function registro() {
   // username: Usuario
   // password: Password del usuario en MD5
   // nombre: Nombre
   // apellidos: Apellidos (opcional)
   // correo: Correo electronico
   // alergant: ID de alergias/intolerancias
   // ip: IP del usuario. Opcional. Solo hay que pasarla si accedemos a esta funcion desde la web.
   require("./lib/bbdd.php");

   // Controlo variables recibidas por post
   if((!isset($_POST["username"]))||(!isset($_POST["password"]))||(!isset($_POST["nombre"]))||(!isset($_POST["correo"]))||(!isset($_POST["alergant"]))) {
      $json[0] = Array('status' => 1000);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Verifico que el usuario no esta en la BBDD
   $consulta = "SELECT Usuario FROM Usuarios WHERE Usuario='".$_POST["username"]."'";
   $resultado = mysql_query($consulta, $db);
   if(mysql_num_rows($resultado)!=0) {
      $json[0] = Array('status' => 201);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Verifico que el usuario ya no esta registrado con el mismo correo electronico.
   $consulta = "SELECT Correo FROM Usuarios WHERE Correo='".$_POST["correo"]."'";
   $resultado = mysql_query($consulta, $db);
   if(mysql_num_rows($resultado)!=0) {
      $json[0] = Array('status' => 202);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Usuario y correo inexistentes. Lo puedo añadir a la BBDD
   $fecha = Date("d/m/Y H:i");
   if(!isset($_POST["ip"])) {
      $ip = $_SERVER["REMOTE_ADDR"]; 
   } else {
      $ip = $_POST["ip"];
   }

   $alphanum = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdfghijklmnopqrstuvwxyz0123456789";
   $token = md5(substr(md5(str_shuffle($alphanum)), 0, 10));

   $consulta = "INSERT INTO Usuarios VALUES(null, '".$_POST["nombre"]."', '".$_POST["apellidos"]."', '".$_POST["correo"]."', '".$_POST["username"]."', '".$_POST["password"]."', '".$fecha."', '".$ip."', 5, '".$_POST["alergant"]."', '".$token."')";
   $resultado = mysql_query($consulta, $db);
   if((!$resultado)||(mysql_affected_rows()==0)) {
      // Si no se ha podido insertar el registro en la BBDD, genero error.
      $json = array('status' => 101);
   } else {
      $json = array('status' => 1);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;
}


// Funcion que devuelve la lista de establecimientos segun la ubicacion
function coordenadas() {
   // lat: Latitud
   // lng: Longitud
   // dst: Distancia
   require("./lib/bbdd.php");

   // Controlo variables recibidas por post
   if((!isset($_POST["lat"]))||(!isset($_POST["lng"]))||(!isset($_POST["dst"]))) {
      $json[0] = Array('status' => 1000);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   $zonadelimitada = getBoundaries($_POST["lat"], $_POST["lng"], $_POST["dst"]);

   $consulta = "SELECT ID, Nombre, (6371 * ACOS(
   SIN(RADIANS(lat)) * SIN(RADIANS(".$_POST["lat"]."))
   + COS(RADIANS(lng - ".$_POST["lng"].")) * COS(RADIANS(lat))
   * COS(RADIANS(".$_POST["lat"]."))
   )
   ) AS Distancia
   FROM Establecimientos
   WHERE (lat BETWEEN " . $zonadelimitada['min_lat']. " AND " . $zonadelimitada['max_lat'] . ")
   AND (lng BETWEEN " . $zonadelimitada['min_lng']. " AND " . $zonadelimitada['max_lng']. ")
   HAVING Distancia < ".$_POST[dst]." 
   ORDER BY Distancia ASC
   LIMIT 0,50";
   $resultado = mysql_query($consulta, $db);

   // Si no encontramos restaurantes con los datos pasados, error 100.
   if(mysql_num_rows($resultado)==0) {
      $json[0] = Array('status' => 100);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Tenemos resultados
   $i = 1;
   $json[0] = array('status' => 1, 'longitud' => mysql_num_rows($resultado));
   while($datos_bbdd = mysql_fetch_array($resultado)) {
      $json[$i] = array(
         'ID' => $datos_bbdd['ID'],
         'Nombre' => utf8_encode($datos_bbdd['Nombre']),
         'Distancia' => $datos_bbdd['Distancia']);
      $i++;
   }
   echo stripslashes(json_encode($json,JSON_UNESCAPED_UNICODE));
   exit;

}


// Funcion que devuelve la lista de establecimientos segun el parametro pasado
function buscar() {
   // busqueda: Cadena de busqueda a buscar en la BBDD 
   // tipo: Tipo de restaurante 
   require("./lib/bbdd.php");

   // Controlo variables recibidas por post
   if((!isset($_POST["busqueda"]))||(!isset($_POST["tipo"]))) {
      $json[0] = Array('status' => 1000);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   $consulta = "SELECT ID, Lat, Lng, Tipo, Direccion, Provincia, LOWER(Poblacion) AS Poblacion, LOWER(Nombre) AS Nombre FROM Establecimientos WHERE Poblacion LIKE '%".strtolower($_POST["busqueda"])."%' OR Nombre LIKE '%".strtolower($_POST["busqueda"])."%' LIMIT 0,50";
   $resultado = mysql_query($consulta, $db);

   // Si no encontramos restaurantes con los datos pasados, error 100.
   if(mysql_num_rows($resultado)==0) {
      $json[0] = Array('status' => 100);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Tenemos resultados
   $i = 1;
   $json[0] = array('status' => 1, 'longitud' => mysql_num_rows($resultado));
   while($datos_bbdd = mysql_fetch_array($resultado)) {
      $json[$i] = array(
         'ID' => $datos_bbdd['ID'],
         'Nombre' => utf8_encode($datos_bbdd['Nombre']),
         'Direccion' => utf8_encode($datos_bbdd['Direccion']),
         'Poblacion' => utf8_encode($datos_bbdd['Poblacion']),
         'Provincia' => utf8_encode($datos_bbdd['Provincia']),
         'Lat' => utf8_encode($datos_bbdd['Lat']),
         'Lng' => utf8_encode($datos_bbdd['Lng']),
         'Tipo' => utf8_encode($datos_bbdd['Tipo'])
         );
      $i++;
   }
   echo stripslashes(json_encode($json,JSON_UNESCAPED_UNICODE));
   exit;
}


// Funcion que devuelve un establecimiento recomendado cercano a nosotros
function recomendacion() {
   // lat: Latitud del usuario
   // lng: Longitud del usuario
   require("./lib/bbdd.php");

   // Controlo variables recibidas por post
   if((!isset($_POST["lat"]))||(!isset($_POST["lng"]))) {
      $json[0] = Array('status' => 1000);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   $eleccion = 4; // Numero de restaurantes entre los que se va a hacer el algoritmo aleatorio para mostrar 
   $num = 25; // Numero de restaurantes cercanos para ordenarlos por puntuacion y elegir entre los "$ELECCION" primeros
   $dst = 5000; // Busco establecimientos 50km alrededor de la posicion del usuario
   $zonadelimitada = getBoundaries($_POST["lat"], $_POST["lng"], $dst);

   $consulta = "SELECT Establecimientos.ID, Establecimientos.Nombre, AVG(Votaciones.Puntuacion) AS Puntuacion, (6371 * ACOS(
   SIN(RADIANS(Establecimientos.lat)) * SIN(RADIANS(".$_POST["lat"]."))
   + COS(RADIANS(Establecimientos.lng - ".$_POST["lng"].")) * COS(RADIANS(Establecimientos.lat))
   * COS(RADIANS(".$_POST["lat"]."))
   )
   ) AS Distancia
   FROM Establecimientos
   INNER JOIN Votaciones ON Establecimientos.ID = Votaciones.IDEstablecimiento
   WHERE (Establecimientos.lat BETWEEN " . $zonadelimitada['min_lat']. " AND " . $zonadelimitada['max_lat'] . ")
   AND (Establecimientos.lng BETWEEN " . $zonadelimitada['min_lng']. " AND " . $zonadelimitada['max_lng']. ")
   GROUP BY Establecimientos.ID
   HAVING Distancia < ".$dst." 
   ORDER BY Puntuacion DESC
   LIMIT 0,".$num;
   $resultado = mysql_query($consulta, $db);

   // Si no encontramos restaurantes con los datos pasados, error 100.
   if(mysql_num_rows($resultado)==0) {
      $json[0] = Array('status' => 100);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Tenemos resultados. Hay que elegir uno aleatoriamente.
   $id = rand(1, $eleccion); 

   // Modificar SQL para que haga un ORDER BY Puntuacion ASC que esta en FROM Votaciones 
   $consulta = "SELECT ID, Tipo, Nombre, Direccion, CP, Poblacion, Provincia, Pais, Lat, Lng, PrecioMedio, Web, WebReserva, Foto, Descripcion, Telefono, Alergant, IDAlergia, AcuerdoAsociacion FROM Establecimientos WHERE ID='".$id."'";
   $resultado = mysql_query($consulta, $db);

   // Si no existe el restaurante con el ID pasado, error 1003.
   if(mysql_num_rows($resultado)==0) {
      $json[0] = Array('status' => 1003);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }
   // Saco los datos de la BBDD
   $datos_bbdd = mysql_fetch_array($resultado);

   $consulta = "SELECT AVG(Puntuacion) AS Puntuacion, AVG(Precio) AS Precio, AVG(Ambiente) AS Ambiente, AVG(Variedad) AS Variedad, AVG(Calidad) AS Calidad FROM Votaciones WHERE IDEstablecimiento='".$_POST["id"]."'";
   $resultado = mysql_query($consulta, $db);
   $datos = mysql_fetch_array($resultado);

   // Meto los datos al array.
   $json[0] = Array('status' => 1);
   $json[1] = Array('ID' => $datos_bbdd['ID'],
                    'Tipo' => $datos_bbdd['Tipo'],
                    'Nombre' => utf8_encode($datos_bbdd['Nombre']),
                    'Direccion' => utf8_encode($datos_bbdd['Direccion']),
                    'CP' => $datos_bbdd['CP'],
                    'Poblacion' => utf8_encode($datos_bbdd['Poblacion']),
                    'Provincia' => utf8_encode($datos_bbdd['Provincia']),
                    'Pais' => utf8_encode($datos_bbdd['Pais']),
                    'Lat' => $datos_bbdd['Lat'],
                    'Lng' => $datos_bbdd['Lng'],
                    'PrecioMedio' => $datos_bbdd['PrecioMedio'],
                    'Web' => utf8_encode($datos_bbdd['Web']),
                    'WebReserva' => utf8_encode($datos_bbdd['WebReserva']),
                    'Foto' => $datos_bbdd['Foto'],
                    'Descripcion' => utf8_encode($datos_bbdd['Descripcion']),
                    'Telefono' => $datos_bbdd['Telefono'],
                    'Alergant' => $datos_bbdd['Alergant'],
                    'IDAlergia' => $datos_bbdd['IDAlergia'],
                    'AcuerdoAsociacion' => $datos_bbdd['AcuerdoAsociacion'],
                    'Puntuacion' => $datos['Puntuacion'],
                    'Precio' => $datos['Precio'],
                    'Ambiente' => $datos['Ambiente'],
                    'Variedad' => $datos['Variedad'],
                    'Calidad' => $datos['Calidad']
                    );
   Header("Content-Type: application/json");
   echo stripslashes(json_encode($json,JSON_UNESCAPED_UNICODE));
   exit;

}


// Funcion que devuelve los detalles de un establecimiento dado su ID
function detalles() {
   // id: ID de establecimiento
   require("./lib/bbdd.php");

   // Controlo variables recibidas por post
   if(!isset($_POST["id"])) {
      $json[0] = Array('status' => 1000);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   $consulta = "SELECT ID, Tipo, Nombre, Direccion, CP, Poblacion, Provincia, Pais, Lat, Lng, PrecioMedio, Web, WebReserva, Carta, Foto, Descripcion, Telefono, Alergant, Propietario, IDAlergia, AcuerdoAsociacion FROM Establecimientos WHERE ID='".$_POST["id"]."'";
   $resultado = mysql_query($consulta, $db);

   // Si no existe el restaurante con el ID pasado, error 1003.
   if(mysql_num_rows($resultado)==0) {
      $json[0] = Array('status' => 1003);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Saco los datos de la BBDD
   $datos_bbdd = mysql_fetch_array($resultado);

   $consulta = "SELECT AVG(Puntuacion) AS Puntuacion, AVG(Precio) AS Precio, AVG(Ambiente) AS Ambiente, AVG(Variedad) AS Variedad, AVG(Calidad) AS Calidad FROM Votaciones WHERE IDEstablecimiento='".$_POST["id"]."'";
   $resultado = mysql_query($consulta, $db);
   $datos = mysql_fetch_array($resultado);

   // Meto los datos al array.
   $json[0] = Array('status' => 1, 'longitud' => 1);
   $json[1] = Array('ID' => $datos_bbdd['ID'],
                    'Estado' => $datos_bbdd['Estado'],
                    'Propietario' => $datos_bbdd['Propietario'],
                    'Tipo' => $datos_bbdd['Tipo'],
                    'Nombre' => utf8_encode($datos_bbdd['Nombre']),
                    'Direccion' => utf8_encode($datos_bbdd['Direccion']),
                    'CP' => $datos_bbdd['CP'],
                    'Poblacion' => utf8_encode($datos_bbdd['Poblacion']),
                    'Provincia' => utf8_encode($datos_bbdd['Provincia']),
                    'Pais' => utf8_encode($datos_bbdd['Pais']),
                    'Lat' => $datos_bbdd['Lat'],
                    'Lng' => $datos_bbdd['Lng'],
                    'PrecioMedio' => $datos_bbdd['PrecioMedio'],
                    'Web' => utf8_encode($datos_bbdd['Web']),
                    'WebReserva' => utf8_encode($datos_bbdd['WebReserva']),
                    'Foto' => $datos_bbdd['Foto'],
                    'Carta' => $datos_bbdd['Carta'],
                    'Descripcion' => utf8_encode($datos_bbdd['Descripcion']),
                    'Telefono' => $datos_bbdd['Telefono'],
                    'Alergant' => $datos_bbdd['Alergant'],
                    'IDAlergia' => $datos_bbdd['IDAlergia'],
                    'Propietario' => $datos_bbdd['Propietario'],
                    'AcuerdoAsociacion' => $datos_bbdd['AcuerdoAsociacion'],
		    'Puntuacion' => $datos['Puntuacion'],
		    'Precio' => $datos['Precio'],
		    'Ambiente' => $datos['Ambiente'],
		    'Variedad' => $datos['Variedad'],
		    'Calidad' => $datos['Calidad']
		    );
   Header("Content-Type: application/json");
   echo stripslashes(json_encode($json,JSON_UNESCAPED_UNICODE));
   exit;
}


// Funcion para realizar la votacion de un establecimiento
function votacion() {
   // username: Usuario
   // password: Password en MD5
   // id: ID del establecimiento
   // idalergia: ID de la alergia a votar
   // puntuacion: Puntuacion del establecimiento
   // precio:
   // ambiente:
   // calidad:
   // variedad:
   // observaciones:

   require("./lib/bbdd.php");

   // Controlo variables recibidas por post
   if((!isset($_POST["username"]))||(!isset($_POST["password"]))||(!isset($_POST["id"]))||(!isset($_POST["idalergia"]))||(!isset($_POST["puntuacion"]))||(!isset($_POST["precio"]))||(!isset($_POST["ambiente"]))||(!isset($_POST["calidad"]))||(!isset($_POST["variedad"]))||(!isset($_POST["observaciones"]))) {
      $json[0] = Array('status' => 1000);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   $fecha = Date("d/m/Y");
   // Verificamos que el usuario se autentica correctamente y no quiero que genere la salida JSON.
   authcheck(0);

   // Quiero el ID del usuario
   $consulta = "SELECT ID FROM Usuarios WHERE Usuario='".$_POST["username"]."'";
   $resultado = mysql_query($consulta, $db);
   $idusuario = mysql_fetch_array($resultado);

   // Verifico que el establecimiento existe.
   $consulta = "SELECT ID FROM Establecimientos WHERE ID='".$_POST["id"]."'";
   $resultado = mysql_query($consulta, $db);
   if(mysql_num_rows($resultado)==0) {
      $json[0] = Array('status' => 1008);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // Verifico que el usuario no ha votado en el dia de hoy.
   $consulta = "SELECT COUNT(Votaciones.Fecha) AS Votaciones FROM Votaciones INNER JOIN Usuarios WHERE Votaciones.Fecha='".$fecha."' AND Usuarios.Usuario='".$_POST["username"]."'";
   $resultado = mysql_query($consulta, $db);
   $datos = mysql_fetch_array($resultado);
   if($datos[0]>=1) {
      $json[0] = Array('status' => 1007);
      Header("Content-Type: application/json");
      echo json_encode($json,JSON_UNESCAPED_UNICODE);
      exit;
   }

   // El usuario se ha identificado correctamente y no ha votado ya en el dia de hoy. Actualizo o inserto votacion.
   $consulta = "SELECT ID FROM Votaciones WHERE IDEstablecimiento='".$_POST["id"]."' AND IDUsuario='".$idusuario[0]."'";
   $resultado = mysql_query($consulta, $db);
   if(mysql_num_rows($resultado)==0) {
      // No ha votado
      $consulta = "INSERT INTO Votaciones VALUES(null, '".$idusuario[0]."', '".$_POST["id"]."', '".$_POST["idalergia"]."', '".$_POST["observaciones"]."', '".$_POST["puntuacion"]."', '".$_POST["precio"]."', '".$_POST["ambiente"]."', '".$_POST["variedad"]."', '".$_POST["calidad"]."', '".$fecha."')";
   } else {
      // Si ha votado, actualizamos la valoracion
      $idvotacion = mysql_fetch_array($resultado);
      $consulta = "UPDATE Votaciones SET Observaciones='".$_POST["observaciones"]."', Puntuacion='".$_POST["puntuacion"]."', Precio='".$_POST["precio"]."', Ambiente='".$_POST["ambiente"]."', Variedad='".$_POST["variedad"]."', Calidad='".$_POST["calidad"]."', Fecha='".$fecha."' WHERE ID='".$idvotacion[0]."'";
   }

   $resultado = mysql_query($consulta, $db);
   if((!$resultado)||(mysql_affected_rows()==0)) {
      // Si no se ha podido insertar el registro en la BBDD, genero error.
      $json = array('status' => 104);
   } else {
      $json = array('status' => 1);
   }

   Header("Content-Type: application/json");
   echo json_encode($json,JSON_UNESCAPED_UNICODE);
   exit;

}


// Funcion que devuelve las preferencias del usuario
function preferencias() {
   // username: Usuario
   // password: Password en MD5
   require("./lib/bbdd.php");

   // Verificamos que el usuario se autentica correctamente y no quiero que genere la salida JSON.
   authcheck(0);

   $consulta = "SELECT ID, Nombre, Apellidos, Correo, Usuario, Alergias FROM Usuarios WHERE Usuario='".$_POST["username"]."'";
   $resultado = mysql_query($consulta, $db);
   $datos_bbdd = mysql_fetch_array($resultado);

   // Meto los datos al array.
   $json[0] = Array('status' => 1, 'longitud' => 1);
   $json[1] = Array('ID' => $datos_bbdd['ID'],
                    'Nombre' => utf8_encode($datos_bbdd['Nombre']),
                    'Apellidos' => utf8_encode($datos_bbdd['Apellidos']),
                    'Correo' => utf8_encode($datos_bbdd['Correo']),
                    'Usuario' => utf8_encode($datos_bbdd['Usuario']),
                    'Alergias' => $datos_bbdd['Alergias']
                    );
   Header("Content-Type: application/json");
   echo stripslashes(json_encode($json,JSON_UNESCAPED_UNICODE));
   exit;
}

?>

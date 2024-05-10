<?php 

require_once dirname(__FILE__) . '/private/conf.php';
require dirname(__FILE__) . '/private/auth.php';
if (isset($_POST['body']) && isset($_GET['id'])) { $playerId = $db->escapeString($_GET['id'])
	; $userId = $db->escapeString($_COOKIE['userId']);
		$body = $db->escapeString($_POST['body']);
		$query = "INSERT INTO comments (playerId, userId, body) VALUES (?, ?, ?)";
		$stmt = $db->prepare($query); $stmt->bind_param("iss", $playerId, $userId, $body);
		$stmt->execute(); if ($stmt->affected_rows > 0) { header("Location: list_players.php");
	exit; } else 
		{ echo "Error al insertar datos en la base de datos.";
	} } 
	
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/style.css">
    <title>Práctica RA3 - Comments creator</title>
</head>
<body>
<header>
    <h1>Comments creator</h1>
</header>
<main class="player">
    <form action="#" method="post">
        <h3>Write your comment</h3>
        <textarea name="body"></textarea>
        <input type="submit" value="Send">
    </form>
    <form action="#" method="post" class="menu-form">
        <a href="list_players.php">Back to list</a>
        <input type="submit" name="Logout" value="Logout" class="logout">
    </form>
</main>
<footer class="listado">
    <img src="images/logo-iesra-cadiz-color-blanco.png">
    <h4>Puesta en producción segura</h4>
    < Please <a href="http://www.donate.co?amount=100&amp;destination=ACMEScouting/"> donate</a> >
</footer>
</body>
</html>

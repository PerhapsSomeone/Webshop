<?php

require "../autoload.php";

if(!isset($_GET["search"]) || empty($_GET["search"])) {
    header("Location: index.php");
}

?>

<html>
<head>
    <title><?= getenv("SHOP_NAME") ?></title>
    <link rel="stylesheet" href="/css/styling.css" />
    <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
</head>
<body>
<?php include("../layouts/navbar.php") ?>
<div class="centered">
    <h1>Search results for "<?= $_GET["search"] ?>"</h1>
</div>
</body>
</html>
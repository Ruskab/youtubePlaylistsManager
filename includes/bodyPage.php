<html>
<head>
    <title>Youtube recover</title>
    <?php include("includes/head.php"); ?>
</head>

<body>

<header class="w3-container w3-red">
    <h1 style="text-shadow:2px 2px 0 #444"><b>Youtube PlayList recover</b></h1>
</header>

<div class="w3-container">
    <div class="w3-container">
        <p><?= $htmlBody ?></p>
        <ul class="w3-ul w3-card-4 w3-hoverable w3-center">
                <?= $htmlListItems ?>
        </ul>
    </div>

    <div class="w3-panel">
            <a href="index.php" class="w3-btn w3-black w3-hover-red w3-block w3-border w3-xlarge">Inicio</a>
    </div>
</div>
</body>
</html>

<?php
/**
 * Created by PhpStorm.
 * User: ilyak
 * Date: 23/08/2018
 * Time: 20:15
 */
<?php
// hash.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = trim($_POST['password']);

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $erreur = "Veuillez saisir un mot de passe.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générateur de Hash</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f5f5f5;
            margin:40px;
        }
        .container{
            max-width:600px;
            margin:auto;
            background:#fff;
            padding:20px;
            border-radius:8px;
            box-shadow:0 2px 10px rgba(0,0,0,.1);
        }
        input[type=text], textarea{
            width:100%;
            padding:10px;
            margin:10px 0;
            box-sizing:border-box;
        }
        button{
            background:#0d6efd;
            color:#fff;
            border:none;
            padding:10px 20px;
            border-radius:5px;
            cursor:pointer;
        }
        textarea{
            height:120px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Générateur de Hash PHP</h2>

    <form method="POST">
        <label>Mot de passe :</label>
        <input type="text" name="password" placeholder="Entrez un mot de passe" required>

        <button type="submit">Générer le Hash</button>
    </form>

    <?php if(isset($erreur)): ?>
        <p style="color:red"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <?php if(isset($hash)): ?>
        <h3>Hash généré :</h3>
        <textarea readonly><?= htmlspecialchars($hash) ?></textarea>
    <?php endif; ?>

</div>

</body>
</html>
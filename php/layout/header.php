<?php
ob_start();
$included = strtolower(realpath(__FILE__)) != strtolower(realpath($_SERVER['SCRIPT_FILENAME']));
if(!$included) die();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title><?=($mpage=='' ? strip_tags(SITE_NAME.' | '.SITE_SLOGAN) : strip_tags($title.' | '.SITE_NAME));?></title>

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    
    <!-- Saját CSS -->
    <link href="/css/fanta.css?v=<?= filemtime(DOC_ROOT.'/css/fanta.css') ?>" rel="stylesheet">
    <link href="/css/lista.css" rel="stylesheet">
    
    <?=$plusmeta;?>

    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Fanta Lyrics" />
    <link rel="manifest" href="/site.webmanifest" />
</head>
<body>
<?php
if(isset($_SESSION['errormsg'])) {
    echo '<div class="alert alert-danger text-center position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:10000; min-width: 300px;">'.$_SESSION['errormsg'].'</div>';
    unset($_SESSION['errormsg']);
}

if($mpage != 'admin' && $mpage != 'belépés'){
    require_once('php/layout/nav.php');
}
?>

<?php
ob_start();
// FREE DATING WEBAPP

header("Cache-Control: must-revalidate");
header('Content-Type: text/html; charset=UTF-8');

//inicializálás

require_once('php/init.php');

$title='';
$description='';
$keywords='';
$plusmeta='';
$incpage='';


switch($mpage) //oldal kezelés
  {

    case '':

    $title=SITE_NAME;
		$description='';
		$keywords='';
		$incpage='live.php';
		


    break;

    case 'songs':
      $title=SITE_NAME;
      $description='';
      $keywords='';
      $incpage='songs.php';     
  
    break; 

    case 'live':
        $title=SITE_NAME;
        $description='';
        $keywords='';
        $incpage='live.php';     

    break; 

    case 'list':
        $title=SITE_NAME;
        $description='';
        $keywords='';
        $incpage='list.php';     

    break; 
  }




  //elrendezés
  

  require_once('php/layout/header.php');

  if(!is_dir(DOC_ROOT.'/php/page/'.$incpage) && file_exists(DOC_ROOT.'/php/page/'.$incpage))
	include(DOC_ROOT.'/php/page/'.$incpage);
else
	echo '-';

    require_once('php/layout/footer.php');

?>
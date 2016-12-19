<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */

if(!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);

	require('../config.php');

}


dol_include_once('/metre/class/metre.class.php');
	
	$PDOdb=new TPDOdb;
	//$PDOdb->debug=true;


	$o=new TMetreCommandedet;
	$o->init_db_by_vars($PDOdb);
	
	$o=new TMetrePropaldet;
	$o->init_db_by_vars($PDOdb);
	
	$o=new TMetreFacturedet;
	$o->init_db_by_vars($PDOdb);
	
	$o=new TMetreCommandeFourndet;
	$o->init_db_by_vars($PDOdb);
	

<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		core/triggers/interface_99_modMyodule_metretrigger.class.php
 * 	\ingroup	metre
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modMymodule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class Interfacemetretrigger
{

    private $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of metre";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        $this->picto = 'tecnic';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }
	
	function _updateTotauxLine(&$object,$qty){
		//MAJ des totaux de la ligne
		$object->total_ht  = price2num($object->subprice * $qty * (1 - $object->remise_percent / 100), 'MT');
		$object->total_tva = price2num(($object->total_ht * (1 + ($object->tva_tx/100))) - $object->total_ht, 'MT');
		$object->total_ttc = price2num($object->total_ht + $object->total_tva, 'MT');
		if (method_exists($object, 'update_total')) $object->update_total();
		elseif (method_exists($object, 'updateTotal')) $object->updateTotal();
	}

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        if(!defined('INC_FROM_DOLIBARR'))define('INC_FROM_DOLIBARR',true);
		dol_include_once('/tarif/config.php');
		dol_include_once('/tarif/class/tarif.class.php');
		dol_include_once('/commande/class/commande.class.php');
		dol_include_once('/fourn/class/fournisseur.commande.class.php');
		dol_include_once('/compta/facture/class/facture.class.php');
		dol_include_once('/comm/propal/class/propal.class.php');
		dol_include_once('/dispatch/class/dispatchdetail.class.php');
		
		global $user, $db,$conf;
		
		if (($action === 'LINEORDER_INSERT' || $action === 'LINEPROPAL_INSERT' || $action === 'LINEBILL_INSERT' ) 
			&& (!isset($_REQUEST['notrigger']) || $_REQUEST['notrigger'] != 1)
			&& (!empty($object->fk_product) || !empty($_REQUEST['idprodfournprice']))
			&& (!empty($_REQUEST['addline_predefined']) || !empty($_REQUEST['addline_libre'])  || !empty($_REQUEST['prod_entry_mode']))) {
				if(get_class($object) == 'PropaleLigne'){
				 $table = 'propal';
				 $tabledet = 'propaldet';
			}
			elseif(get_class($object) == 'OrderLine'){
				 $table = 'commande';
				 $tabledet = 'commandedet';
			}
			elseif(get_class($object) == 'FactureLigne'){
				 $table = 'facture';
				 $tabledet = 'facturedet';
			}
			elseif(get_class($object) == 'CommandeFournisseur' || get_class($object) == 'CommandeFournisseurLigne'){
				$table = "commande_fournisseur"; 
				$tabledet = 'commande_fournisseurdet'; 
				$parentfield = 'fk_commande';
			}
			
			if(!empty($_REQUEST['poidsAff_product'])){ //Si un poids produit a été transmis
				$poids = ($_REQUEST['poidsAff_product'] > 0) ? $_REQUEST['poidsAff_product'] : 1;
				
			}
			elseif(!empty($_REQUEST['poidsAff_libre'])){ //Si un poids ligne libre a été transmis
				$poids = ($_REQUEST['poidsAff_libre'] > 0) ? $_REQUEST['poidsAff_libre'] : 1;
			}
		
			if(isset($_REQUEST['weight_unitsAff_product'])){ //Si on a un unité produit transmise
				$weight_units = $_REQUEST['weight_unitsAff_product'];
			}
			else{ //Sinon on est sur un tarif à l'unité donc pas de gestion de poids => 69 chiffre pris au hasard
				$weight_units = 69;
			}
			
			if(!empty($poids) && $object->product_type ==0 && $conf->global->METRE_UNIT_PRICE_BY_CALCULATION) {
				$object->price *= $poids;
				$object->subprice *= $poids;
				
				$this->_updateTotauxLine($object,$object->qty);
				$object->update($user);	
				
				
			}
			$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
			$this->db->query("UPDATE ".MAIN_DB_PREFIX.$tabledet." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
			
			$monUrl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
			header('Location: '.$monUrl);
			
			}elseif(($action === 'LINEORDER_INSERT' || $action === 'LINEPROPAL_INSERT' || $action === 'LINEBILL_INSERT' )  && $object->product_type ==0 && $conf->global->METRE_UNIT_PRICE_BY_CALCULATION){
			if(get_class($object) == 'PropaleLigne'){
				 $table = 'propal';
				 $tabledet = 'propaldet';
			}
			elseif(get_class($object) == 'OrderLine'){
				 $table = 'commande';
				 $tabledet = 'commandedet';
			}
			elseif(get_class($object) == 'FactureLigne'){
				 $table = 'facture';
				 $tabledet = 'facturedet';
			}
			elseif(get_class($object) == 'CommandeFournisseur' || get_class($object) == 'CommandeFournisseurLigne'){
				$table = "commande_fournisseur"; 
				$tabledet = 'commande_fournisseurdet'; 
				$parentfield = 'fk_commande';
			}
			
			if(!empty($_REQUEST['poidsAff_product'])){ //Si un poids produit a été transmis
				$poids = ($_REQUEST['poidsAff_product'] > 0) ? $_REQUEST['poidsAff_product'] : 1;
				
			}
			elseif(!empty($_REQUEST['poidsAff_libre'])){ //Si un poids ligne libre a été transmis
				$poids = ($_REQUEST['poidsAff_libre'] > 0) ? $_REQUEST['poidsAff_libre'] : 1;
			}
		
			if(isset($_REQUEST['weight_unitsAff_product'])){ //Si on a un unité produit transmise
				$weight_units = $_REQUEST['weight_unitsAff_product'];
			}
			else{ //Sinon on est sur un tarif à l'unité donc pas de gestion de poids => 69 chiffre pris au hasard
				$weight_units = 69;
			}
			if(!empty($poids)){
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$tabledet." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
			}
			
			
			
			$monUrl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
			header('Location: '.$monUrl);
		}elseif(($action == 'LINEORDER_UPDATE' || $action == 'LINEPROPAL_UPDATE' || $action == 'LINEBILL_UPDATE'  ) 
				&& (!isset($_REQUEST['notrigger']) || $_REQUEST['notrigger'] != 1)) {
			if(get_class($object) == 'PropaleLigne'){
				 $table = 'propal';
				 $tabledet = 'propaldet';
			}
			elseif(get_class($object) == 'OrderLine'){
				 $table = 'commande';
				 $tabledet = 'commandedet';
			}
			elseif(get_class($object) == 'FactureLigne'){
				 $table = 'facture';
				 $tabledet = 'facturedet';
			}
			elseif(get_class($object) == 'CommandeFournisseur' || get_class($object) == 'CommandeFournisseurLigne'){
				$table = "commande_fournisseur"; 
				$tabledet = 'commande_fournisseurdet'; 
				$parentfield = 'fk_commande';
			}
			
			if(!empty($_REQUEST['poidsAff_product'])){ //Si un poids produit a été transmis
				$poids = ($_REQUEST['poidsAff_product'] > 0) ? $_REQUEST['poidsAff_product'] : 1;
				
			}
			elseif(!empty($_REQUEST['poidsAff_libre'])){ //Si un poids ligne libre a été transmis
				$poids = ($_REQUEST['poidsAff_libre'] > 0) ? $_REQUEST['poidsAff_libre'] : 1;
			}
		
			if(isset($_REQUEST['weight_unitsAff_product'])){ //Si on a un unité produit transmise
				$weight_units = $_REQUEST['weight_unitsAff_product'];
			}
			else{ //Sinon on est sur un tarif à l'unité donc pas de gestion de poids => 69 chiffre pris au hasard
				$weight_units = 69;
			}
			if(!empty($poids)){
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$table." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
				$this->db->query("UPDATE ".MAIN_DB_PREFIX.$tabledet." SET tarif_poids = ".$poids.", poids = ".$weight_units." WHERE rowid = ".$object->rowid);
			}
			$monUrl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
			header('Location: '.$monUrl);
		}

        return 0;
    }
}
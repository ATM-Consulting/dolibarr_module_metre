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
 * \file    class/actions_metre.class.php
 * \ingroup metre
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionsmetre
 */
class Actionsmetre
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function formObjectOptions ($parameters, &$object, &$action, $hookmanager) {
		global $db,$conf,$langs;
		
		$langs->load('metre@metre');
		
    	if (in_array('propalcard',explode(':',$parameters['context']))
    		|| in_array('ordercard',explode(':',$parameters['context']))
    		|| in_array('invoicecard',explode(':',$parameters['context'])))
        {
        	
			?>
				<script type="text/javascript">
					var dialog = '<div id="dialog-metre" title="<?php print $langs->trans('tarifSaveMetre'); ?>"><p><label name="label_long">Longueur :</label><input type="text" name="metre_long" /><label name="label_larg">Largeur : </label><input type="text" name="metre_larg" /></p></div>';
					$(document).ready(function() {
						$('body').append(dialog);
						$('#dialog-metre').dialog({
							autoOpen:false
							,buttons: { 
										"Mode Avancé" : function(){
											$('input[name=metre_larg]').hide();
											$('label[name=label_larg]').hide();
											if($("span:contains('Mode Avancé')")){
												if($("span:contains('Mode Standard')").text()){
													$("span:contains('Mode Standard')").text('Mode Avancé');
													$('label[name=label_long]').text('Longueur  :');
													$('input[name=metre_larg]').show();
													$('label[name=label_larg]').show();
												} else {
													$("span:contains('Mode Avancé')").text('Mode Standard');
													$('label[name=label_long]').text('Formule  :');
													$('input[name=metre_larg]').val("");
												}
											
											}
										}
										,"Ok": function() {
											var metre = $('input[name=metre_long]').val();
											var larg = $('input[name=metre_larg]').val();
											
											$('input[name=metre]').val(metre );
											if(larg == ""){
												<?php if($conf->global->METRE_UNIT_PRICE_BY_CALCULATION) {?>
												$('input[name=qty]').val( eval(metre) );	
												
											
													<?php } ?>
											} else {
												<?php if($conf->global->METRE_UNIT_PRICE_BY_CALCULATION) {?>
												$('input[name=qty').val( eval(metre)*eval(larg) );
												$('input[name=metre]').val("("+metre +")*("+larg+")" );
												
												<?php } ?>
											}
										
											$(this).dialog("close");
										}
										,"Annuler": function() {
											$(this).dialog("close");
										}
									  }
						});
					});
					
					function show_Metre() {
						$('input[name=metre_long]').val( $('input[name=metre]').val() );	
						$('#dialog-metre').dialog('open');	
					}
					
				</script>
					
				
				<?php 
		
			if($action === 'editline' || $action === "edit_line"){
				
				$lineid = GETPOST('lineid');
				
				?>	
				<script type="text/javascript">
					/* script tarif */
					$(document).ready(function(){
						<?php
						
						dol_include_once('/product/class/html.formproduct.class.php');
						$formproduct = new FormProduct($db);

							$sql = "SELECT  pe.unite_vente,e.metre 
	         									 FROM ".MAIN_DB_PREFIX.$object->table_element_line." as e 
	         									 	LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON (e.fk_product = pe.fk_object)
	         									 WHERE e.rowid = ".$lineid;
							$resql = $db->query($sql);
							$res = $db->fetch_object($resql);
							
							?>$('input[name=qty]').parent().after('<td align="right"><?php
										?><?php
							
									
							
									
									print '<a href="javascript:show_Metre()">M</a><input type="hidden" name="metre" value="'.$res->metre.'" />';
									
							
							?></td>');
							

							<?php
						
						
						?>

					});
				</script>
				<?php 
			}
		}
		
	}

	function formBuilddocOptions ($parameters, &$object, &$action, $hookmanager) {
		global $db,$langs,$conf;
		include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
		include_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
		include_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
		include_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");
		include_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
		include_once(DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php');
		$langs->load("other");
		$langs->load("metre@metre");

		define('INC_FROM_DOLIBARR', true);
		dol_include_once('/metre/config.php');
		
		if(!defined('DOL_DEFAULT_UNIT')){
			define('DOL_DEFAULT_UNIT','weight');
		}
		
		if (in_array('propalcard',explode(':',$parameters['context']))
			|| in_array('ordercard',explode(':',$parameters['context']))
			|| in_array('invoicecard',explode(':',$parameters['context']))) 
        {
        		
			if($object->line->error)
				dol_htmloutput_mesg($object->line->error,'', 'error');
			
			//var_dump($object->lines);
			
        	?>
         	<script type="text/javascript">
         		<?php

	         		?>


		         	$('#dp_desc').parent().next().next().next().after('<td align="right" tarif-col="conditionnement_product" type_unite="<?php echo $type_unite; ?>"><?php
			         		
			         			?><?php
							
		         			
							
								print '<a href="javascript:show_Metre(0)">M</a><input type="hidden" name="metre" value="" />';
							
							
		         			?></td>');

		         	  	<?php 
				
					
	         	
	         	?>
	         /*	$('#addpredefinedproduct').append('<input class="poids_product" type="hidden" value="1" name="poids" size="3">');
	         	$('#addpredefinedproduct').append('<input class="weight_units_product" type="hidden" value="0" name="weight_units" size="3">');
	         	*/
	
	         	

         	</script>
         	<?php
        }


}
}
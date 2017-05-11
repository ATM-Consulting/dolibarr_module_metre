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
		
		if (in_array('propalcard',explode(':',$parameters['context']))
    		|| in_array('ordercard',explode(':',$parameters['context']))
    		|| in_array('invoicecard',explode(':',$parameters['context'])))
        {
        	$langs->load('metre@metre');
        	
			?>
				<script type="text/javascript">
					var metre_dialog_standard = 1;
				
					var dialog = '<div id="dialog-metre" title="<?php print $langs->trans('tarifSaveMetre'); ?>"><p>'
						+'<div class="standard"><div><label name="label_long"><?php echo $langs->trans('Height') ?> :</label><input type="text" name="metre_long" /></div>'
						+'<div><label name="label_larg"><?php echo $langs->trans('Width') ?> : </label><input type="text" name="metre_larg" /></div>'
						+'<div rel="metre_depth"><label name="label_depth"><?php echo $langs->trans('Depth') ?> : </label><input type="text" name="metre_depth" /></div></div>'
						+'<div class="advanced" rel="formule" style="display:none;"><label name="formule"><?php echo $langs->trans('Formule') ?> : </label><br /><textarea name="formule" size="20" rows="3"></textarea></div>'
					+'</p></div>';
					
					$(document).ready(function() {
						
						$('body').append(dialog);
						$('#dialog-metre').dialog({
							autoOpen:false
							,buttons: { 
										"<?php echo $langs->transnoentities('AdvancedMode') ?>" : function(){
											metre_dialog_show();

										}
										,"Ok": function() {

											if(metre_dialog_standard == 1) {
												var vlong = parseFloat( $('input[name=metre_long]').val() );
												var larg = parseFloat( $('input[name=metre_larg]').val() );
												var depth =parseFloat( $('input[name=metre_depth]').val() );

												if(isNaN(vlong)) vlong = 1;
												if(isNaN(larg)) larg = 1;
												if(isNaN(depth)) depth = 1;
												
												var metre = "("+vlong +")*("+larg+")*("+depth+")";

												
											}
											else {
												var metre = $("textarea[name=formule]").val();
											}

											$('input[name=metre]').val( metre );
											$('input[name=qty]').val( eval( ' ('+ metre +')' ) );	
											
											
											$(this).dialog("close");
										}
										,"Annuler": function() {
											$(this).dialog("close");
										}
									  }
						});
					
         			<?php
         			
         			$metre_formule = ''; 
         			
					if($action === 'editline' || $action === "edit_line"){
						
						$lineid = GETPOST('lineid');
						
						$sql = "SELECT e.metre FROM ".MAIN_DB_PREFIX.$object->table_element_line." as e WHERE e.rowid = ".$lineid;
						$resql = $db->query($sql);
						$obj= $db->fetch_object($resql);
						
						$metre_formule =  $obj->metre ;
						
						if(preg_match ( '/(\(\d*\.?\d*\)\**){3}/',$metre_formule)) {
							$matches=array();
							$matches = sscanf($obj->metre, "(%f)*(%f)*(%f)");
																	
							echo ' $("#dialog-metre input[name=metre_long]").val("'.$matches[0].'"); ';
							echo ' $("#dialog-metre input[name=metre_larg]").val("'.$matches[1].'"); ';
							echo ' $("#dialog-metre input[name=metre_depth]").val("'.$matches[2].'"); ';
							
						}
						else {
							echo 'metre_dialog_show();'; // switch en mode avancé
						}
						
						echo ' $("input[name=metre]").val("'.$metre_formule.'"); ';
						
					}		
						?>

						var $qtyfield = $('input#qty'); 
	         			$qtyfield.closest('td').attr('nowrap','nowrap');
	         			$qtyfield.after(' <a href="javascript:show_Metre()"><?php echo img_picto($langs->trans('Metre'), 'object_metre@metre',' align="middle" ') ?></a><input type="hidden" name="metre" value="<?php echo $metre_formule; ?>" />');
					
					});

					function metre_dialog_show() {
						
						if(metre_dialog_standard == 1) {
							$('div.ui-dialog-buttonset > button.ui-button:first > span.ui-button-text').text('<?php echo $langs->transnoentities('StandardMode') ?>');
							$('div.standard').hide();
							$('div.advanced').show();
							metre_dialog_standard = 0;
						}
						else{
							$('div.ui-dialog-buttonset > button.ui-button:first > span.ui-button-text').text('<?php echo $langs->transnoentities('AdvancedMode') ?>');

							$('div.standard').show();
							$('div.advanced').hide();
							
							metre_dialog_standard = 1;
						}


					}
					
					function show_Metre() {
						var metre = $('input[name=metre]').val();
						
						$("textarea[name=formule]").val( metre );
							
						$('#dialog-metre').dialog('open');	
					}

					<?php 
					
					?>
				</script>
					
				
				<?php 
		
			
		}
		
	}



}
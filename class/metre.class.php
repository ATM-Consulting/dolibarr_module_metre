<?php 
class TMetreCommandedet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'commandedet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		parent::add_champs('metre');
		
		parent::_init_vars();
		parent::start();
	}
}

class TMetrePropaldet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'propaldet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		parent::add_champs('metre');
		
		parent::_init_vars();
		parent::start();
	}
}

class TMetreFacturedet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'facturedet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		parent::add_champs('metre');
		
		parent::_init_vars();
		parent::start();
	}
}

class TMetreCommandeFourndet extends TObjetStd {
	function __construct() { /* declaration */
		global $langs;
		
		parent::set_table(MAIN_DB_PREFIX.'commande_fournisseurdet');
		parent::add_champs('poids','type=entier;');
		parent::add_champs('tarif_poids','type=float;');
		parent::add_champs('metre');
		
		parent::_init_vars();
		parent::start();
	}
}
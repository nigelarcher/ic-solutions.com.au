<?php
/*
 * @author Chiru Bhavansikar (chiru@smartyhost.com.au)
 * @version 1.0
 * @package SmartyHost Reseller Website
 * @access private
 * @copyright Smartyhost Pty Ltd.
 */
 
//Application Variables
define("APPLICATION_NAME","SmartyHost Reseller");

//Directory Variables
define('TEMPLATE_DIR','tpl');
define('LIB_DIR','lib/clientcode');
define('COMPILE_DIR','compile');
define("SMARTY_DIR","Smarty/");

$smarty_dir_path = SMARTY_DIR . "Smarty.class.php";

//Site Path
define('SITE_PATH', 'http://www.ic-solutions.com.au');

//Include Files

require_once (LIB_DIR . '/settings.inc.php');
require_once (LIB_DIR . '/Currency.class.php');
require_once (LIB_DIR . '/Email.class.php');
require_once (LIB_DIR . '/Session.class.php');
require_once (LIB_DIR . '/Page.class.php');
require_once (LIB_DIR . '/WebPage.class.php');
require_once (LIB_DIR . '/DataParser.class.php');
require_once (LIB_DIR . '/Helper.class.php');
require_once (LIB_DIR . '/SmartyHostWebService.class.php');
require_once (LIB_DIR . '/Domain.class.php');
require_once (LIB_DIR . '/Cart.class.php');
require_once (LIB_DIR . '/xmlrpc.inc');

?>
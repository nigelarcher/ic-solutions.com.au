<?php
phpinfo();
error_reporting(1);
require_once('config.inc');
require_once('DomainOrder.class.php');
$session = &new Session();
$filter = &new DataParser();
$request = $filter->getRequestVariables();

if(isset($request['view']))
{
	$mode = strtoupper($request['view']);

	switch($mode)
	{	
		case "CHECK_CDETAILS":
			$order = &new Order($db);
			$order->checkContactDetails($request, $session);
			break;
			
		case "BILLING":
			$order = &new Order($db);
			$order->showBillingForm($request, $session);
			break;
			
		case "SAVE_ORDER":
			$order = &new Order($db);
			$order->saveOrder($request, $session, $filter);
			break;			

		case "THANKS":
			$order = &new Order($db);
			$order->showThankyou($request, $session);
			break;			
			
		case "RENEW_DOMAIN":
			$domain = &new DomainOrder($db);
			$domain->checkDomain($request, $session);
			break;		
			
		case "DOMAIN_LIST":
			$domain = &new DomainOrder($db);
			$domain->showDomainList($request, $session);
			break;									
			
		case "RENEW_FORM":
			$domain = &new DomainOrder($db);
			$domain->renewDomainForm($request, $session);
			break;									
			
		case "PROCESS_DOMAINS":
			$domain = &new DomainOrder($db);
			$domain->processDomains($request, $session, $filter);
			break;								
			
		case "PROCESS_DETAILS":
			$domain = &new DomainOrder($db);
			$domain->processDetails($request, $session, $filter);
			break;											
			
		case "REGISTER_DOMAIN":
			$domain = &new DomainOrder($db);
			$domain->checkRegisterDomain($request, $session, $filter);
			break;							
			
		case "REGISTER_DOMAIN_LIST":
			$domain = &new DomainOrder($db);
			$domain->showRegisterDomainList($request, $session);
			break;									

		case "PROCESS_REGISTER_DOMAINS":
			$domain = &new DomainOrder($db);
			$domain->processRegistrations($request, $session);
			break;						
			
		case "PROCESS_HOSTING":
			$domain = &new DomainOrder($db);
			$domain->processHosting($request, $session);
			break;																	
			
		case "BILLING_FORM":
			$domain = &new DomainOrder($db);
			$domain->showBillingForm($request, $session);
			break;									
			
		case "PROCESS_BILLING":
			$domain = &new DomainOrder($db);
			$domain->processBilling($request, $session, $filter);
			break;		
			
		default:		
			$helper = &new Helper($db);
			$helper->redirect(SITE_PATH);
	}
}

?>
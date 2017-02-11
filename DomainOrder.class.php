<?php

class DomainOrder
{
	var $db;
	var $helper;
	var $web_page;

	function DomainOrder(&$db)
	{
		$this->db = $db;
		$this->helper = &new Helper($db);
		$this->web_page = &new WebPage();
	}
	
	function checkDomain(&$request, &$session)
	{
		if ($request['add'] != 'Y')
		{
			$session->del('cart');
		}
		
		$domain_name = $request['domain_name'];
		$domain_ext = $request['domain_ext'];	
		
		$dom = strtolower($request['domain_name'] . "." . $request['domain_ext']);

		$domain =  &new Domain($domain_name, $domain_ext);
		if (!$domain->isValid())
		{
			echo "alert('The domain name you entered is not valid.');";
			exit;
		}
		if ($domain->isAvailable())
		{
			echo "alert('The domain name you entered is not registered.');";
			exit;
		}		

		global $domain_cost_table;
		$currency = &new Currency();
		$error = '';
		$password = '';
		
		$SH = &new SmartyHostWebService(RESELLER_USER, RESELLER_PASS);		
		
		if (($domain_ext == 'com.au') || ($domain_ext == 'net.au') || ($domain_ext == 'org.au') || ($domain_ext == 'id.au') || ($domain_ext == 'asn.au'))
		{
			// au domains
			$belongs_to_RSP = false;
			
			$result = $SH->auDomainBelongsToRSP($dom);	
			
			if ($result)
			{
				$result = xmlrpc_decode($result);			
			}
			else 
			{
				$belongs_to_RSP = false;
			}			

			if (($result['response_code'] == 400)||($result['response_code'] == 402))
			{
				$belongs_to_RSP = false;
			}
			else if ($result['response_code'] != 200)
			{
				$belongs_to_RSP = false;
			}		
			if ($result['belongs_to_rsp'] == 1)
			{
				$belongs_to_RSP = true;
			}					
			$period_options = '<option value="2">2 years</option>';
			$period = 2;
			$type = "AU";
			$price = $currency->toDollars($domain_cost_table[$domain_ext] * $period);
			if ($belongs_to_RSP == true)
			{			
				// check if domain renewable
				$renewable = false;
				
				$result = $SH->auDomainIsRenewable($dom);	
				
				if ($result)
				{
					$result = xmlrpc_decode($result);			
				}
				else 
				{
					$renewable = false;
				}			
	
				if (($result['response_code'] == 400)||($result['response_code'] == 402))
				{
					$renewable = false;
				}
				else if ($result['response_code'] != 200)
				{
					$renewable = false;
				}						
				if ($result['renewable'] == 1)
				{
					$renewable = true;
				}
				
				if ($renewable == false)
				{
					$error = 'Domain is not renewable. Reason : ' . $result['response_text'];
					$ordertype = 'Error';
				}
				else $ordertype = 'Renew';
			}
			else 
			{
				$ordertype = 'Transfer';		
			}
		} // end au domains
		else 
		{  // gtld domains
			$belongs_to_RSP = false;
			
			$result = $SH->gtldDomainBelongsToRSP($dom);	
			
			if ($result)
			{
				$result = xmlrpc_decode($result);			
			}
			else 
			{
				$belongs_to_RSP = false;
			}			

			if (($result['response_code'] == 400)||($result['response_code'] == 402))
			{
				$belongs_to_RSP = false;
			}
			else if ($result['response_code'] != 200)
			{
				$belongs_to_RSP = false;
			}		
			if ($result['belongs_to_rsp'] == 1)
			{
				$belongs_to_RSP = true;
			}					
			
			$period = 1;
			$type = "GTLD";
			$price = $currency->toDollars($domain_cost_table[$domain_ext] * $period);
			
			if ($belongs_to_RSP == true)
			{
				// check if domain renewable
				$renewable = false;
				
				$result = $SH->gtldDomainIsRenewable($dom);	
				
				if ($result)
				{
					$result = xmlrpc_decode($result);			
				}
				else 
				{
					$renewable = false;
				}			
	
				if (($result['response_code'] == 400)||($result['response_code'] == 402))
				{
					$renewable = false;
				}
				else if ($result['response_code'] != 200)
				{
					$renewable = false;
				}						
				if ($result['is_renewable'])
				{
					$renewable = true;
				}
				
				if ($renewable == false)
				{
					$error = 'Domain is not renewable. Reason : ' . $result['response_text'];
					$ordertype = 'Error';
				}
				else $ordertype = 'Renew';				
				
			}
			else
			{
				$ordertype = 'Transfer';		
			}
		}  // end gtld domains
		
		
		$cartdb = &new CartDB($this->db);
		$cart_id = $request['cart_id'];
		if (!$session->get('cart'))
		{
			$cart = &new Cart();
		}
		else 
		{
			$cart = $cartdb->getCart($cart_id);
		}		
		$dom = strtolower($request['domain_name'] . "." . $request['domain_ext']);
		$cart->addItem($dom, $ordertype, $period, $price, $password, $error, $type);
		
		if ($cart_id == '')
		{
			$cart_id = $cartdb->insertCart($cart);
		}
		else 
		{
			$cartdb->updateCart($cart_id, $cart);
		}		
		$session->set('cart_id', $cart_id);				
	
		echo "location.replace('" . SITE_PATH . "/index.php?view=domain_list');";
	}
	
	function checkRegisterDomain(&$request, &$session)
	{
		$domain_name = $request['domain_name'];
		$domain_ext = $request['domain_ext'];	
		
		if ($request['add'] != 'Y')
		{
			$session->del('cart');
		}
		
		$dom = strtolower($request['domain_name'] . "." . $request['domain_ext']);

		$domain =  &new Domain($domain_name, $domain_ext);
		if (!$domain->isValid())
		{
			echo "alert('The domain name you entered is not valid.');";
			exit;
		}
		if (!$domain->isAvailable())
		{
			echo "alert('The domain name you entered is not available.');";
			exit;
		}		
		
		$cartdb = &new CartDB($this->db);
		$cart_id = $request['cart_id'];
		if (!$session->get('cart'))
		{
			$cart = &new Cart();
		}
		else 
		{
			$cart = $cartdb->getCart($cart_id);
		}

		if ($domain_ext == 'id.au')
		{
			if (($cart->findExtension('com.au'))||($cart->findExtension('net.au'))||($cart->findExtension('org.au'))||($cart->findExtension('asn.au')))
			{
				echo "alert('You cannot register a " . $domain_ext . " domain name together with a com.au, net.au, org.au or asn.au domain name.');";
				exit;			
			}
		}			
		else if (($domain_ext == 'org.au')||($domain_ext == 'asn.au'))
		{
			if (($cart->findExtension('com.au'))||($cart->findExtension('net.au'))||($cart->findExtension('id.au')))
			{
				echo "alert('You cannot register a " . $domain_ext . " domain name together with a com.au, net.au or id.au domain name.');";
				exit;			
			}
		}		
		else if (($domain_ext == 'com.au')||($domain_ext == 'net.au'))
		{
			if (($cart->findExtension('org.au'))||($cart->findExtension('asn.au'))||($cart->findExtension('id.au')))
			{
				echo "alert('You cannot register a " . $domain_ext . " domain name together with a org.au, asn.au or id.au domain name.');";
				exit;			
			}			
		}

		global $domain_cost_table;
		$currency = &new Currency();
		$error = '';
		$password = '';
		
		if (($domain_ext == 'com.au') || ($domain_ext == 'net.au') || ($domain_ext == 'org.au') || ($domain_ext == 'id.au') || ($domain_ext == 'asn.au'))
		{
			// au domains
			$period = 2;
			$type = "AU";
			$price = $currency->toDollars($domain_cost_table[$domain_ext] * $period);
			$ordertype = 'Register';
		} // end au domains
		else 
		{  // gtld domains
			$period = 1;
			$type = "GTLD";
			$price = $currency->toDollars($domain_cost_table[$domain_ext] * $period);
			$ordertype = 'Register';
		}  // end gtld domains
		
		$dom = strtolower($request['domain_name'] . "." . $request['domain_ext']);
		$cart->addItem($dom, $ordertype, $period, $price, $password, 'checked', $type);
		
		if (($domain_ext == 'com.au')||($domain_ext == 'net'))
		{
			$exts = array('net.au');			
		}
		else if (($domain_ext == 'net.au')||($domain_ext == 'com'))
		{
			$exts = array('com.au');			
		}		
		else if (($domain_ext == 'org.au')||($domain_ext == 'org'))
		{
			$exts = array('org.au', 'org');			
		}		
		else if ($domain_ext == 'id.au')
		{
			$exts = array('info');			
		}						
		else if ($domain_ext == 'net.au')
		{
			$exts = array('com.au');			
		}				
		if ($exts)
		{
			foreach ($exts as $ext)
			{
				if ($domain_ext != $ext)
				{
					$domain =  &new Domain($domain_name, $ext);				
					if ($domain->isAvailable())
					{
						if (($ext == 'com.au') || ($ext == 'net.au') || ($ext == 'org.au') || ($ext == 'id.au') || ($ext == 'asn.au'))
						{
							// au domains
							$period = 2;
							$type = "AU";
							$price = $currency->toDollars($domain_cost_table[$domain_ext] * $period);
							$ordertype = 'Register';
						} // end au domains
						else 
						{  // gtld domains
							$period = 1;
							$type = "GTLD";
							$price = $currency->toDollars($domain_cost_table[$domain_ext] * $period);
							$ordertype = 'Register';
						}  // end gtld domains
						$dom = strtolower($domain_name . "." . $ext);
						$cart->addItem($dom, $ordertype, $period, $price, $password, $error, $type);					
					}						
				}
			}
		}
		if ($cart_id == '')
		{
			$cart_id = $cartdb->insertCart($cart);
		}
		else 
		{
			$cartdb->updateCart($cart_id, $cart);
		}
		$session->set('cart_id', $cart_id);		
	
		echo "location.replace('" . SITE_PATH . "/index.php?view=register_domain_list');";
	}	
	
	function showDomainList(&$request, &$session)
	{
	
		$cart_id = $session->get('cart_id');
		$cartdb = &new CartDB($this->db);
		$cart_items = $cartdb->getCart($cart_id);
		
		if (!$cart_items)
		{
			$this->web_page->display('no_cookies.tpl');
			exit;
		}		
		
		$periods_au = '<option value="2">2 years</option>';
		$this->web_page->assign('PERIODS_AU', $periods_au);		
		
		for ($i = 2; $i <= 8; $i++)
		{
			$periods_renew[] = $i;
		}
		$this->web_page->assign('PERIODS_RENEW', $periods_renew);	
		
		$periods = '<option value="1">1 year</option>';

		$this->web_page->assign('AFFILIATE', $session->get('affiliate'));			
		
		$this->web_page->assign('PERIODS', $periods);	
		$this->web_page->assign('CART_ID', $cart_id);			
		$this->web_page->assign('ITEMS', $cart_items->items);	
		$this->web_page->display('domain_list.tpl');
	}		
	
	function showRegisterDomainList(&$request, &$session)
	{
		$cart_id = $session->get('cart_id');
		$cartdb = &new CartDB($this->db);
		$cart_items = $cartdb->getCart($cart_id);
		
		if (!$cart_items)
		{
			$this->web_page->display('no_cookies.tpl');
			exit;
		}
				
		
		$periods_au = '<option value="2">2 years</option>';
		$this->web_page->assign('PERIODS_AU', $periods_au);		
		
		for ($i = 2; $i <= 8; $i++)
		{
			$periods_renew[] = $i;
		}
		
		$this->web_page->assign('AFFILIATE', $session->get('affiliate'));			
		
		$this->web_page->assign('PERIODS', $periods_renew);	
		$this->web_page->assign('CART_ID', $cart_id);	
		$this->web_page->assign('ITEMS', $cart_items->items);	
		$this->web_page->display('register_domain_list.tpl');
	}				

	function showContactDetailsForm(&$request, &$session)
	{
	
		$cart_id = $this->cart_id;
		if ($cart_id == '')
		{
			$cart_id = $request['cart_id'];
		}
		$cartdb = &new CartDB($this->db);
		$cart = $cartdb->getCart($cart_id);		
		
		$au = 'N';
		
		foreach ($cart->items as $item)
		{
			if ($item->getType() == 'AU')
			{
				$au = 'Y';
			}
		}	 	
		
		$affiliate = $session->get('affiliate');
		$this->web_page->assign('AFFILIATE', $affiliate);		
		
		$session->set('au', $au);						
		
		$this->web_page->assign('CART', $cart->items);		
		$this->web_page->assign('CART_ID', $cart_id);				
		$this->web_page->assign('AU', $session->get('au'));				
		
		$total_price = $session->get('total_price');
		$this->web_page->assign('TOTAL', sprintf("%0.2f", $total_price));	

		$reg_firstname = $session->get('reg_firstname');
		$reg_lastname = $session->get('reg_lastname');
		$reg_orgname = $session->get('reg_orgname');
		$reg_address1 = $session->get('reg_address1');
		$reg_address2 = $session->get('reg_address2');
		$reg_city = $session->get('reg_city');
		$reg_state = $session->get('reg_state');
		$reg_postcode = $session->get('reg_postcode');
		$reg_country = $session->get('reg_country');
		$reg_email = $session->get('reg_email');
		$reg_phone = $session->get('reg_phone');
		$reg_fax = $session->get('reg_fax');

		$this->web_page->assign('REG_FIRSTNAME', $reg_firstname);
		$this->web_page->assign('REG_LASTNAME', $reg_lastname);
		$this->web_page->assign('REG_ORGNAME', $reg_orgname);
		$this->web_page->assign('REG_ADDRESS1', $reg_address1);
		$this->web_page->assign('REG_ADDRESS2', $reg_address2);
		$this->web_page->assign('REG_CITY', $reg_city);
		$this->web_page->assign('REG_STATE', $reg_state);
		$this->web_page->assign('REG_POSTCODE', $reg_postcode);
		$this->web_page->assign('REG_COUNTRY', $reg_country);
		$this->web_page->assign('REG_EMAIL', $reg_email);
		$this->web_page->assign('REG_PHONE', $reg_phone);
		$this->web_page->assign('REG_FAX', $reg_fax);
		
		$tech_same = $session->get('tech_same');
		$this->web_page->assign('TECH_SAME', $tech_same);

		if ($tech_same == '')
		{
			if ($session->get('not_first') == '')
			{
				$session->set('not_first', 1);
			}
			else
			{
				$this->web_page->assign('TECH_SAME', 'no');
			}
			
			$tech_firstname = $session->get('tech_firstname');
			$tech_lastname = $session->get('tech_lastname');
			$tech_orgname = $session->get('tech_orgname');
			$tech_address1 = $session->get('tech_address1');
			$tech_address2 = $session->get('tech_address2');
			$tech_city = $session->get('tech_city');
			$tech_state = $session->get('tech_state');
			$tech_postcode = $session->get('tech_postcode');
			$tech_country = $session->get('tech_country');
			$tech_email = $session->get('tech_email');
			$tech_phone = $session->get('tech_phone');
			$tech_fax = $session->get('tech_fax');
	
			$this->web_page->assign('TECH_FIRSTNAME', $tech_firstname);
			$this->web_page->assign('TECH_LASTNAME', $tech_lastname);
			$this->web_page->assign('TECH_ORGNAME', $tech_orgname);
			$this->web_page->assign('TECH_ADDRESS1', $tech_address1);
			$this->web_page->assign('TECH_ADDRESS2', $tech_address2);
			$this->web_page->assign('TECH_CITY', $tech_city);
			$this->web_page->assign('TECH_STATE', $tech_state);
			$this->web_page->assign('TECH_POSTCODE', $tech_postcode);
			$this->web_page->assign('TECH_COUNTRY', $tech_country);
			$this->web_page->assign('TECH_EMAIL', $tech_email);
			$this->web_page->assign('TECH_PHONE', $tech_phone);
			$this->web_page->assign('TECH_FAX', $tech_fax);
		}
		
		$billing_same = $session->get('billing_same');
		$this->web_page->assign('BILLING_SAME', $billing_same);

		if ($billing_same == '')
		{
			if ($session->get('not_first1') == '')
			{
				$session->set('not_first1', 1);
			}
			else
			{
				$this->web_page->assign('BILLING_SAME', 'no');
			}
			
			$billing_firstname = $session->get('billing_firstname');
			$billing_lastname = $session->get('billing_lastname');
			$billing_orgname = $session->get('billing_orgname');
			$billing_address1 = $session->get('billing_address1');
			$billing_address2 = $session->get('billing_address2');
			$billing_city = $session->get('billing_city');
			$billing_state = $session->get('billing_state');
			$billing_postcode = $session->get('billing_postcode');
			$billing_country = $session->get('billing_country');
			$billing_email = $session->get('billing_email');
			$billing_phone = $session->get('billing_phone');
			$billing_fax = $session->get('billing_fax');
	
			$this->web_page->assign('BILLING_FIRSTNAME', $billing_firstname);
			$this->web_page->assign('BILLING_LASTNAME', $billing_lastname);
			$this->web_page->assign('BILLING_ORGNAME', $billing_orgname);
			$this->web_page->assign('BILLING_ADDRESS1', $billing_address1);
			$this->web_page->assign('BILLING_ADDRESS2', $billing_address2);
			$this->web_page->assign('BILLING_CITY', $billing_city);
			$this->web_page->assign('BILLING_STATE', $billing_state);
			$this->web_page->assign('BILLING_POSTCODE', $billing_postcode);
			$this->web_page->assign('BILLING_COUNTRY', $billing_country);
			$this->web_page->assign('BILLING_EMAIL', $billing_email);
			$this->web_page->assign('BILLING_PHONE', $billing_phone);
			$this->web_page->assign('BILLING_FAX', $billing_fax);
		}		
		
		$year = date("Y");
		for ($i = 0; $i < 10; $i++)
		{
			$year_options .= '<option value="' . $year . '">' . $year . '</option>';
			$year++;
		}
		$this->web_page->assign('YEARS', $year_options);
		$this->web_page->display('renew_domain.tpl');
	}	
	
	function processDetails(&$request, &$session, &$parser)
	{
		//get cart items
		
		$cart_id = $request['cart_id'];
		$cartdb = &new CartDB($this->db);
		$cart = $cartdb->getCart($cart_id);		
		
		$au = $session->get('au');
		
 		$total_cost = 0;
 		
		foreach ($cart->items as $item)
		{
			$total_cost += $item->getPrice();
		}		
		
		$affiliate = $session->get('affiliate');		
		
		$session->set('total_price', $total_cost);		
		
		$reg_firstname = $request['reg_firstname'];
		$reg_lastname = $request['reg_lastname'];
		$reg_orgname = $request['reg_orgname'];
		$reg_address1 = $request['reg_address1'];
		$reg_address2 = $request['reg_address2'];
		$reg_city = $request['reg_city'];
		$reg_state = $request['reg_state'];
		$reg_postcode = $request['reg_postcode'];
		$reg_country = $request['reg_country'];
		$reg_email = $request['reg_email'];
		$reg_phone = $request['reg_phone'];
		$reg_fax = $request['reg_fax'];
		
		$session->set('reg_firstname', $reg_firstname);
		$session->set('reg_lastname', $reg_lastname);
		$session->set('reg_orgname', $reg_orgname);
		$session->set('reg_address1', $reg_address1);
		$session->set('reg_address2', $reg_address2);
		$session->set('reg_city', $reg_city);
		$session->set('reg_state', $reg_state);
		$session->set('reg_postcode', $reg_postcode);
		$session->set('reg_country', $reg_country);
		$session->set('reg_email', $reg_email);
		$session->set('reg_phone', $reg_phone);
		$session->set('reg_fax', $reg_fax);
		
		if(!$parser->isString($reg_firstname,2,30))
		{
			$error = 'Invalid Registrant/Admin First Name. First Name should contain 2-30 characters.';
			$this->web_page->assign('ERROR', $error);
			$this->showContactDetailsForm($request, $session);
			exit;
		}
		else if(!$parser->isString($reg_lastname,2,30))
		{
			$error = 'Invalid Registrant/Admin Last Name. Last Name should contain 2-30 characters.';
			$this->web_page->assign('ERROR', $error);
			$this->showContactDetailsForm($request, $session);
			exit;
		}
		else if (!$parser->isString($reg_orgname, 2, 80))
		{
			$error = 'Invalid Registrant/Admin Organisation Name. Organisation Name has to be between 2 and 80 characters.';
			$this->web_page->assign('ERROR', $error);
			$this->showContactDetailsForm($request, $session, $parser);
			exit;
		}			
		else if(!$parser->isString($reg_address1,5,50))
		{
			$error = 'Invalid Registrant/Admin Address 1. Address 1 should contain 5-50 characters.';
			$this->web_page->assign('ERROR', $error);
			$this->showContactDetailsForm($request, $session);
			exit;
		}
		else if(!$parser->isString($reg_city,3,20))
		{
			$error = 'Invalid Registrant/Admin City. City should contain 3-20 characters.';
			$this->web_page->assign('ERROR', $error);
			$this->showContactDetailsForm($request, $session);
			exit;
		}
		else if(!$parser->isString($reg_state,2,50))
		{
			$error = 'Invalid Registrant/Admin State. State should contain 2-50 characters.';
			$this->web_page->assign('ERROR', $error);
			$this->showContactDetailsForm($request, $session);
			exit;
		}
		else if(!$parser->isString($reg_postcode, 4, 10, 'plain'))
		{
			$error = 'Invalid Registrant/Admin Postcode. Postcode should be 4-10 digits long.';
			$this->web_page->assign('ERROR', $error);
			$this->showContactDetailsForm($request, $session);
			exit;
		} 
		else if(!$parser->isString($reg_phone, 6, 20, 'phone'))
		{
			$error = 'Invalid Registrant/Admin Phone Number. (Eg. +61.386655432)';
			$this->web_page->assign('ERROR', $error);
			$this->showContactDetailsForm($request, $session);
			exit;
		}
		if (!empty($reg_fax))
		{
			if (!$parser->isString($reg_fax, 10, 20, 'phone'))
			{
				$error = 'Invalid Registrant/Admin Fax Number. (eg. +61.396200552)';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session, $parser);
				exit;
			}						
		}				
		if(!$parser->isString($reg_email,1,120,'email'))
		{
			$error = 'Invalid Registrant/Admin Email Address.';
			$this->web_page->assign('ERROR', $error);
			$this->showContactDetailsForm($request, $session);
			exit;
		}

		$tech_same = $request['tech_same'];
		$session->set('tech_same', $tech_same);
		
		if ($tech_same == '')
		{
			$tech_firstname = $request['tech_firstname'];
			$tech_lastname = $request['tech_lastname'];
			$tech_orgname = $request['tech_orgname'];
			$tech_address1 = $request['tech_address1'];
			$tech_address2 = $request['tech_address2'];
			$tech_city = $request['tech_city'];
			$tech_state = $request['tech_state'];
			$tech_postcode = $request['tech_postcode'];
			$tech_country = $request['tech_country'];
			$tech_email = $request['tech_email'];
			$tech_phone = $request['tech_phone'];
			$tech_fax = $request['tech_fax'];
			
			$session->set('tech_firstname', $tech_firstname);
			$session->set('tech_lastname', $tech_lastname);
			$session->set('tech_orgname', $tech_orgname);
			$session->set('tech_address1', $tech_address1);
			$session->set('tech_address2', $tech_address2);
			$session->set('tech_city', $tech_city);
			$session->set('tech_state', $tech_state);
			$session->set('tech_postcode', $tech_postcode);
			$session->set('tech_country', $tech_country);
			$session->set('tech_email', $tech_email);
			$session->set('tech_phone', $tech_phone);
			$session->set('tech_fax', $tech_fax);
			
			if(!$parser->isString($tech_firstname,2,30))
			{
				$error = 'Invalid Tech First Name. First Name should contain 2-30 characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session);
				exit;
			}
			else if(!$parser->isString($tech_lastname,2,30))
			{
				$error = 'Invalid Tech Last Name. Last Name should contain 2-30 characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session);
				exit;
			}
			else if (!$parser->isString($tech_orgname, 2, 80))
			{
				$error = 'Invalid Tech Organisation Name. Organisation Name has to be between 2 and 80 characters';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session, $parser);
				exit;
			}				
			else if(!$parser->isString($tech_address1,5,50))
			{
				$error = 'Invalid Tech Address 1. Address 1 should contain 5-50 characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session);
				exit;
			}
			else if(!$parser->isString($tech_city,3,20))
			{
				$error = 'Invalid Tech City. City should contain 3-20 characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session);
				exit;
			}
			else if(!$parser->isString($tech_state,2,50))
			{
				$error = 'Invalid Tech State. State should contain 2-50 characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session);
				exit;
			}
			else if(!$parser->isString($tech_postcode, 4, 10, 'plain'))
			{
				$error = 'Invalid Tech Postcode. Postcode should be 4-10 digits long.';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session);
				exit;
			} 
			else if(!$parser->isString($tech_phone, 6, 20, 'phone'))
			{
				$error = 'Invalid Tech Phone Number. (Eg. +61.386655432)';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session);
				exit;
			}
			if (!empty($tech_fax))
			{
				if (!$parser->isString($tech_fax, 10, 20, 'phone'))
				{
					$error = 'Invalid Tech Fax Number. (eg. +61.396200552)';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session, $parser);
					exit;
				}						
			}					
			if(!$parser->isString($tech_email,1,120,'email'))
			{
				$error = 'Invalid Tech Email Address.';
				$this->web_page->assign('ERROR', $error);
				$this->showContactDetailsForm($request, $session);
				exit;
			}
		}
		else 
		{
			$session->set('tech_first_name', $reg_firstname);
			$session->set('tech_last_name', $reg_lastname);
			$session->set('tech_org_name', $reg_orgname);
			$session->set('tech_address1', $reg_address1);
			$session->set('tech_address2', $reg_address2);
			$session->set('tech_city', $reg_city);
			$session->set('tech_state', $reg_state);
			$session->set('tech_postcode', $reg_postcode);
			$session->set('tech_country', $reg_country);
			$session->set('tech_email', $reg_email);
			$session->set('tech_phone', $reg_phone);
			$session->set('tech_fax', $reg_fax);
		}
		
		$billing_same = $request['billing_same'];
		$session->set('billing_same', $billing_same);
		
		if ($au == 'Y')
		{
			if ($billing_same == '')
			{
				$billing_firstname = $request['billing_firstname'];
				$billing_lastname = $request['billing_lastname'];
				$billing_orgname = $request['billing_orgname'];
				$billing_address1 = $request['billing_address1'];
				$billing_address2 = $request['billing_address2'];
				$billing_city = $request['billing_city'];
				$billing_state = $request['billing_state'];
				$billing_postcode = $request['billing_postcode'];
				$billing_country = $request['billing_country'];
				$billing_email = $request['billing_email'];
				$billing_phone = $request['billing_phone'];
				$billing_fax = $request['billing_fax'];
				
				$session->set('billing_firstname', $billing_firstname);
				$session->set('billing_lastname', $billing_lastname);
				$session->set('billing_orgname', $billing_orgname);
				$session->set('billing_address1', $billing_address1);
				$session->set('billing_address2', $billing_address2);
				$session->set('billing_city', $billing_city);
				$session->set('billing_state', $billing_state);
				$session->set('billing_postcode', $billing_postcode);
				$session->set('billing_country', $billing_country);
				$session->set('billing_email', $billing_email);
				$session->set('billing_phone', $billing_phone);
				$session->set('billing_fax', $billing_fax);
				
				if(!$parser->isString($billing_firstname,2,30))
				{
					$error = 'Invalid Billing First Name. First Name should contain 2-30 characters.';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session);
					exit;
				}
				else if(!$parser->isString($billing_lastname,2,30))
				{
					$error = 'Invalid Billing Last Name. Last Name should contain 2-30 characters.';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session);
					exit;
				}
				else if (!$parser->isString($billing_orgname, 2, 80))
				{
					$error = 'Invalid Billing Organisation Name. Organisation Name has to be between 2 and 80 characters';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session, $parser);
					exit;
				}				
				else if(!$parser->isString($billing_address1,5,50))
				{
					$error = 'Invalid Billing Address 1. Address 1 should contain 5-50 characters.';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session);
					exit;
				}
				else if(!$parser->isString($billing_city,3,20))
				{
					$error = 'Invalid Billing City. City should contain 3-20 characters.';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session);
					exit;
				}
				else if(!$parser->isString($billing_state,2,50))
				{
					$error = 'Invalid Billing State. State should contain 2-50 characters.';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session);
					exit;
				}
				else if(!$parser->isString($billing_postcode, 4, 10, 'plain'))
				{
					$error = 'Invalid Billing Postcode. Postcode should be 4-10 digits long.';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session);
					exit;
				} 
				else if(!$parser->isString($billing_phone, 6, 20, 'phone'))
				{
					$error = 'Invalid Billing Phone Number. (Eg. +61.386655432)';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session);
					exit;
				}
				if (!empty($billing_fax))
				{
					if (!$parser->isString($billing_fax, 10, 20, 'phone'))
					{
						$error = 'Invalid Billing Fax Number. (eg. +61.396200552)';
						$this->web_page->assign('ERROR', $error);
						$this->showContactDetailsForm($request, $session, $parser);
						exit;
					}						
				}						
				if(!$parser->isString($billing_email,1,120,'email'))
				{
					$error = 'Invalid Billing Email Address.';
					$this->web_page->assign('ERROR', $error);
					$this->showContactDetailsForm($request, $session);
					exit;
				}
			}
			else 
			{
				$session->set('billing_first_name', $reg_firstname);
				$session->set('billing_last_name', $reg_lastname);
				$session->set('billing_org_name', $reg_orgname);
				$session->set('billing_address1', $reg_address1);
				$session->set('billing_address2', $reg_address2);
				$session->set('billing_city', $reg_city);
				$session->set('billing_state', $reg_state);
				$session->set('billing_postcode', $reg_postcode);
				$session->set('billing_country', $reg_country);
				$session->set('billing_email', $reg_email);
				$session->set('billing_phone', $reg_phone);
				$session->set('billing_fax', $reg_fax);
			}		
		}
		

		// process items
		$SH = &new SmartyHostWebService(RESELLER_USER, RESELLER_PASS);
		
		$service_status = 'SUCCESS';					

		foreach ($cart->items as $item)
		{
			if ($item->getType() == 'AU')
			{
				// AU domains
				if ($item->getOrdertype() == 'Renew')
				{
					//AU Renewal
					
					$result = $SH->auDomainRenew($item->getDomain(), $item->getPeriod(), $affiliate);
					if ($result)
					{
						$result = xmlrpc_decode($result);		
						
						if ($result['response_code'] == 200)
						{

						}
						else if (($result['response_code'] == 400)||($result['response_code'] == 402))
						{
							$service_status = 'FAILED';
							$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
						}
						else
						{
							$service_status = 'FAILED';
							$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
						}										
					}
					else 
					{
						$service_status = 'FAILED';
						$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";				
					}	
							
				}// end AU renewal
				else 
				{
					// AU Transfer
					
					$reg_contact_set = new xmlrpcval(array(
			                                "first_name" => new xmlrpcval($reg_firstname, 'string'),
			                                "last_name" => new xmlrpcval($reg_lastname, 'string'),
			                                "org_name" => new xmlrpcval($reg_orgname, 'string'),
			                                "address1" => new xmlrpcval($reg_address1, 'string'),
			                                "address2" => new xmlrpcval($reg_address2, 'string'),
			                                "city" => new xmlrpcval($reg_city, 'string'),
			                                "state" => new xmlrpcval($reg_state, 'string'),
			                                "country" => new xmlrpcval($reg_country, 'string'),
			                                "postcode" => new xmlrpcval($reg_postcode, 'int'),
			                                "email" => new xmlrpcval($reg_email, 'string'),
			                                "phone" => new xmlrpcval($reg_phone, 'string'),
			                                "fax" => new xmlrpcval($reg_fax, 'string')
			                               ),
			                               'struct');				
			                               
					$billing_contact_set = new xmlrpcval(array(
			                    						"first_name" => new xmlrpcval($billing_firstname, 'string'),
			                                "last_name" => new xmlrpcval($billing_lastname, 'string'),
			                                "org_name" => new xmlrpcval($billing_orgname, 'string'),
			                                "address1" => new xmlrpcval($billing_address1, 'string'),
			                                "address2" => new xmlrpcval($billing_address2, 'string'),
			                                "city" => new xmlrpcval($billing_city, 'string'),
			                                "state" => new xmlrpcval($billing_state, 'string'),
			                                "country" => new xmlrpcval($billing_country, 'string'),
			                                "postcode" => new xmlrpcval($billing_postcode, 'int'),
			                                "email" => new xmlrpcval($billing_email, 'string'),
			                                "phone" => new xmlrpcval($billing_phone, 'string'),
			                                "fax" => new xmlrpcval($billing_fax, 'string')
			                               ),
			                               'struct');					   
			                               
			     $tech_contact_set = new xmlrpcval(array(
			                    						"first_name" => new xmlrpcval($tech_firstname, 'string'),
			                                "last_name" => new xmlrpcval($tech_lastname, 'string'),
			                                "org_name" => new xmlrpcval($tech_orgname, 'string'),
			                                "address1" => new xmlrpcval($tech_address1, 'string'),
			                                "address2" => new xmlrpcval($tech_address2, 'string'),
			                                "city" => new xmlrpcval($tech_city, 'string'),
			                                "state" => new xmlrpcval($tech_state, 'string'),
			                                "country" => new xmlrpcval($tech_country, 'string'),
			                                "postcode" => new xmlrpcval($tech_postcode, 'int'),
			                                "email" => new xmlrpcval($tech_email, 'string'),
			                                "phone" => new xmlrpcval($tech_phone, 'string'),
			                                "fax" => new xmlrpcval($tech_fax, 'string')
			                               ),
			                               'struct');	                            
			                               
			
					if ($tech_same == 'yes')
					{
						if ($billing_same == 'yes')
						{
							$result = $SH->auDomainTransfer($item->getDomain(), $item->getPassword(), $item->getPeriod(), $reg_contact_set, $reg_contact_set, $reg_contact_set, $session->get('affiliate'));					
						}		
						else 
						{
							$result = $SH->auDomainTransfer($item->getDomain(), $item->getPassword(), $item->getPeriod(), $reg_contact_set, $billing_contact_set, $reg_contact_set, $session->get('affiliate'));					
						}
					}
					else
					{
						if ($billing_same == 'yes')
						{
							$result = $SH->auDomainTransfer($item->getDomain(), $item->getPassword(), $item->getPeriod(), $reg_contact_set, $reg_contact_set, $tech_contact_set, $session->get('affiliate'));					
						}		
						else 
						{
							$result = $SH->auDomainTransfer($item->getDomain(), $item->getPassword(), $item->getPeriod(), $reg_contact_set, $billing_contact_set, $tech_contact_set, $session->get('affiliate'));					
						}
					}
					
					if ($result)
					{
						$result = xmlrpc_decode($result);		
						
						if (($result['response_code'] == 400)||($result['response_code'] == 402))
						{
							$service_status = 'FAILED';
							$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
						}
						else if ($result['response_code'] != 200)
						{
							$service_status = 'FAILED';
							$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
						}							
					}
					else 
					{
						$service_status = 'FAILED';
						$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
					}			
				}	// end AU transfer
			}
			else 
			{
				//GTLD domains
				if ($item->getOrdertype() == 'Renew')
				{
					//GTLD Renewal
					$result = $SH->gtldDomainRenew($item->getDomain(), $item->getPeriod(), $affiliate);
					if ($result)
					{
						$result = xmlrpc_decode($result);			
						
						if ($result['response_code'] == 200)
						{

						}
						else if (($result['response_code'] == 400)||($result['response_code'] == 402))
						{
							$service_status = 'FAILED';
							$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
						}
						else
						{
							$service_status = 'FAILED';
							$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
						}								
					}
					else 
					{
						$service_status = 'FAILED';
						$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
					}	
							
				}// end GTLD renewal
				else 
				{
					// GTLD Transfer
					
					$reg_contact_set = new xmlrpcval(array(
			                                "first_name" => new xmlrpcval($reg_firstname, 'string'),
			                                "last_name" => new xmlrpcval($reg_lastname, 'string'),
			                                "org_name" => new xmlrpcval($reg_orgname, 'string'),
			                                "address1" => new xmlrpcval($reg_address1, 'string'),
			                                "address2" => new xmlrpcval($reg_address2, 'string'),
			                                "city" => new xmlrpcval($reg_city, 'string'),
			                                "state" => new xmlrpcval($reg_state, 'string'),
			                                "country" => new xmlrpcval($reg_country, 'string'),
			                                "postcode" => new xmlrpcval($reg_postcode, 'int'),
			                                "email" => new xmlrpcval($reg_email, 'string'),
			                                "phone" => new xmlrpcval($reg_phone, 'string'),
			                                "fax" => new xmlrpcval($reg_fax, 'string')
			                               ),
			                               'struct');				
			                               
			
					if ($tech_same != '')
					{
						$result = $SH->gtldDomainTransfer($item->getDomain(), $item->getPeriod(), $reg_contact_set, $reg_contact_set, $session->get('affiliate'));					
					}
					else
					{
						$tech_contact_set = new xmlrpcval(array(
			                    						"first_name" => new xmlrpcval($tech_firstname, 'string'),
			                                "last_name" => new xmlrpcval($tech_lastname, 'string'),
			                                "org_name" => new xmlrpcval($tech_orgname, 'string'),
			                                "address1" => new xmlrpcval($tech_address1, 'string'),
			                                "address2" => new xmlrpcval($tech_address2, 'string'),
			                                "city" => new xmlrpcval($tech_city, 'string'),
			                                "state" => new xmlrpcval($tech_state, 'string'),
			                                "country" => new xmlrpcval($tech_country, 'string'),
			                                "postcode" => new xmlrpcval($tech_postcode, 'int'),
			                                "email" => new xmlrpcval($tech_email, 'string'),
			                                "phone" => new xmlrpcval($tech_phone, 'string'),
			                                "fax" => new xmlrpcval($tech_fax, 'string')
			                               ),
			                               'struct');	
			                               
			      $result = $SH->gtldDomainTransfer($item->getDomain(), $item->getPeriod(), $reg_contact_set, $tech_contact_set, $session->get('affiliate'));					
					}
					
					if ($result)
					{
						$result = xmlrpc_decode($result);			
						
						if (($result['response_code'] == 400)||($result['response_code'] == 402))
						{
							$service_status = 'FAILED';
							$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
						}
						else if ($result['response_code'] != 200)
						{
							$service_status = 'FAILED';
							$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
						}							
					}
					else 
					{
						$service_status = 'FAILED';
						$reason .= $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
					}			
			                               
				}	// end GTLD transfer
			}
		
		}// end foreach
		
 		
 		$total_cost = 0;
 		
		foreach ($cart->items as $item)
		{
			$domains .= $item->getOrdertype() . " : " . $item->getDomain() . " x " . $item->getPeriod() . " years = $" . $item->getPrice() ."\n";				
			$total_cost += $item->getPrice();
		}

		$currency = &new Currency();

		$this->web_page->assign('AMOUNT', $currency->getFormattedPrice($total_cost));
	
		$this->web_page->assign('DOMAINS', $domains);
		
		$message = $this->web_page->fetch('email_invoice_renew.tpl');
		
		$email = &new Email(INVOICE_SUBJECT, $message, EMAIL_COMPANY, INVOICE_EMAIL, 
											array($session->get('reg_email'), INVOICE_EMAIL), 0, 0, INVOICE_EMAIL);
		$email->send();
		
		$this->web_page->assign('DATE', date("F j, Y, g:i a"));	
		$this->web_page->assign('STATUS', $service_status);
		$this->web_page->assign('REASON', $reason);
		
		$this->web_page->assign('REG_FIRSTNAME', $reg_firstname);
		$this->web_page->assign('REG_LASTNAME', $reg_lastname);
		$this->web_page->assign('REG_ORGNAME', $reg_orgname);
		$this->web_page->assign('REG_ADDRESS1', $reg_address1);
		$this->web_page->assign('REG_ADDRESS2', $reg_address2);
		$this->web_page->assign('REG_CITY', $reg_city);
		$this->web_page->assign('REG_STATE', $reg_state);
		$this->web_page->assign('REG_COUNTRY', $reg_country);
		$this->web_page->assign('REG_POSTCODE', $reg_postcode);
		$this->web_page->assign('REG_EMAIL', $reg_email);
		$this->web_page->assign('REG_PHONE', $reg_phone);
		$this->web_page->assign('REG_FAX', $reg_fax);
                             
		$this->web_page->assign('TECH_FIRSTNAME', $tech_firstname);
		$this->web_page->assign('TECH_LASTNAME', $tech_lastname);
		$this->web_page->assign('TECH_ORGNAME', $tech_orgname);
		$this->web_page->assign('TECH_ADDRESS1', $tech_address1);
		$this->web_page->assign('TECH_ADDRESS2', $tech_address2);
		$this->web_page->assign('TECH_CITY', $tech_city);
		$this->web_page->assign('TECH_STATE', $tech_state);
		$this->web_page->assign('TECH_COUNTRY', $tech_country);
		$this->web_page->assign('TECH_POSTCODE', $tech_postcode);
		$this->web_page->assign('TECH_EMAIL', $tech_email);
		$this->web_page->assign('TECH_PHONE', $tech_phone);
		$this->web_page->assign('TECH_FAX', $tech_fax);
		
		$this->web_page->assign('BILLING_FIRSTNAME', $billing_firstname);
		$this->web_page->assign('BILLING_LASTNAME', $billing_lastname);
		$this->web_page->assign('BILLING_ORGNAME', $billing_orgname);
		$this->web_page->assign('BILLING_ADDRESS1', $billing_address1);
		$this->web_page->assign('BILLING_ADDRESS2', $billing_address2);
		$this->web_page->assign('BILLING_CITY', $billing_city);
		$this->web_page->assign('BILLING_STATE', $billing_state);
		$this->web_page->assign('BILLING_COUNTRY', $billing_country);
		$this->web_page->assign('BILLING_POSTCODE', $billing_postcode);
		$this->web_page->assign('BILLING_EMAIL', $billing_email);
		$this->web_page->assign('BILLING_PHONE', $billing_phone);
		$this->web_page->assign('BILLING_FAX', $billing_fax);		
		
		$this->web_page->assign('REG_NAME', $registrant_name);
		$this->web_page->assign('REG_ID_TYPE', $registrant_id_type);
		$this->web_page->assign('REG_ID_NO', $registrant_id_no);
		$this->web_page->assign('ELIG_ID_NO', $elig_id_no);
		$this->web_page->assign('ELIG_TYPE', $elig_type);
		$this->web_page->assign('ELIG_NAME', $elig_name);
		$this->web_page->assign('ELIG_ID_TYPE', $elig_id_type);
		$this->web_page->assign('ELIG_REASON', $elig_reason);

		$this->web_page->assign('USERNAME', $username);
		$this->web_page->assign('PASSWORD', $password1);
		
		$this->web_page->assign('CC_FNAME', $request['cardholder_fname']);
		$this->web_page->assign('CC_LNAME', $request['cardholder_lname']);
		$this->web_page->assign('CC_TYPE', $request['p_cc_type']);
		$this->web_page->assign('CC_NUM', $request['p_cc_num']);
		$this->web_page->assign('CC_EXPMON', $request['p_cc_exp_mon']);
		$this->web_page->assign('CC_EXPYEAR', $request['p_cc_exp_yr']);
		$this->web_page->assign('CHEQUE', $request['cheque']);		
		
		$this->web_page->assign('AFFILIATE', $session->get('affiliate'));					
                               
		$message = $this->web_page->fetch('order.tpl');
				
		$email = &new Email('Order Received', $message, EMAIL_COMPANY, ORDER_EMAIL, 
											array(ORDER_EMAIL), 0, 0, ORDER_EMAIL);
		$email->send();					
 		
		
		$order_amount = $session->get('total_price');
	
		$cartdb->removeCart($cart_id);
	
		$this->web_page->assign('PACKAGE_AMOUNT', $currency->getFormattedPrice($order_amount));
		$this->web_page->assign('INV_EMAIL', $session->get('reg_email'));
		
		$session->del('domain');
		$session->del('period');
		$session->del('affiliate');
		$session->del('password');

		$session->del('reg_firstname');
		$session->del('reg_lastname');
		$session->del('reg_orgname');
		$session->del('reg_address1');
		$session->del('reg_address2');
		$session->del('reg_city');
		$session->del('reg_state');
		$session->del('reg_postcode');
		$session->del('reg_country');
		$session->del('reg_phone');
		$session->del('reg_fax');
		$session->del('reg_email');
		
		$session->del('tech_same');		
		
		$session->del('tech_firstname');
		$session->del('tech_lastname');
		$session->del('tech_orgname');
		$session->del('tech_address1');
		$session->del('tech_address2');
		$session->del('tech_city');
		$session->del('tech_state');
		$session->del('tech_postcode');
		$session->del('tech_country');
		$session->del('tech_phone');
		$session->del('tech_fax');
		$session->del('tech_email');				
		
		$session->del('billing_same');		
		
		$session->del('billing_firstname');
		$session->del('billing_lastname');
		$session->del('billing_orgname');
		$session->del('billing_address1');
		$session->del('billing_address2');
		$session->del('billing_city');
		$session->del('billing_state');
		$session->del('billing_postcode');
		$session->del('billing_country');
		$session->del('billing_phone');
		$session->del('billing_fax');
		$session->del('billing_email');					
		
		$this->web_page->display('domain_thankyou.tpl');

	}	
	
	function processBilling(&$request, &$session, &$parser)
	{
		$affiliate = $session->get('affiliate');
		
		$username = $request['username'];
		$password1 = $request['password1'];
		$password2 = $request['password2'];
		
		$session->set('username', $username);
		$session->set('password', $password1);
		
		$brn_type = $request['brn_type'];
		$brn = $request['brn'];		
		
		$session->set('brn_type', $brn_type);
		$session->set('brn', $brn);			
	
		$reg_firstname = $request['reg_firstname'];
		$reg_lastname = $request['reg_lastname'];
		$reg_orgname = $request['reg_orgname'];
		$reg_address1 = $request['reg_address1'];
		$reg_address2 = $request['reg_address2'];
		$reg_city = $request['reg_city'];
		$reg_state = $request['reg_state'];
		$reg_postcode = $request['reg_postcode'];
		$reg_country = $request['reg_country'];
		$reg_phone = $request['reg_phone'];
		$reg_fax = $request['reg_fax'];
		$reg_email = $request['reg_email'];
		
		$session->set('reg_firstname', $reg_firstname);
		$session->set('reg_lastname', $reg_lastname);
		$session->set('reg_orgname', $reg_orgname);
		$session->set('reg_address1', $reg_address1);
		$session->set('reg_address2', $reg_address2);
		$session->set('reg_city', $reg_city);
		$session->set('reg_state', $reg_state);
		$session->set('reg_postcode', $reg_postcode);
		$session->set('reg_country', $reg_country);
		$session->set('reg_phone', $reg_phone);
		$session->set('reg_fax', $reg_fax);
		$session->set('reg_email', $reg_email);
		
		
		$tech_same = $request['tech_same'];
		$session->set('tech_same', $tech_same);		

		if ($tech_same == '')
		{
			if ($step == 3)
			{
				$session->set('tech_same', 'no');
			}
						
			$tech_firstname = $request['tech_firstname'];
			$tech_lastname = $request['tech_lastname'];
			$tech_orgname = $request['tech_orgname'];
			$tech_address1 = $request['tech_address1'];
			$tech_address2 = $request['tech_address2'];
			$tech_city = $request['tech_city'];
			$tech_state = $request['tech_state'];
			$tech_postcode = $request['tech_postcode'];
			$tech_country = $request['tech_country'];
			$tech_phone = $request['tech_phone'];
			$tech_fax = $request['tech_fax'];
			$tech_email = $request['tech_email'];
			
			$session->set('tech_firstname', $tech_firstname);
			$session->set('tech_lastname', $tech_lastname);
			$session->set('tech_orgname', $tech_orgname);
			$session->set('tech_address1', $tech_address1);
			$session->set('tech_address2', $tech_address2);
			$session->set('tech_city', $tech_city);
			$session->set('tech_state', $tech_state);
			$session->set('tech_postcode', $tech_postcode);
			$session->set('tech_country', $tech_country);
			$session->set('tech_phone', $tech_phone);
			$session->set('tech_fax', $tech_fax);
			$session->set('tech_email', $tech_email);			
		}
		
		$billing_same = $request['billing_same'];
		$session->set('billing_same', $billing_same);		

		if ($billing_same == '')
		{
			if ($step == 3)
			{
				$session->set('billing_same', 'no');
			}
			$billing_firstname = $request['billing_firstname'];
			$billing_lastname = $request['billing_lastname'];
			$billing_orgname = $request['billing_orgname'];
			$billing_address1 = $request['billing_address1'];
			$billing_address2 = $request['billing_address2'];
			$billing_city = $request['billing_city'];
			$billing_state = $request['billing_state'];
			$billing_postcode = $request['billing_postcode'];
			$billing_country = $request['billing_country'];
			$billing_phone = $request['billing_phone'];
			$billing_fax = $request['billing_fax'];
			$billing_email = $request['billing_email'];
			
			$session->set('billing_firstname', $billing_firstname);
			$session->set('billing_lastname', $billing_lastname);
			$session->set('billing_orgname', $billing_orgname);
			$session->set('billing_address1', $billing_address1);
			$session->set('billing_address2', $billing_address2);
			$session->set('billing_city', $billing_city);
			$session->set('billing_state', $billing_state);
			$session->set('billing_postcode', $billing_postcode);
			$session->set('billing_country', $billing_country);
			$session->set('billing_phone', $billing_phone);
			$session->set('billing_fax', $billing_fax);
			$session->set('billing_email', $billing_email);			
		}		
		
		$registrant_name = $request['registrant_name'];
		$registrant_id_type = $request['registrant_id_type'];
		$registrant_id_no = $request['registrant_id_no'];
		$elig_id_no = $request['elig_id_no'];
		$elig_type = $request['elig_type'];
		$elig_name = $request['elig_name'];
		$elig_id_type = $request['elig_id_type'];
		$elig_reason = $request['elig_reason'];								
				
		$session->set('registrant_name', $registrant_name);				
		$session->set('registrant_id_type', $registrant_id_type);
		$session->set('registrant_id_no', $registrant_id_no);
		$session->set('elig_id_no', $elig_id_no);
		$session->set('elig_type', $elig_type);
		$session->set('elig_name', $elig_name);
		$session->set('elig_id_type', $elig_id_type);
		$session->set('elig_reason', $elig_reason);										
		
		$session->set('cc_fname', $request['cardholder_fname']);
		$session->set('cc_lname', $request['cardholder_lname']);		
		
		$hosting_acc = $session->get('hosting');		
		$au = $session->get('au');
		

		$step = $request['step'];

		$SH = &new SmartyHostWebService(RESELLER_USER, RESELLER_PASS);
		
		if ($step == 2)
		{
			if ($au == 'Y')
			{		
				$request['step'] = 1;			
				if ($elig_type == '')
				{
					$error = 'An eligibilty type must be selected.';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}				
				
				$result == '';
				if (($elig_type == 'registered business')||($elig_type == 'sole trader')||($elig_type == 'partnership')||($elig_type == 'charity')||($elig_type == 'non-profit organisation'))
				{
					if ($elig_id_no == '')
					{
						$error = 'Please enter your ABN/ACN/BRN as your Eligibilty ID Number.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;				
					}			
					if ($elig_id_type == 'abn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'ABN');
					}
					else if ($elig_id_type == 'acn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'ACN');
					}								
					else if ($elig_id_type == 'vic bn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'BRN', 'VIC');
					}		
					else if ($elig_id_type == 'nsw bn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'BRN', 'NSW');
					}		
					else if ($elig_id_type == 'qld bn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'BRN', 'QLD');
					}		
					else if ($elig_id_type == 'nt bn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'BRN', 'NT');
					}		
					else if ($elig_id_type == 'wa bn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'BRN', 'WA');
					}		
					else if ($elig_id_type == 'sa bn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'BRN', 'SA');
					}		
					else if ($elig_id_type == 'tas bn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'BRN', 'TAS');
					}		
					else if ($elig_id_type == 'act bn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($elig_id_no, 'BRN', 'ACT');
					}		
	
					if ($result)
					{
						$result = xmlrpc_decode($result);			
					}
					else 
					{
						$error = 'ABN/ACN/BRN Number not recognised. Please Contact SmartyHost at 1300 721 465.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;						
					}				
								
				}
				else if ($elig_type == 'trademark owner')
				{
					$elig_id_type = 'tm';
					if ($elig_id_no == '')
					{
						$error = 'Please enter your trademark number as your Eligibilty ID Number.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;				
					}			
			
					if ($elig_name == '')
					{
						$error = 'Please enter your trademark name as your Eligibilty Name.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;				
					}							
				}
				else if ($elig_type == 'pending TM owner')
				{
					if ($elig_name == '')
					{
						$error = 'Please enter your trademark name as your Eligibilty Name.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;				
					}							
				}			
				else if ($elig_type == 'incorporated association')
				{
					if ($registrant_id_no == '')
					{
						$error = 'Please enter your Association Number as your Registrant ID Number.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;				
					}				
					$registrant_id_type = 'other';
				}			
				else if ($elig_type == 'political party')
				{
					if ($registrant_name == '')
					{
						$error = 'Please enter your Political Party Name as your Registrant Name.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;				
					}
					$result = $SH->auDomainIsValidBusinessNumber($registrant_name, 'PP');
					if ($result)
					{
						$result = xmlrpc_decode($result);			
					}
					else 
					{
						$error = 'Registrar server temporary unavailable. Please try again later.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;						
					}			
				}
				else if ($elig_type == 'trade union')
				{
					if ($registrant_name == '')
					{
						$error = 'Please enter your Trade Union Name as your Registrant Name.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;				
					}
					$result = $SH->auDomainIsValidBusinessNumber($registrant_name, 'TU');
					if ($result)
					{
						$result = xmlrpc_decode($result);			
					}
					else 
					{
						$error = 'Registrar server temporary unavailable. Please try again later.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;						
					}			
				}	
				else if (($elig_type != 'citizen/resident')&&($elig_type != 'club'))
				{
					if ($registrant_id_no == '')
					{
						$error = 'Please enter your ABN/ACN as your Registrant ID Number.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;				
					}		
								
					if ($registrant_id_type == 'abn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($registrant_id_no, 'ABN');
					}
					else if ($registrant_id_type == 'acn')
					{
						$result = $SH->auDomainIsValidBusinessNumber($registrant_id_no, 'ACN');
					}			
					if ($result)
					{
						$result = xmlrpc_decode($result);			
					}
					else 
					{
						$error = 'ABN/ACN Number not recognised. Please Contact SmartyHost at 1300 721 465.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;						
					}			
				}
				
				if ($result != '')
				{
					if (($result['response_code'] == 400)||($result['response_code'] == 402))
					{
						$error = 'Error Registering Domain: '. $result['response_text'];
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;			
					}
					else if ($result['response_code'] != 200)
					{
						$error = 'ABN/ACN/BRN Number not recognised. Please Contact SmartyHost at 1300 721 465.';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;			
					}				
				}
				if (($elig_type == 'registered business')&&($elig_id_type != 'abn')&&($elig_id_type != 'acn'))
				{
					$session->set('eligibility_name', $result['business_name']);
				}
				else if (($elig_type == 'political party')||($elig_type == 'trade union'))
				{
					$session->set('reg_orgname', $result['business_name']);
				}				
				else 
				{
					$session->set('reg_orgname', $result['business_name']);
				}
				if ($elig_type == 'citizen/resident')
				{
					$session->set('reg_orgname', $domain);
				}
				$request['step'] = 2;
				$this->showBillingForm($request, $session, $parser);
				exit;			
				
			}
		}
		
		$request['step'] = 2;
		
		if ($hosting_acc == 'Y')
		{
		 	if(!$parser->isString($username,3,12,'username'))		
		 	{
				$error = 'Invalid Username. Username should be 3-12 characters long (lowercase).';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session);
				exit;	 		
		 	}
		 	
			$result = $SH->hostingCheckUsername($username);

			if ($result)
			{
				$result = xmlrpc_decode($result);			
			}
			else 
			{
				$error = 'Error Registering Domain: Registry Temporary Unavailable.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;						
			}			

			if (($result['response_code'] == 400)||($result['response_code'] == 402))
			{
				$error = 'Error Registering Domain: '. $result['response_text'];
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;			
			}
			else if ($result['response_code'] != 200)
			{
				$error = 'Error Registering Domain: '. $result['response_text'];
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;			
			}		 	
			
			if(!$parser->isString($password1,6,10,'password'))
		 	{
				$error = 'Invalid Password. Password should be 3-20 characters long.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session);
				exit;	 		
		 	}	 	
			else if($password1 != $password2)
			{
				$error = 'The passwords that was entered do not match.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session);
				exit;
			}
		}
				
		if ($brn_type != '')
		{
			if ($brn == '')
			{
				$error = 'Please enter your BRN Number.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;				
			}			
			if ($brn_type == 'vic bn')
			{
				$result = $SH->auDomainIsValidBusinessNumber($brn, 'BRN', 'VIC');
			}		
			else if ($brn_type == 'nsw bn')
			{
				$result = $SH->auDomainIsValidBusinessNumber($brn, 'BRN', 'NSW');
			}		
			else if ($brn_type == 'qld bn')
			{
				$result = $SH->auDomainIsValidBusinessNumber($brn, 'BRN', 'QLD');
			}		
			else if ($brn_type == 'nt bn')
			{
				$result = $SH->auDomainIsValidBusinessNumber($brn, 'BRN', 'NT');
			}		
			else if ($brn_type == 'wa bn')
			{
				$result = $SH->auDomainIsValidBusinessNumber($brn, 'BRN', 'WA');
			}		
			else if ($brn_type == 'sa bn')
			{
				$result = $SH->auDomainIsValidBusinessNumber($brn, 'BRN', 'SA');
			}		
			else if ($brn_type == 'tas bn')
			{
				$result = $SH->auDomainIsValidBusinessNumber($brn, 'BRN', 'TAS');
			}		
			else if ($brn_type == 'act bn')
			{
				$result = $SH->auDomainIsValidBusinessNumber($brn, 'BRN', 'ACT');
			}		

			if ($result)
			{
				$result = xmlrpc_decode($result);			
			}
			else 
			{
				$error = 'BRN Number not recognised. Please Contact SmartyHost at 1300 721 465.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;						
			}			

			if (($result['response_code'] == 400)||($result['response_code'] == 402))
			{
				$error = 'Error Registering Domain: '. $result['response_text'];
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;			
			}
			else if ($result['response_code'] != 200)
			{
				$error = 'BRN Number not recognised. Please Contact SmartyHost at 1300 721 465.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;			
			}						
			
			$business_name = $result['business_name'];
		}		
		
		if (!$parser->isString($reg_firstname, 2, 40, 'plain'))
		{
			$error = 'Invalid Registrant First Name. Registrant first name has to be between 2 and 40 characters and contain no spaces or special characters.';
			$this->web_page->assign('ERROR', $error);
			$this->showBillingForm($request, $session, $parser);
			exit;
		}		
		else if (!$parser->isString($reg_lastname, 2, 40, 'plain'))
		{
			$error = 'Invalid Registrant Last Name. Registrant last name has to be between 2 and 40 characters and contain no spaces or special characters.';
			$this->web_page->assign('ERROR', $error);
			$this->showBillingForm($request, $session, $parser);
			exit;
		}				
		else if (!$parser->isString($reg_orgname, 2, 80))
		{
			$error = 'Invalid Registrant Organisation Name. Registrant Organisation name has to be between 2 and 80 characters';
			$this->web_page->assign('ERROR', $error);
			$this->showBillingForm($request, $session, $parser);
			exit;
		}				
		else if (!$parser->isString($reg_address1, 5, 80))
		{
			$error = 'Invalid Registrant Address. Registrant address has to be between 5 and 80 characters';
			$this->web_page->assign('ERROR', $error);
			$this->showBillingForm($request, $session, $parser);
			exit;
		}				
		else if (!$parser->isString($reg_city, 3, 40))
		{
			$error = 'Invalid Registrant City. Registrant city has to be between 3 and 40 characters';
			$this->web_page->assign('ERROR', $error);
			$this->showBillingForm($request, $session, $parser);
			exit;
		}				
		if ($AU == 'Y') 
		{
			if (!in_array(strtolower($reg_state), array('vic', 'nsw', 'wa', 'sa', 'tas', 'nt', 'act', 'qld')))
			{
				$error = 'Invalid Registrant State. Registrant state has to be \'VIC\', \'NSW\', \'WA\', \'SA\', \'TAS\', \'NT\', \'ACT\' or \'QLD\'';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if (!$parser->isNumber($reg_postcode, 4, 4))
			{
				$error = 'Invalid Registrant Postcode. Registrant postcode has to be 4 digits.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if ($reg_country !== 'AU')
			{
				$error = 'Invalid Registrant Country. Registrant country has to be \'AU\'.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}
			else if (!$parser->isString($reg_phone, 10, 20, 'phone'))
			{
				$error = 'Invalid Registrant Phone Number. (eg. +61.396200552)';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			if ($reg_fax)
			{
				if (!$parser->isString($reg_fax, 10, 20, 'phone'))
				{
					$error = 'Invalid Registrant Fax Number. (eg. +61.396200552)';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}						
			}			
		}	
		else
		{
			if (!$parser->isString($reg_state, 2, 30))
			{
				$error = 'Invalid Registrant State. Registrant state has to be between 2 and 30 characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if (!$parser->isString($reg_postcode, 4, 10,  'plain'))
			{
				$error = 'Invalid Registrant Postcode. Registrant postcode has to be between 4 and 10 characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if (!$parser->isString($reg_country, 2, 2))
			{
				$error = 'Invalid Registrant Country.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}		
			else if (!$parser->isString($reg_phone, 10, 20, 'phone2'))
			{
				$error = 'Invalid Registrant Phone Number. (eg. +61.396200552)';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}			
			if ($reg_fax)
			{
				if (!$parser->isString($reg_fax, 10, 20, 'phone2'))
				{
					$error = 'Invalid Registrant Fax Number. (eg. +61.396200552)';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}						
			}
		}			
		if (!$parser->isString($reg_email, 10, 60, 'email'))
		{
			$error = 'Invalid Registrant Email Address.';
			$this->web_page->assign('ERROR', $error);
			$this->showBillingForm($request, $session, $parser);
			exit;
		}				
		
		if ($tech_same == '')
		{
			if (!$parser->isString($tech_firstname, 2, 40, 'plain'))
			{
				$error = 'Invalid Tech First Name. Tech First name has to be between 2 and 40 characters and contain no spaces or special characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}		
			else if (!$parser->isString($tech_lastname, 2, 40, 'plain'))
			{
				$error = 'Invalid Tech Last Name. Tech Last name has to be between 2 and 40 characters and contain no spaces or special characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if (!$parser->isString($tech_orgname, 2, 80))
			{
				$error = 'Invalid Tech Organisation Name. Tech Organisation name has to be between 2 and 80 characters';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if (!$parser->isString($tech_address1, 5, 80))
			{
				$error = 'Invalid Tech Address. Tech Address has to be between 5 and 80 characters';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if (!$parser->isString($tech_city, 3, 40))
			{
				$error = 'Invalid Tech City. Tech City has to be between 3 and 40 characters';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if (!$parser->isString($tech_state, 2, 30))
			{
				$error = 'Invalid Tech State. Tech State has to be between 2 and 30 characters';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if (!$parser->isString($tech_postcode, 4, 10, 'plain'))
			{
				$error = 'Invalid Tech Postcode. Tech Postcode has to be between 4 and 10 characters.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
			else if (!$parser->isString($tech_country, 2, 2))
			{
				$error = 'Invalid Tech Country.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}					
			else if (!$parser->isString($tech_phone, 10, 20, 'phone'))
			{
				$error = 'Invalid Tech Phone Number. (eg. +61.396200552)';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}		
			if (!empty($tech_fax))
			{
				if (!$parser->isString($tech_fax, 10, 20, 'phone'))
				{
					$error = 'Invalid Tech Fax Number. (eg. +61.396200552)';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}						
			}
			if (!$parser->isString($tech_email, 10, 60, 'email'))
			{
				$error = 'Invalid Tech Email Address.';
				$this->web_page->assign('ERROR', $error);
				$this->showBillingForm($request, $session, $parser);
				exit;
			}				
		}
		if ($au == 'Y')
		{
			if ($billing_same == '')
			{
				if (!$parser->isString($billing_firstname, 2, 40, 'plain'))
				{
					$error = 'Invalid Billing First Name. Billing First name has to be between 2 and 40 characters and contain no spaces or special characters.';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}		
				else if (!$parser->isString($billing_lastname, 2, 40, 'plain'))
				{
					$error = 'Invalid Billing Last Name. Billing Last name has to be between 2 and 40 characters and contain no spaces or special characters.';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}				
				else if (!$parser->isString($billing_orgname, 2, 80))
				{
					$error = 'Invalid Billing Organisation Name. Billing Organisation name has to be between 2 and 80 characters';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}				
				else if (!$parser->isString($billing_address1, 5, 80))
				{
					$error = 'Invalid Billing Address. Billing Address has to be between 5 and 80 characters';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}				
				else if (!$parser->isString($billing_city, 3, 40))
				{
					$error = 'Invalid Billing City. Billing City has to be between 3 and 40 characters';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}				
				else if (!$parser->isString($billing_state, 2, 30))
				{
					$error = 'Invalid Billing State. Billing State has to be between 2 and 30 characters';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}				
				else if (!$parser->isString($billing_postcode, 4, 10, 'plain'))
				{
					$error = 'Invalid Billing Postcode. Billing Postcode has to be between 4 and 10 characters.';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}				
				else if (!$parser->isString($billing_country, 2, 2))
				{
					$error = 'Invalid Billing Country.';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}					
				else if (!$parser->isString($billing_phone, 10, 20, 'phone'))
				{
					$error = 'Invalid Billing Phone Number. (eg. +61.396200552)';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}				
				if (!empty($billing_fax))
				{
					if (!$parser->isString($billing_fax, 10, 20, 'phone'))
					{
						$error = 'Invalid Billing Fax Number. (eg. +61.396200552)';
						$this->web_page->assign('ERROR', $error);
						$this->showBillingForm($request, $session, $parser);
						exit;
					}						
				}				
				else if (!$parser->isString($billing_email, 10, 60, 'email'))
				{
					$error = 'Invalid Billing Email Address.';
					$this->web_page->assign('ERROR', $error);
					$this->showBillingForm($request, $session, $parser);
					exit;
				}				
			}
		}

		
		$currency = &new Currency();

		// register domain
    $registrant_name = $reg_orgname;		
    if ($elig_type == 'citizen/resident')
    {
    	$registrant_name = $reg_firstname . ' ' . $reg_lastname;
    	$reg_orgname = $registrant_name;
    	$elig_id_type = '';
			$elig_id_no = '';   
			$elig_name = ''; 
			$registrant_id_no = '';   
			$registrant_id_type = ''; 	    	
    }
    else if (($elig_type == 'registered business')||($elig_type == 'charity')||($elig_type == 'sole trader')||($elig_type == 'partnership')||($elig_type == 'non-profit organisation'))
    {    
    	if (($elig_id_type == 'abn')||($elig_id_type == 'acn'))
    	{
    		$registrant_id_type = $elig_id_type;
    		$registrant_id_no = $elig_id_no;
	    	$elig_id_type = '';
				$elig_id_no = '';   
				$elig_name = ''; 			
    		
	    	if ($brn_type != '')
		    {
		    	$elig_name = $business_name;
		    	$registrant_name = $reg_firstname . ' ' . $reg_lastname;
		    	$elig_id_type = $brn_type;
					$elig_id_no = $brn;
		    }
		  }
		  else
		  {
		  	$registrant_name = $reg_firstname . ' ' . $reg_lastname;
    		$registrant_id_type = '';
    		$registrant_id_no = '';		
    		$elig_name = $business_name;  	
		  }
  	}
    else if ($elig_type == 'trademark owner')
    {
    	$elig_id_type = 'tm';
			$registrant_id_no = '';   
			$registrant_id_type = ''; 	
    }		
    else if ($elig_type == 'pending TM owner')
    {
    	$elig_id_type = '';
			$registrant_id_no = '';   
			$elig_id_no = '';  
			$registrant_id_type = ''; 	
    }	
    else if ($elig_type == 'club')
    {
    	$elig_id_type = '';
			$elig_id_no = '';   
			$elig_name = $registrant_name; 
			$registrant_id_no = '';   
			$registrant_id_type = ''; 	
    }	
    else if ($elig_type == 'incorporated association')
    {
    	$elig_id_type = '';
			$elig_id_no = '';   
			$elig_name = ''; 
			$registrant_id_type = 'other'; 	
    }		    	
    else if (($elig_type == 'trade union')||($elig_type == 'political party'))
    {
    	$elig_id_type = '';
			$elig_id_no = '';   
			$elig_name = ''; 
			$registrant_id_no = '';   
			$registrant_id_type = ''; 	
    }		    
    else 
    {
    	$elig_id_type = '';
			$elig_id_no = '';   
			$elig_name = ''; 	
    }		

		
		$reg_contact_set = new xmlrpcval(array(
                                "first_name" => new xmlrpcval($reg_firstname, 'string'),
                                "last_name" => new xmlrpcval($reg_lastname, 'string'),
                                "org_name" => new xmlrpcval($reg_orgname, 'string'),
                                "address1" => new xmlrpcval($reg_address1, 'string'),
                                "address2" => new xmlrpcval($reg_address2, 'string'),
                                "city" => new xmlrpcval($reg_city, 'string'),
                                "state" => new xmlrpcval($reg_state, 'string'),
                                "country" => new xmlrpcval($reg_country, 'string'),
                                "postcode" => new xmlrpcval($reg_postcode, 'int'),
                                "email" => new xmlrpcval($reg_email, 'string'),
                                "phone" => new xmlrpcval($reg_phone, 'string'),
                                "fax" => new xmlrpcval($reg_fax, 'string')
                               ),
                               'struct');			
                               

                               
		$au_exts = new xmlrpcval(array(
                              "registrant_name" => new xmlrpcval($registrant_name, 'string'),
                              "registrant_id_type" => new xmlrpcval($registrant_id_type, 'string'),
                              "registrant_id_no" => new xmlrpcval($registrant_id_no, 'string'),		                                    
                              "elig_id_no" => new xmlrpcval($elig_id_no, 'string'),
                              "elig_type" => new xmlrpcval($elig_type, 'string'),
                              "elig_name" => new xmlrpcval($elig_name, 'string'),
                              "elig_id_type" => new xmlrpcval($elig_id_type, 'string'),
                              "elig_reason" => new xmlrpcval($elig_reason, 'string')
                             ),
                             'struct');		  
                             
		$tech_contact_set = new xmlrpcval(array(
                    						"first_name" => new xmlrpcval($tech_firstname, 'string'),
                                "last_name" => new xmlrpcval($tech_lastname, 'string'),
                                "org_name" => new xmlrpcval($tech_orgname, 'string'),
                                "address1" => new xmlrpcval($tech_address1, 'string'),
                                "address2" => new xmlrpcval($tech_address2, 'string'),
                                "city" => new xmlrpcval($tech_city, 'string'),
                                "state" => new xmlrpcval($tech_state, 'string'),
                                "country" => new xmlrpcval($tech_country, 'string'),
                                "postcode" => new xmlrpcval($tech_postcode, 'int'),
                                "email" => new xmlrpcval($tech_email, 'string'),
                                "phone" => new xmlrpcval($tech_phone, 'string'),
                                "fax" => new xmlrpcval($tech_fax, 'string')
                               ),
                               'struct');	  
                               
		$billing_contact_set = new xmlrpcval(array(
                    						"first_name" => new xmlrpcval($billing_firstname, 'string'),
                                "last_name" => new xmlrpcval($billing_lastname, 'string'),
                                "org_name" => new xmlrpcval($billing_orgname, 'string'),
                                "address1" => new xmlrpcval($billing_address1, 'string'),
                                "address2" => new xmlrpcval($billing_address2, 'string'),
                                "city" => new xmlrpcval($billing_city, 'string'),
                                "state" => new xmlrpcval($billing_state, 'string'),
                                "country" => new xmlrpcval($billing_country, 'string'),
                                "postcode" => new xmlrpcval($billing_postcode, 'int'),
                                "email" => new xmlrpcval($billing_email, 'string'),
                                "phone" => new xmlrpcval($billing_phone, 'string'),
                                "fax" => new xmlrpcval($billing_fax, 'string')
                               ),
                               'struct');	 	                                                           			


		//get cart items
		
		$cart_id = $request['cart_id'];
		$cartdb = &new CartDB($this->db);
		$cart = $cartdb->getCart($cart_id);
							
	
		global $domain_hosting_package_names, $domain_hosting_package_costs;
		
		// process items

		$service_status = 'SUCCESS';						
		
		foreach ($cart->items as $item)
		{
			if ($item->getType() == 'AU')
			{
				// AU domains register
				if ($fraud_order == false)
				{

					if ($tech_same == 'yes')
					{
						if ($billing_same == 'yes')
						{
							$result = $SH->auDomainRegister($item->getDomain(), $item->getPeriod(), $reg_contact_set, $reg_contact_set, 1, $au_exts, $session->get('affiliate'), $reg_contact_set);					
						}
						else 
						{
							$result = $SH->auDomainRegister($item->getDomain(), $item->getPeriod(), $reg_contact_set, $billing_contact_set, 1, $au_exts, $session->get('affiliate'), $reg_contact_set);					
						}
					}
					else
					{
						if ($billing_same == 'yes')
						{
							$result = $SH->auDomainRegister($item->getDomain(), $item->getPeriod(), $reg_contact_set, $reg_contact_set, 1, $au_exts, $session->get('affiliate'), $tech_contact_set);					
						}
						else 
						{
							$result = $SH->auDomainRegister($item->getDomain(), $item->getPeriod(), $reg_contact_set, $billing_contact_set, 1, $au_exts, $session->get('affiliate'), $tech_contact_set);					
						}							
					}
					
					if ($result)
					{
						$result = xmlrpc_decode($result);			
					}
					else 
					{
						$service_status = 'FAILED';
						$reason .=  $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
					}			
			                               
					if ($result['response_code'] != 200)
					{
						$service_status = 'FAILED';
						$reason .=  $item->getDomain() . ' : ' . $result['response_text'] . "\n";		
					}

				}
			}	// end AU register
			else 
			{
				//GTLD domains register
				if ($fraud_order == false)
				{
					$service_status = 'ACTIVE';						
					
					if ($tech_same == 'yes')
					{
						$result = $SH->gtldDomainRegister($item->getDomain(), $item->getPeriod(), $reg_contact_set, 1, $session->get('affiliate'), $reg_contact_set);					
					}
					else
					{
			      $result = $SH->gtldDomainRegister($item->getDomain(), $item->getPeriod(), $reg_contact_set, 1, $session->get('affiliate'), $tech_contact_set);					
					}
			
					if ($result)
					{
						$result = xmlrpc_decode($result);			
					}
					else 
					{
						$service_status = 'FAILED';
						$reason .=  $item->getDomain() . ' : ' . $result['response_text'] . "\n";			
					}			
			                               
					if ($result['response_code'] != 200)
					{
						$service_status = 'FAILED';
						$reason .=  $item->getDomain() . ' : ' . $result['response_text'] . "\n";		
					}
				}
			}	// end GTLD transfer
			
			// add hosting service if exists
			$hosting = $item->getReference1();
			if ($hosting != '')
			{
				if (SMARTYHOST_HOSTING == 1)
				{
					// signup for SmartHost Hosting
					
					$result = $SH->hostingPurchase($item->getDomain(), $domain_hosting_package_names[$hosting], 1, $username, $password1);
					if ($result)
					{
						$result = xmlrpc_decode($result);			
					}
					else 
					{
						$service_status = 'FAILED';
						$reason .=  $item->getDomain() . ' ('. $domain_hosting_package_names[$hosting] . ') : ' . $result['response_text'] . "\n";			
					}			
			                               
					if ($result['response_code'] != 200)
					{
						$service_status = 'FAILED';
						$reason .=  $item->getDomain() . ' ('. $domain_hosting_package_names[$hosting] . ') : ' . $result['response_text'] . "\n";		
					}					
				}
			}				

		}// end foreach
		
 		$total_cost = 0;
 		
		foreach ($cart->items as $item)
		{
			$packages .= $item->getDomain() . " x " . $item->getPeriod() . " years = $" . $item->getPrice() ."\n";				
			if ($item->getReference1())
			{
				global $domain_hosting_package_names, $domain_hosting_package_costs;
				$packages .= " + " . $domain_hosting_package_names[$item->getReference1()] . " x 1 year = $" . $currency->toDollars($domain_hosting_package_costs[$item->getReference1()]) ."\n";				
				$total_cost += $currency->toDollars($domain_hosting_package_costs[$item->getReference1()]);
			}
			$total_cost += $item->getPrice();
		}

		$this->web_page->assign('AMOUNT', $currency->getFormattedPrice($total_cost));
		$this->web_page->assign('PACKAGES', $packages);
		
		$message = $this->web_page->fetch('email_invoice_registration.tpl');
		
		$email = &new Email(INVOICE_SUBJECT, $message, EMAIL_COMPANY, INVOICE_EMAIL, 
											array($session->get('reg_email'), INVOICE_EMAIL), 0, 0, INVOICE_EMAIL);
		$email->send();		

		$this->web_page->assign('DATE', date("F j, Y, g:i a"));	
		$this->web_page->assign('STATUS', $service_status);
		$this->web_page->assign('REASON', $reason);
		
		$this->web_page->assign('REG_FIRSTNAME', $reg_firstname);
		$this->web_page->assign('REG_LASTNAME', $reg_lastname);
		$this->web_page->assign('REG_ORGNAME', $reg_orgname);
		$this->web_page->assign('REG_ADDRESS1', $reg_address1);
		$this->web_page->assign('REG_ADDRESS2', $reg_address2);
		$this->web_page->assign('REG_CITY', $reg_city);
		$this->web_page->assign('REG_STATE', $reg_state);
		$this->web_page->assign('REG_COUNTRY', $reg_country);
		$this->web_page->assign('REG_POSTCODE', $reg_postcode);
		$this->web_page->assign('REG_EMAIL', $reg_email);
		$this->web_page->assign('REG_PHONE', $reg_phone);
		$this->web_page->assign('REG_FAX', $reg_fax);
                             
		$this->web_page->assign('TECH_FIRSTNAME', $tech_firstname);
		$this->web_page->assign('TECH_LASTNAME', $tech_lastname);
		$this->web_page->assign('TECH_ORGNAME', $tech_orgname);
		$this->web_page->assign('TECH_ADDRESS1', $tech_address1);
		$this->web_page->assign('TECH_ADDRESS2', $tech_address2);
		$this->web_page->assign('TECH_CITY', $tech_city);
		$this->web_page->assign('TECH_STATE', $tech_state);
		$this->web_page->assign('TECH_COUNTRY', $tech_country);
		$this->web_page->assign('TECH_POSTCODE', $tech_postcode);
		$this->web_page->assign('TECH_EMAIL', $tech_email);
		$this->web_page->assign('TECH_PHONE', $tech_phone);
		$this->web_page->assign('TECH_FAX', $tech_fax);
		
		$this->web_page->assign('BILLING_FIRSTNAME', $billing_firstname);
		$this->web_page->assign('BILLING_LASTNAME', $billing_lastname);
		$this->web_page->assign('BILLING_ORGNAME', $billing_orgname);
		$this->web_page->assign('BILLING_ADDRESS1', $billing_address1);
		$this->web_page->assign('BILLING_ADDRESS2', $billing_address2);
		$this->web_page->assign('BILLING_CITY', $billing_city);
		$this->web_page->assign('BILLING_STATE', $billing_state);
		$this->web_page->assign('BILLING_COUNTRY', $billing_country);
		$this->web_page->assign('BILLING_POSTCODE', $billing_postcode);
		$this->web_page->assign('BILLING_EMAIL', $billing_email);
		$this->web_page->assign('BILLING_PHONE', $billing_phone);
		$this->web_page->assign('BILLING_FAX', $billing_fax);		
		
		$this->web_page->assign('REG_NAME', $registrant_name);
		$this->web_page->assign('REG_ID_TYPE', $registrant_id_type);
		$this->web_page->assign('REG_ID_NO', $registrant_id_no);
		$this->web_page->assign('ELIG_ID_NO', $elig_id_no);
		$this->web_page->assign('ELIG_TYPE', $elig_type);
		$this->web_page->assign('ELIG_NAME', $elig_name);
		$this->web_page->assign('ELIG_ID_TYPE', $elig_id_type);
		$this->web_page->assign('ELIG_REASON', $elig_reason);
		
		$this->web_page->assign('CC_FNAME', $request['cardholder_fname']);
		$this->web_page->assign('CC_LNAME', $request['cardholder_lname']);
		$this->web_page->assign('CC_TYPE', $request['p_cc_type']);
		$this->web_page->assign('CC_NUM', $request['p_cc_num']);
		$this->web_page->assign('CC_EXPMON', $request['p_cc_exp_mon']);
		$this->web_page->assign('CC_EXPYEAR', $request['p_cc_exp_yr']);
		$this->web_page->assign('CHEQUE', $request['cheque']);

		$this->web_page->assign('USERNAME', $username);
		$this->web_page->assign('PASSWORD', $password1);
		
		$this->web_page->assign('AFFILIATE', $session->get('affiliate'));					
                               
		$message = $this->web_page->fetch('order.tpl');
				
		$email = &new Email('Order Received', $message, EMAIL_COMPANY, ORDER_EMAIL, 
											array(ORDER_EMAIL), 0, 0, ORDER_EMAIL);
		$email->send();				
		
		$order_amount = $session->get('total_price');

		$this->web_page->assign('PACKAGE_AMOUNT', $currency->getFormattedPrice($order_amount));
		$this->web_page->assign('INV_EMAIL', $session->get('reg_email'));		
	
		$cartdb->removeCart($cart_id);
		
		$session->del('registrant_name');
		$session->del('registrant_id_type');
		$session->del('registrant_id_no');
		$session->del('elig_id_no');
		$session->del('elig_type');
		$session->del('elig_name');
		$session->del('elig_id_type');
		$session->del('elig_reason');		
	
		$session->del('reg_firstname');
		$session->del('reg_lastname');
		$session->del('reg_orgname');
		$session->del('reg_address1');
		$session->del('reg_address2');
		$session->del('reg_city');
		$session->del('reg_state');
		$session->del('reg_postcode');
		$session->del('reg_country');
		$session->del('reg_phone');
		$session->del('reg_fax');
		$session->del('reg_email');
		
		$session->del('tech_same');		
		
		$session->del('tech_firstname');
		$session->del('tech_lastname');
		$session->del('tech_orgname');
		$session->del('tech_address1');
		$session->del('tech_address2');
		$session->del('tech_city');
		$session->del('tech_state');
		$session->del('tech_postcode');
		$session->del('tech_country');
		$session->del('tech_phone');
		$session->del('tech_fax');
		$session->del('tech_email');				
		
		$session->del('billing_same');		
		
		$session->del('billing_firstname');
		$session->del('billing_lastname');
		$session->del('billing_orgname');
		$session->del('billing_address1');
		$session->del('billing_address2');
		$session->del('billing_city');
		$session->del('billing_state');
		$session->del('billing_postcode');
		$session->del('billing_country');
		$session->del('billing_phone');
		$session->del('billing_fax');
		$session->del('billing_email');				

		$this->web_page->display('hosting_thankyou.tpl');
 		
	}	//end processBilling		
	
	function processDomains(&$request, &$session, &$parser)
	{
		$affiliate = $request['affiliate'];
		$session->set('affiliate', $affiliate);
		
		$cart_id = $request['cart_id'];
		$this->cart_id = $cart_id;
		$cartdb = &new CartDB($this->db);
		$cart_items = $cartdb->getCart($cart_id);
		global $domain_cost_table, $affiliate_discounts;
		$currency = &new Currency();

		//update details in cart
		$total_price = 0;
		$count = 0;
		foreach ($cart_items->items as $item)
		{
			$form_domain = str_replace(".", "_", $item->getDomain());
			list($domain_name, $domain_ext) = split("\.", $item->getDomain(), 2); 
			if ($request[$form_domain] == 'Y')
			{
				$count++;
				if ($item->type == 'AU')
				{
					$session->set('AU', 'Y');
				}				
				$period = $request["period_".$form_domain];
				$price = $currency->toDollars($domain_cost_table[$domain_ext] * $period);
				if ($affiliate)
				{
					if (array_key_exists($affiliate, $affiliate_discounts))
					{
						$price = round($price - ($price * $affiliate_discounts[$affiliate] / 100), 2);
					}
				}				
				$password_form = "password_".$form_domain;
				$password = $request[$password_form];
				$cart_items->updateItem($item->getDomain(), $item->getOrdertype(), $period, $price, $password, $item->getError(), $item->getType());
				$total_price += $price;
			}
			else
			{
				$cart_items->removeItem($item->getDomain());
			}
		}	

		//recheck to make sure passwords are filled.
		
	
		$total_price = 0;
		
		$SH = &new SmartyHostWebService(RESELLER_USER, RESELLER_PASS);
		

		foreach ($cart_items->items as $item)
		{
			$price = $item->getPrice();
			if (($item->ordertype == 'Transfer')&&($item->type == 'AU'))
			{
				if ($item->getPassword() == '')
				{
					$error = 'Password required for transfer of ' . $item->getDomain() . '. Please enter a password. To transfer a .au domain name, you require
	               the domain password. You should be able to retrieve this information
	               from the existing registrar or from the auDA website.
	               <a href="http://admin.auda.org.au" target="_blank" class="red"><b>Click here</b></a>
	               to retrieve your domain password from auDA.';
					$this->web_page->assign('ERROR', $error);		
					$this->showDomainList($request, $session);
					exit;
				}
				else 
				{
					$result = $SH->auDomainIsTransferable($item->getDomain(), $item->getPassword());			
					if ($result)
					{
						$result = xmlrpc_decode($result);			
					}
					else 
					{
						$checkpass = false;						
					}
					
					if ($result['response_code'] == 200)
					{
						$checkpass = true;
					}					
					else
					{
						$checkpass = false;
					}

					if($checkpass == true)
					{
							$period = $result['recommended_period'];
							$price = $currency->toDollars($domain_cost_table[$domain_ext] * $period);
							if ($affiliate)
							{
								if (array_key_exists($affiliate, $affiliate_discounts))
								{
									$price = round($price - ($price * $affiliate_discounts[$affiliate] / 100), 2);
								}
							}							
							$cart_items->updateItem($item->getDomain(), $item->getOrdertype(), $period, $price, $item->getPassword(), $item->getError(), $item->getType());					
					}
					else
					{
						if ($result['response_code'] == 500)
						{
							$error = 'Registry temporary unavailable';
						}
						else 
						{
							$error = $item->getDomain() . ': ' . $result['response_text'];
						}
						$this->web_page->assign('ERROR', $error);		
						$this->showDomainList($request, $session);
						exit;						
					} 
				}
			}
			if ($item->type != 'AU')
			{
				$result = $SH->gtldDomainIsTransferable($item->getDomain());			
				if ($result)
				{
					$result = xmlrpc_decode($result);			
				}
				else 
				{
					$is_transfer = false;						
				}
				
				if ($result['response_code'] == 200)
				{
					$is_transfer = true;
				}					
				else
				{
					$is_transfer = false;
				}				
				if($is_transfer == false)
				{
					if ($result['response_code'] == 500)
					{
						$error = 'Registry temporary unavailable';
					}
					else 
					{
						$error = $item->getDomain() . ': ' . $result['response_text'];
					}
					$this->web_page->assign('ERROR', $error);		
					$this->showDomainList($request, $session);
					exit;						
				} 				
			}
			$total_price += $price;
		}			
		
		$cartdb->updateCart($cart_id, $cart_items);		
		
		if ($count == 0)
		{
			$this->showDomainList(&$request, &$session);
			exit;
		}
		
		$session->set('total_price', $total_price);
		$session->set('tech_same', 'on');
				
		// go to contact details form
		
		$this->showContactDetailsForm($request, $session);
	}	
	
	function processRegistrations(&$request, &$session)
	{
		$affiliate = $request['affiliate'];
		$session->set('affiliate', $affiliate);
		
		$cart_id = $request['cart_id'];
		$this->cart_id = $cart_id;
		$cartdb = &new CartDB($this->db);
		$cart_items = $cartdb->getCart($cart_id);
		
		//$cart_items = $session->get('Cart');
		global $domain_cost_table, $affiliate_discounts;
		$currency = &new Currency();

		//update details in cart
		
		$count = 0;
		foreach ($cart_items->items as $item)
		{
			$form_domain = str_replace(".", "_", $item->getDomain());
			//print $form_domain;
			list($domain_name, $domain_ext) = split("\.", $item->getDomain(), 2); 
			if ($request[$form_domain] == 'Y')
			{
				$count++;
				$period = $request["period_".$form_domain];
				$price = $currency->toDollars($domain_cost_table[$domain_ext] * $period);
				if ($affiliate)
				{
					if (array_key_exists($affiliate, $affiliate_discounts))
					{
						$price = round($price - ($price * $affiliate_discounts[$affiliate] / 100), 2);
					}
				}
				$password_form = "password_".$form_domain;
				$password = $request[$password_form];
				$cart_items->updateItem($item->getDomain(), $item->getOrdertype(), $period, $price, $password, $item->getError(), $item->getType());
				$total_price += $price;
			}
			else
			{
				$cart_items->removeItem($item->getDomain());
			}
		}	
		
		$cartdb->updateCart($cart_id, $cart_items);
		
		if ($count == 0)
		{
			$this->showRegisterDomainList(&$request, &$session);
			exit;
		}		

		$session->set('total_price', $total_price);
		$session->set('tech_same', 'on');
	
		// go to contact details form
		
		$this->showHostingForm($request, $session);
	}		
	
	function showHostingForm(&$request, &$session)
	{
		//$cart = $session->get('Cart');
		
		$cart_id = $this->cart_id;
		$cartdb = &new CartDB($this->db);
		$cart = $cartdb->getCart($cart_id);
		
		foreach ($cart->items as $item)
		{
			$domain_options .= '<option value="' . $item->getDomain() . '">' . $item->getDomain() . '</option>';
		}
		
		global $domain_hosting_package_names, $domain_hosting_package_costs;
	
		$currency = &new Currency();
		foreach ($domain_hosting_package_names as $key => $value)
		{
			$hosting_options .= '<option value="' . $key . '">' . $value . ' - $' . $currency->toDollars($domain_hosting_package_costs[$key]) . '</option>';
			$this->web_page->assign(strtoupper($key) . '_NAME', $domain_hosting_package_names[$key]);					
			$this->web_page->assign(strtoupper($key) . '_COST', $currency->toDollars($domain_hosting_package_costs[$key]));					
		}
		
 	
		$total_price = 0;		
		
		foreach ($cart->items as $item)
		{
			$price = $item->getPrice();			
			$total_price += $price;
			if ($item->getReference1() != '')
			{
				$total_price += $currency->toDollars($domain_hosting_package_costs[$item->getReference1()]);
			}
		}	 	
		
		$session->set('total_price', $total_price);				
		
		$this->web_page->assign('CART', $cart->items);		
		$this->web_page->assign('CART_ID', $cart_id);				
		$this->web_page->assign('TOTAL', $total_price);				
		$this->web_page->assign('DOMAIN_OPTIONS', $domain_options);
		$this->web_page->assign('HOSTING_OPTIONS', $hosting_options);		
		$this->web_page->assign('ADDON_OPTIONS', $addon_options);				
		$this->web_page->assign('SPACE_OPTIONS', $space_options);						
		
		$this->web_page->display('hosting_options.tpl');
	}			
	
	function showBillingForm(&$request, &$session)
	{
		
		$cart_id = $this->cart_id;
		if ($cart_id == '')
		{
			$cart_id = $request['cart_id'];
		}
		$cartdb = &new CartDB($this->db);
		$cart = $cartdb->getCart($cart_id);		
		
		$total_price = $session->get('total_price');		
		
		global $domain_hosting_package_names, $domain_hosting_package_costs;

		$currency = &new Currency();
		
		foreach ($domain_hosting_package_names as $key => $value)
		{
			$this->web_page->assign(strtoupper($key) . '_NAME', $domain_hosting_package_names[$key]);					
			$this->web_page->assign(strtoupper($key) . '_COST', sprintf("%0.2f", $currency->toDollars($domain_hosting_package_costs[$key])));					
		}		

		$total_price = 0;		
		global $domain_hosting_package_costs, $domain_addon_costs;
		
		$hosting = 'N';
		$au = 'N';
		
		foreach ($cart->items as $item)
		{
			$price = $item->getPrice();			
			$total_price += $price;
			list ($domain_name, $domain_ext) = split("\.", $item->getDomain(), 2);
			if ($item->getType() == 'AU')
			{
				$au = 'Y';
				$au_ext = $domain_ext;
			}
			
			if ($item->getReference1() != '')
			{
				$hosting = 'Y';
				$total_price += $currency->toDollars($domain_hosting_package_costs[$item->getReference1()]);
				if ($item->getReference2() != '')
				{
					foreach ($item->getReference2() as $tool)
					{
						if (array_key_exists($tool, $domain_addon_costs))
						{
							$total_price += $currency->toDollars($domain_addon_costs[$tool]);
						}
						if (array_key_exists($tool, $domain_space_costs))
						{
							$total_price += $currency->toDollars($domain_space_costs[$tool]);
						}	
					}
				}				
			}
		}	 	
		
		$session->set('total_price', $total_price);				
		$session->set('hosting', $hosting);						
		$session->set('au', $au);								
		
		$this->web_page->assign('CART', $cart->items);		
		$this->web_page->assign('CART_ID', $cart_id);				
		$this->web_page->assign('TOTAL', sprintf("%0.2f", $total_price));			
		$this->web_page->assign('HOSTING', $hosting);					
		$this->web_page->assign('AU', $au);							
		$this->web_page->assign('DOMAIN_EXT', $au_ext);
		
		$step = $request['step'];
		
		if (!$step)				
		{
			if ($au == 'Y')
			{	
				$this->web_page->assign('STEP', 1);							
				$this->web_page->assign('NEXT_STEP', 2);							
			} 
			else 
			{
				$this->web_page->assign('STEP', 2);							
				$this->web_page->assign('NEXT_STEP', 3);				
			}
		}
		else
		{
			$this->web_page->assign('STEP', $step);							
			$this->web_page->assign('NEXT_STEP', $step + 1);				
		}
		
		$username = $session->get('username');		
		$this->web_page->assign('USERNAME', $username);		
		
		$affiliate = $session->get('affiliate');		
		$this->web_page->assign('AFFILIATE', $affiliate);		
		
		$reg_firstname = $session->get('reg_firstname');
		$reg_lastname = $session->get('reg_lastname');
		$reg_orgname = $session->get('reg_orgname');
		$reg_address1 = $session->get('reg_address1');
		$reg_address2 = $session->get('reg_address2');
		$reg_city = $session->get('reg_city');
		$reg_state = $session->get('reg_state');
		$reg_postcode = $session->get('reg_postcode');
		$reg_country = $session->get('reg_country');
		$reg_email = $session->get('reg_email');
		$reg_phone = $session->get('reg_phone');
		$reg_fax = $session->get('reg_fax');

		$this->web_page->assign('REG_FIRSTNAME', $reg_firstname);
		$this->web_page->assign('REG_LASTNAME', $reg_lastname);
		$this->web_page->assign('REG_ORGNAME', $reg_orgname);
		$this->web_page->assign('REG_ADDRESS1', $reg_address1);
		$this->web_page->assign('REG_ADDRESS2', $reg_address2);
		$this->web_page->assign('REG_CITY', $reg_city);
		$this->web_page->assign('REG_STATE', $reg_state);
		$this->web_page->assign('REG_POSTCODE', $reg_postcode);
		$this->web_page->assign('REG_COUNTRY', $reg_country);
		$this->web_page->assign('REG_EMAIL', $reg_email);
		$this->web_page->assign('REG_PHONE', $reg_phone);
		$this->web_page->assign('REG_FAX', $reg_fax);
		
		$this->web_page->assign('CC_FNAME', $session->get('cc_fname'));
		$this->web_page->assign('CC_LNAME', $session->get('cc_lname'));		
		$this->web_page->assign('CC_TYPE', $session->get('cc_type'));				
		
		$this->web_page->assign('REGISTRANT_NAME', $session->get('registrant_name'));		
		$this->web_page->assign('REGISTRANT_ID_NO', $session->get('registrant_id_no'));		
		$this->web_page->assign('REGISTRANT_ID_TYPE', $session->get('registrant_id_type'));		
		$this->web_page->assign('ELIG_ID_NO', $session->get('elig_id_no'));		
		$this->web_page->assign('ELIG_TYPE', $session->get('elig_type'));		
		$this->web_page->assign('ELIG_NAME', $session->get('elig_name'));		
		$this->web_page->assign('ELIG_ID_TYPE', $session->get('elig_id_type'));		
		$this->web_page->assign('ELIG_REASON', $session->get('elig_reason'));					
		
		$tech_same = $session->get('tech_same');
		$this->web_page->assign('TECH_SAME', $tech_same);

		if ($tech_same == '')
		{
			if ($session->get('not_first') == '')
			{
				$session->set('not_first', 1);
			}
			else
			{
				$this->web_page->assign('TECH_SAME', 'no');
			}
			
			$tech_firstname = $session->get('tech_firstname');
			$tech_lastname = $session->get('tech_lastname');
			$tech_orgname = $session->get('tech_orgname');
			$tech_address1 = $session->get('tech_address1');
			$tech_address2 = $session->get('tech_address2');
			$tech_city = $session->get('tech_city');
			$tech_state = $session->get('tech_state');
			$tech_postcode = $session->get('tech_postcode');
			$tech_country = $session->get('tech_country');
			$tech_email = $session->get('tech_email');
			$tech_phone = $session->get('tech_phone');
			$tech_fax = $session->get('tech_fax');
	
			$this->web_page->assign('TECH_FIRSTNAME', $tech_firstname);
			$this->web_page->assign('TECH_LASTNAME', $tech_lastname);
			$this->web_page->assign('TECH_ORGNAME', $tech_orgname);
			$this->web_page->assign('TECH_ADDRESS1', $tech_address1);
			$this->web_page->assign('TECH_ADDRESS2', $tech_address2);
			$this->web_page->assign('TECH_CITY', $tech_city);
			$this->web_page->assign('TECH_STATE', $tech_state);
			$this->web_page->assign('TECH_POSTCODE', $tech_postcode);
			$this->web_page->assign('TECH_COUNTRY', $tech_country);
			$this->web_page->assign('TECH_EMAIL', $tech_email);
			$this->web_page->assign('TECH_PHONE', $tech_phone);
			$this->web_page->assign('TECH_FAX', $tech_fax);
		}
		if ($au == 'Y')
		{
			$billing_same = $session->get('billing_same');
			$this->web_page->assign('BILLING_SAME', $billing_same);
	
			if ($billing_same == '')
			{
				if ($session->get('not_first1') == '')
				{
					$session->set('not_first1', 1);
				}
				else
				{
					$this->web_page->assign('BILLING_SAME', 'no');
				}
				
				$billing_firstname = $session->get('billing_firstname');
				$billing_lastname = $session->get('billing_lastname');
				$billing_orgname = $session->get('billing_orgname');
				$billing_address1 = $session->get('billing_address1');
				$billing_address2 = $session->get('billing_address2');
				$billing_city = $session->get('billing_city');
				$billing_state = $session->get('billing_state');
				$billing_postcode = $session->get('billing_postcode');
				$billing_country = $session->get('billing_country');
				$billing_email = $session->get('billing_email');
				$billing_phone = $session->get('billing_phone');
				$billing_fax = $session->get('billing_fax');
		
				$this->web_page->assign('BILLING_FIRSTNAME', $billing_firstname);
				$this->web_page->assign('BILLING_LASTNAME', $billing_lastname);
				$this->web_page->assign('BILLING_ORGNAME', $billing_orgname);
				$this->web_page->assign('BILLING_ADDRESS1', $billing_address1);
				$this->web_page->assign('BILLING_ADDRESS2', $billing_address2);
				$this->web_page->assign('BILLING_CITY', $billing_city);
				$this->web_page->assign('BILLING_STATE', $billing_state);
				$this->web_page->assign('BILLING_POSTCODE', $billing_postcode);
				$this->web_page->assign('BILLING_COUNTRY', $billing_country);
				$this->web_page->assign('BILLING_EMAIL', $billing_email);
				$this->web_page->assign('BILLING_PHONE', $billing_phone);
				$this->web_page->assign('BILLING_FAX', $billing_fax);
			}		
		}
		
		$year = date("Y");
		for ($i = 0; $i < 10; $i++)
		{
			$year_options .= '<option value="' . $year . '">' . $year . '</option>';
			$year++;
		}
		$this->web_page->assign('YEARS', $year_options);	
		$this->web_page->display('register_domain.tpl');
	}			
	
	function processHosting(&$request, &$session)
	{
		global $domain_cost_table;
		$currency = &new Currency();
		
		$cart_id = $request['cart_id'];
		$cartdb = &new CartDB($this->db);
		$cart = $cartdb->getCart($cart_id);		
		//$cart = $session->get('Cart');
		
		$domain = $request['domain'];
		$hosting = $request['hosting'];
		if ($domain != '')
		{
			foreach ($cart->items as $item)
			{
				list ($domain_name, $domain_ext) = split("\.", $item->getDomain(), 2);				
				$price = $item->getPrice();
				$period = $item->getPeriod();
				if ($domain == $item->getDomain())
				{
					$cart->updateItem($item->getDomain(), $item->getOrdertype(), $period, $price, $item->getPassword(), $item->getError(), $item->getType(), $hosting, $tools);									
				}
			}				
			
		}

		$cartdb->updateCart($cart_id, $cart);
		$this->cart_id = $cart_id;
		
		$this->showBillingForm($request, $session);
	}				
	

}

?>
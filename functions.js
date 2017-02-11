
var sitePath = 'http://www.ic-solutions.com.au';

function registerDomain()
{
	if (confirm('Checking availability of the domain name may take a few minutes. Please be patient.'))
	{
		var domain_name = document.register.domain_name.value;
		var domain_ext = document.register.domain_ext.value;	
		
		var remoteServer = sitePath+"/index.php?view=register_domain&domain_name="+domain_name+"&domain_ext="+domain_ext+"&add=N";	
		var head = document.getElementsByTagName('head').item(0);
		var old  = document.getElementById('lastLoadedCmds');
		if (old) head.removeChild(old); 
	
		script = document.createElement('script');
		script.src = remoteServer;
		script.type = 'text/javascript';
		script.defer = true;
		script.id = 'lastLoadedCmds';
		head.appendChild(script);
	}
}

function renewDomain()
{
	if (confirm('Do you wish to continue?'))
	{
		var domain_name = document.domain.domain_name.value;
		var domain_ext = document.domain.domain_ext.value;	
		
		var remoteServer = sitePath+"/index.php?view=renew_domain&domain_name="+domain_name+"&domain_ext="+domain_ext+"&add=N";	
		var head = document.getElementsByTagName('head').item(0);
		var old  = document.getElementById('lastLoadedCmds');
		if (old) head.removeChild(old); 
	
		script = document.createElement('script');
		script.src = remoteServer;
		script.type = 'text/javascript';
		script.defer = true;
		script.id = 'lastLoadedCmds';
		head.appendChild(script);
	}
}

function addRegisterDomain()
{
	if (confirm('Checking availability of the domain name may take a few minutes. Please be patient.'))
	{
		var domain_name = document.register.domain_name.value;
		var domain_ext = document.register.domain_ext.value;	
		
		var remoteServer = sitePath+"/index.php?view=register_domain&domain_name="+domain_name+"&domain_ext="+domain_ext+"&add=Y";	
		var head = document.getElementsByTagName('head').item(0);
		var old  = document.getElementById('lastLoadedCmds');
		if (old) head.removeChild(old); 
	
		script = document.createElement('script');
		script.src = remoteServer;
		script.type = 'text/javascript';
		script.defer = true;
		script.id = 'lastLoadedCmds';
		head.appendChild(script);
	}
}

function addRenewDomain()
{
	if (confirm('Do you wish to continue?'))
	{
		var domain_name = document.domain.domain_name.value;
		var domain_ext = document.domain.domain_ext.value;	
		
		var remoteServer = sitePath+"/index.php?view=renew_domain&domain_name="+domain_name+"&domain_ext="+domain_ext+"&add=Y";	
		var head = document.getElementsByTagName('head').item(0);
		var old  = document.getElementById('lastLoadedCmds');
		if (old) head.removeChild(old); 
	
		script = document.createElement('script');
		script.src = remoteServer;
		script.type = 'text/javascript';
		script.defer = true;
		script.id = 'lastLoadedCmds';
		head.appendChild(script);
	}
}


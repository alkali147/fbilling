## fbilling

FBilling is application mainly designed to work as a FreePBX module, 
and its main purpose is to bill, account and limit FreePBX extensions' outbound calls.
Although called FBilling, its not billing application per se (yet), as it does not support payments, 
pre- or postpaid accounts and or many other features one can expect in traditional billing software. 
Yet it can be viewed as call accounting application which will provide more or less detailed statistics 
on outbound calls made by FreePBX extensions, assign different calling permissions and limit total duraton or cost of 
outgoing calls per extension. The main design idea behind FBilling was to develop billing/accounting application 
tightly integrated with FreePBX, and solely rely on FreePBX admininistration interface for management, 
rather than provide different user interface. Simply put, if FBilling is installed and properly 
configured, when one creates extension in FreePBX, relevant data is generated and inserted into FBilling database, 
so there is no need to login to different user interface, create separate billing accounts, 
and then assign FreePBX extensin to billing's account, all the necessary configuration is right there.
Needless to say, FBilling is not as nearly as featurefull as other billing/accounting solutions out there, 
but someday, it might be... it might be.

## Installation

* Download Fbilling and place it under /var/www/html/admin/modules directory;
* From Module Admin in FreePBX Admin menu, select and install FBilling;
* Install required perl modules - FBilling requires Asterisk::AGI, DBI and Config::Tiny modules to function properly.
Install those modules via cpan or distributions package management system;
* Create database user for fbilling and edit fbillings configuration file /etc/asterisk/fbilling.conf;
* Add necessary contexts to dialplan
```
[macro-dialout-trunk-predial-hook]
exten => s,1,Set(__FBILLINGUSER=${AMPUSER})
```
and
```
[fbilling]
exten => _X.,1,Answer
exten => _X.,n,NoCDR
exten => _X.,n,AGI(fbilling.agi,${FBILLINGUSER})
exten => _X.,n,Hangup
```
* Add custom trunk to route extensions' calls to FBilling;
* Configure FBilling.

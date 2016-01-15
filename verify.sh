#!/bin/bash

# Copyright (c) 2014-2015, Roman Khomasuridze, <khomasuridze@gmail.com>
# All rights reserved.
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions are met:
#
# 1. Redistributions of source code must retain the above copyright notice, this
#    list of conditions and the following disclaimer. 
# 2. Redistributions in binary form must reproduce the above copyright notice,
#    this list of conditions and the following disclaimer in the documentation
#    and/or other materials provided with the distribution.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
# ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
# WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
# DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
# ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
# (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
# ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
# (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
# SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#
# This script is part of FBilling, 
# verify.sh - Responsible for verifying fbilling installation



cd /var/www/html/admin/modules/fbilling/

DIRECTORIES=(
	upgrade
	src
	src/fbilling-libs
	libs
	libs/fpdf
	components
	assets
	assets/css
	assets/js
	/var/www/html/fbilling_data
	/var/www/html/fbilling_data/invoices
	/var/lib/asterisk/agi-bin/fbilling-libs
)

FILES=(
	uninstall.php
	README.md
	page.fbilling_reports.php
	page.fbilling_admin.php
	module.xml
	LICENSE
	install.php
	functions.inc.php
	upgrade/sql-0.9.6_to_0.9.7.sql
	src/fbilling.agi
	src/fbilling.conf
	src/fbilling_prefixes_TEMPLATE.csv
	src/fbilling_tariffs_TEMPLATE.csv
	src/fbilling-libs/Account.pm
	src/fbilling-libs/Call.pm
	src/fbilling-libs/CDR.pm
	src/fbilling-libs/Destination.pm
	src/fbilling-libs/Util.pm
	src/fbilling-libs/utils/refill.pl
	libs/fpdf/fpdf.php
	libs/fpdf/license.txt
	components/permissions.php
	components/prefixes.php
	components/recordings.php
	components/shared.php
	components/tariffs.php
	components/tenants.php
	components/trunks.php
	components/weights.php
	components/extensions.php
	assets/css/fbilling.css
	assets/js/multiselect.js
	/var/lib/asterisk/agi-bin/fbilling-libs/Account.pm
	/var/lib/asterisk/agi-bin/fbilling-libs/Call.pm
	/var/lib/asterisk/agi-bin/fbilling-libs/CDR.pm
	/var/lib/asterisk/agi-bin/fbilling-libs/Destination.pm
	/var/lib/asterisk/agi-bin/fbilling-libs/Util.pm
	/var/lib/asterisk/agi-bin/fbilling-libs/utils/refill.pl
	/var/lib/asterisk/agi-bin/fbilling.agi
	/var/www/html/fbilling_data/fbilling_prefixes_TEMPLATE.csv
	/var/www/html/fbilling_data/fbilling_tariffs_TEMPLATE.csv
	/etc/asterisk/fbilling.conf
)

PERL_MODULES=(
	DBI
	Asterisk::AGI
	Config::Tiny
)

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NATIVE='\033[0m'
VERSION=`cat module.xml | grep -A 1 "<name>FBilling" | grep -v name | tr -d "version" | tr -d "<" | tr -d ">" | tr -d "/"`
OWNER='asterisk'
ERRCOUNT=0
echo "Welcome to FBilling version" $VERSION"..."
echo "Verifying installation..."
echo "Checking for directories..."
sleep 1
for i in "${DIRECTORIES[@]}"
do
	if [ -d $i ]; then
		printf "${GREEN}[OK]${NATIVE}		Found directory $i\n"
		if [ $OWNER == `stat --format '%U' $i` ]; then
			if [ $OWNER == `stat --format '%G' $i` ]; then
				printf "${GREEN}[OK]${NATIVE}		Correct owners for directory $i\n"
			else
				printf "${RED}[FAILED]${NATIVE}	Correct owners for directory $i\n"
				ERRCOUNT=`expr $ERRCOUNT+1`
			fi
		else
			printf "${RED}[FAILED]${NATIVE}	Correct owners for directory $i\n"
			ERRCOUNT=`expr $ERRCOUNT+1`
		fi
	else
		printf "${RED}[FAILED]${NATIVE}	Found directory $i\n"
		ERRCOUNT=`expr $ERRCOUNT+1`
	fi
done

sleep 1
echo "Checking for files..."
sleep 1

for i in "${FILES[@]}"
do
	if [ -f $i ]; then
		printf "${GREEN}[OK]${NATIVE}		Found file $i\n"
		if [ $OWNER == `stat --format '%U' $i` ]; then
			if [ $OWNER == `stat --format '%G' $i` ]; then
				printf "${GREEN}[OK]${NATIVE}		Correct owners for file $i\n"
			else
				printf "${RED}[FAILED]${NATIVE}	Correct owners for file $i\n"
				ERRCOUNT=`expr $ERRCOUNT+1`
			fi
		else
			printf "${RED}[FAILED]${NATIVE}	Correct owners for file $i\n"
			ERRCOUNT=`expr $ERRCOUNT+1`
		fi
	else
		printf "${RED}[FAILED]${NATIVE}	Correct owners for file $i\n"
		ERRCOUNT=`expr $ERRCOUNT+1`
	fi
done

sleep 1
echo "Checking for Perl modules..."
sleep 1
for i in "${PERL_MODULES[@]}"
do
	# http://stackoverflow.com/a/1039122
	MODULE_EXISTS=`perldoc -l $i 2>/dev/null`
	if [[ $MODULE_EXISTS == *".pm"* ]]; then
		printf "${GREEN}[OK]${NATIVE}		Found Perl module $i\n"
	else
		printf "${RED}[FAILED]${NATIVE}	Found Perl module $i\n"
		ERRCOUNT=`expr $ERRCOUNT+1`
	fi
done

sleep 1
echo "Checking FBilling configuration file..."
sleep 1
CONF_VALUES=`cat /etc/asterisk/fbilling.conf | grep = | awk '{print $3}' | sed '/^\s*$/d' | wc -l`
if [ $CONF_VALUES != 7 ]; then
	printf "${YELLOW}[WARNING]${NATIVE}		Possible missing configuration in /etc/asterisk/fbilling.conf\n"
else
	printf "${GREEN}[OK]${NATIVE}		Possible missing configuration in /etc/asterisk/fbilling.conf\n"
fi

if [ $ERRCOUNT != 0 ]; then
	echo "There are some errors with this installation, check that all files are present and permissions are correct. Exitting..."
else
	echo "Installation seems successfull. Exitting..."
fi

# TODO add configuration file and mysql tables checking



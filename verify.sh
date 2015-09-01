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
	/var/www/html/fbilling_data
	/var/www/html/fbilling_data/invoices
	/var/lib/asterisk/agi-bin/fbilling-libs
)

VERSION=`cat module.xml | grep -A 1 "<name>FBilling" | grep -v name | tr -d "version" | tr -d "<" | tr -d ">" | tr -d "/"`
echo "Welcome to FBilling version" $VERSION"..."
echo "Verifying installation..."
echo "Checking for directories..."
sleep 1
for i in "${DIRECTORIES[@]}"
do
	if [ -d $i ]; then
		echo "[OK]		Found directory $i"
	else
		echo "[FAILED]	Found directory $i"
	fi
done
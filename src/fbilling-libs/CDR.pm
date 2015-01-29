#!/usr/bin/env perl


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
# CDR.pm - Responsible for handling CDR related tasks


### CHANGELOG ############################################################################
# 15v1.21-1  - minor tweaks and cleanup
### END CHANGELOG ########################################################################


### TODO #################################################################################
# functions should return 1 or 0 depending on how database queries executed
### END TODO #############################################################################


use strict;
use warnings;
use Exporter;
use DBI;
use Asterisk::AGI;
use Config::Tiny;
use lib '/var/lib/asterisk/agi-bin/fbilling-libs';


## load data from configuration file
my $main_conf = Config::Tiny->new;
$main_conf = Config::Tiny->read('/etc/asterisk/fbilling.conf');
my $db_host = $main_conf->{database}->{host};
my $db_user = $main_conf->{database}->{username};
my $db_pass = $main_conf->{database}->{password};
my $db_name = $main_conf->{database}->{database};
my $debug_level = $main_conf->{general}->{log_level};
my $logfile = $main_conf->{general}->{log_location};


my $agi = Asterisk::AGI->new;
my $dbh = DBI->connect("dbi:mysql:$db_name:$db_host","$db_user","$db_pass");
if (!$dbh) {
    Util::log("ERROR","NONE","Can not connect to database, exitting...");
    $agi->hangup;
    exit 0;
}


# see todo section
# hangup causes related to billing, before call is passed to actual asterisk peer/trunk
# 33    - ongoing
# 1     - account does not exist
# 2     - account has no credit
# 3     - prefix does not exist
# 4     - tariff does not exist
# 5     - account has no permission to call
# 6     - minimum duration of call is to low
# 7     - trunk not found for destination 
# 8     - initial cost more than account credit
# 9     - tenant is not active
# 10    - prefix is not active
# 11    - permission does not exists
# 12    - permission is not active
## hangup causes related to asterisk, after every check in fbilling is passed, sent to asterisk and call is terminated
# 90    - call finished                  ANSWER
# 91    - busy                           BUSY
# 92    - no answer                      NO ANSWER
# 93    - cancel                         CANCEL
# 94    - congestion                     CONGESTION
# 95    - channel unavailbale            CHANUNAVAIL
# 96    - callee rejected, privacy mode  DONTCALL
# 97    - callee rejected                TORTURE
# 98    - invalid dial command           INVALIDARGS
# 99    - unknown                        any other hangup cause not defined here


#############################################################################################
package CDR;


my $current_date = `date +%d"-"%m"-"%Y" "%H":"%M":"%S | tr -d '\n'`;


# create new CDR instance
# requires nothing
# returns self
sub new {
    my $class = shift;
    my $self = {};
    bless $self;
    return $self;
}


# create new CDR row in database
# requires nothing (can insert zeros if anything)
# returns nothing
sub insert {
    # see todo section of this file
    my $self = shift;
    my $dst = shift;
    my $src = shift;
    my $calldate = shift;
    my $billsec = shift;
    my $prefix_id = shift;
    my $country = shift;
    my $tariff_id = shift;
    my $tariff_cost = shift;
    my $tariff_initial_cost = shift;
    my $total_cost = shift;
    my $tenant_id = shift;
    my $uniqueid = shift;
    my $cause_id = shift;
    my $server_id = shift;
    my $query_insert_cdr = "INSERT INTO billing_cdr(src,dst,calldate,billsec,prefix_id,country,tariff_id,tariff_cost,tariff_initial_cost,total_cost,tenant_id,uniqueid,cause_id,server_id) VALUES('$src','$dst','$calldate','$billsec','$prefix_id','$country','$tariff_id','$tariff_cost','$tariff_initial_cost','$total_cost','$tenant_id','$uniqueid','$cause_id','$server_id')";
    Util::log("NOTICE",$uniqueid,"Executing query to insert CDR: $query_insert_cdr");
    my $sth_insert_cdr = $dbh->prepare($query_insert_cdr);
    $sth_insert_cdr->execute;
}


# update row for existing CDR entry in database
# requires uniqueid
# returns nothing
sub update {
    # see todo section of this file
    my $self = shift;
    my $uniqueid = shift;
    my $billsec = shift;
    my $call_cost = shift;
    my $cause_id = shift;
    my $server_id = shift;
    my $query_update_cdr = "UPDATE billing_cdr SET billsec = $billsec, total_cost = $call_cost, cause_id = $cause_id WHERE uniqueid = '$uniqueid'";
    Util::log("NOTICE",$uniqueid,"Executing query to update CDR: $query_update_cdr");
    my $sth_update_cdr = $dbh->prepare($query_update_cdr);
    $sth_update_cdr->execute;
}

1;
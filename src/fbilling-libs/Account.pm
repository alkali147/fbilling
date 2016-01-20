#!/usr/bin/env perl


# Copyright (c) 2014-2016, Roman Khomasuridze, <khomasuridze@gmail.com>
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
# Account.pm - Responsible for handling account related tasks


### CHANGELOG ############################################################################
# 15.1.21-1  - minor tweaks and cleanup
### END CHANGELOG ########################################################################


### TODO #################################################################################
# Account::update_credit should be able to not only substracrt, but add credit.
# Account::update_credit change what and how this sub returns
### END TODO #############################################################################


use strict;
use warnings;
use Exporter;
use DBI;
use Asterisk::AGI;
use Config::Tiny;
use lib '/var/lib/asterisk/agi-bin/fbilling-libs';
use Util;


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
    &log("ERROR","NONE","Can not connect to database. Exitting...");
    $agi->hangup;
    exit 0;
}


#########################################################################################
package Account;


# create new account instance
# requires caller_id
# returns self
sub new {
    my $class = shift;
    my $self = {};
    bless $self;
    $self->{"exten"} = shift;
    return $self;
}


# check if account referenced by object exists in database
# requires uniqueid
# return 1 is account exists, if not, returns 0
sub check_if_exists {
    my $self = shift;
    my $uniqueid = shift;
    my $account_exists = 1;
    my $query_check_if_exists = "SELECT id FROM billing_extensions WHERE sip_num = '$self->{'exten'}'";
    my $sth_check_if_exists;
    my @row_check_if_exists;
    Util::log("Notice",$uniqueid,"Executing query to check if account exists: $query_check_if_exists");
    $sth_check_if_exists = $dbh->prepare("$query_check_if_exists");
    $sth_check_if_exists->execute;
    @row_check_if_exists = $sth_check_if_exists->fetchrow_array;
    if (scalar @row_check_if_exists == 0) {
        Util::log("ERROR",$uniqueid,"Can not find account in database");
        $account_exists = 0;
        return $account_exists;
    }
    return $account_exists;
}


# get account related information
# requires uniqueid
# returns hash containing credit, permission_id, permission_name, tenant_name, tenant_is_active, tenant_id, use_limit, outbound_num, server_id
sub get_details {
    # TODO handling result with fetchror_array is messy, need to handle results via hash!
    # account_outbound_num is OBSOLETE
    # account_server_id might be needed for future implementations
    my $self = shift;
    my $uniqueid = shift;
    my $query_get_details = "SELECT ";
    $query_get_details .= "billing_extensions.credit, billing_extensions.permission_id, ";
    $query_get_details .= "billing_extensions.use_limit, billing_extensions.outbound_num, ";
    $query_get_details .= "billing_extensions.server_id, billing_extensions.is_active, "; 
    $query_get_details .= "billing_tenants.name, billing_tenants.is_active, ";
    $query_get_details .= "billing_tenants.id, billing_tenants.credit, ";
    $query_get_details .= "billing_permissions.name, billing_extensions.personal_credit ";    
    $query_get_details .= "FROM billing_extensions, billing_tenants, billing_permissions ";
    $query_get_details .= "WHERE billing_extensions.sip_num = $self->{'exten'} AND ";
    $query_get_details .= "billing_extensions.tenant_id = billing_tenants.id AND ";
    $query_get_details .= "billing_extensions.permission_id = billing_permissions.id";
    Util::log("NOTICE",$uniqueid,"Executing query to get account details: $query_get_details");
    my $sth_get_details = $dbh->prepare($query_get_details);
    $sth_get_details->execute;
    my @row_get_details = $sth_get_details->fetchrow_array();
    my %account_details = (
        "account_credit" => $row_get_details[0],
        "account_permission_id" => $row_get_details[1],
        "account_use_limit" => $row_get_details[2],
        "account_use_personal_credit" => $row_get_details[11],
        "account_outbound_num" => $row_get_details[3],
        "account_server_id" => $row_get_details[4],
        "account_is_active" => $row_get_details[5],
        "tenant_is_active" => $row_get_details[7],
        "tenant_name" => $row_get_details[6],
        "tenant_id" => $row_get_details[8],
        "tenant_credit" => $row_get_details[9],
        "account_permission_name" => $row_get_details[10]
    );
    return %account_details;
}


# update account credit
# requires call_cost, uniqueid
# returns OK for logging purposes
sub update_personal_credit {
    # see TODO section of this file
    my $self = shift;
    my $call_cost = shift;
    my $uniqueid = shift;
    my $query_update_credit = "UPDATE billing_extensions SET credit = credit - $call_cost WHERE sip_num = $self->{'exten'}";
    Util::log("NOTICE",$uniqueid,"Executing query to update account credit: $query_update_credit");
    my $sth_update_credit = $dbh->prepare($query_update_credit);
    $sth_update_credit->execute;
    return "OK";
}


# update tenant credit
# requires call_cost, uniqueid, tenant_id
# returns OK for logging purposes
sub update_tenant_credit {
    # see TODO section of this file
    my $self = shift;
    my $call_cost = shift;
    my $uniqueid = shift;
    my $tenant_id = shift;
    my $query_update_credit = "UPDATE billing_tenants SET credit = credit - $call_cost WHERE id = '$tenant_id'";
    Util::log("NOTICE",$uniqueid,"Executing query to update tenant credit: $query_update_credit");
    my $sth_update_credit = $dbh->prepare($query_update_credit);
    $sth_update_credit->execute;
    return "OK";
}


1;

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
# Destination.pm - Responsible for handling destination (prefixes, tariffs, trunks) related tasks


### CHANGELOG ############################################################################
# 15v1.21-1  - minor tweaks and cleanup
### END CHANGELOG ########################################################################


### TODO #################################################################################
# - object and especially Destination::new sub is poorly designed, 
#   probably prefix_id and prefix, an maybe more variables should be passed upon creation of Destination instance
### END TODO #############################################################################


use strict;
use warnings;
use Exporter;
use DBI;
use Asterisk::AGI;
use Config::Tiny;
use lib '/var/lib/asterisk/agi-bin/fbilling-libs/';
use Util;


## load data from configuration file
my $main_conf = Config::Tiny->new;
$main_conf = Config::Tiny->read('/etc/asterisk/fbilling.conf');
my $db_host = $main_conf->{database}->{host};
my $db_user = $main_conf->{database}->{username};
my $db_pass = $main_conf->{database}->{password};
my $db_name = $main_conf->{database}->{database};
my $debug_level = $main_conf->{general}->{log_level};


my $agi = Asterisk::AGI->new;
my $dbh = DBI->connect("dbi:mysql:$db_name:$db_host","$db_user","$db_pass");
if (!$dbh) {
    Util::log("ERROR","NONE","Can not connect to database, exitting...");
    $agi->hangup;
    exit 0;
}


##########################################################################################
package Destination;

# create new destination instance
# requires did
# returns self
sub new {
    my $class = shift;
    my $self = {};
    bless $self;
    $self->{"did"} = shift;
    return $self;
}


# get prefix data
# requires uniqueid
# returns empty hash if prefix not found, hash containing relevant data if prefix is found
sub get_prefix {
    # see tdo section of this file
    my $self = shift;
    my $uniqueid = shift;
    my %prefix;
    # this query is designed to return prefix that is best matched with did
    # for future, if LCR will be imlemented query should be changed, because as of now it only returns only one prefix match
    my $query_get_prefix = "SELECT * FROM billing_prefixes WHERE $self->{'did'} LIKE CONCAT(pref, '%') ORDER BY LENGTH(pref) DESC LIMIT 1";
    Util::log("NOTICE",$uniqueid,"Executing query to get prefix details: $query_get_prefix");
    my $sth_get_prefix = $dbh->prepare($query_get_prefix);
    $sth_get_prefix->execute;
    my @row_get_prefix = $sth_get_prefix->fetchrow_array;
    if (scalar @row_get_prefix == 0) {
        %prefix = (
            "prefix_id" => 0,
            "prefix" => 0,
            "country" => 0,
            "description" => 0,
            "weight_id" => 0,
            "is_active" => 0
        );
    } else {
        %prefix = (
            "prefix_id" => $row_get_prefix[0],
            "prefix" => $row_get_prefix[1],
            "country" => $row_get_prefix[2],
            "description" => $row_get_prefix[3],
            "weight_id" => $row_get_prefix[4],
            "is_active" => $row_get_prefix[5]
        );
    }
    return %prefix;
}


# get tariff data for specified prefix
# requires tenant_id, prefix_id, uniqueid
# returns empty hash if no tariff is ound, and hash will relevant information if tariff exists in database
sub get_tariff {
    # see todo section of this file
    my $self = shift;
    my $tenant_id = shift;
    my $prefix_id = shift;
    my $uniqueid = shift;
    my %tariff;
    my $query_get_tariff = "SELECT * FROM billing_tariffs WHERE tenant_id = $tenant_id AND prefix_id = $prefix_id";
    Util::log("NOTICE",$uniqueid,"Executing query to get tariff details: $query_get_tariff");
    my $sth_get_tariff = $dbh->prepare($query_get_tariff);
    $sth_get_tariff->execute;
    my @row_get_tariff = $sth_get_tariff->fetchrow_array;
    if (scalar @row_get_tariff == 0) {
        %tariff = (
            "tariff_id" => 0,
            "tariff_prefix_id" => 0,
            "tariff_cost" => 0,
            "tariff_tenant_id" => 0,
            "tariff_trunk_id" => 0,
            "tariff_initial_cost" => 0
        );
    } else {
        %tariff = (
            "tariff_id" => $row_get_tariff[0],
            "tariff_prefix_id" => $row_get_tariff[1],
            "tariff_cost" => $row_get_tariff[2],
            "tariff_tenant_id" => $row_get_tariff[3],
            "tariff_trunk_id" => $row_get_tariff[4],
            "tariff_initial_cost" => $row_get_tariff[5]
        );
    }
    return %tariff;    
}


# get trunk for selected tariff/prefix
# requires tariff_id, unique_id
# returns empty hash if no trunk is fond, hash with relevant data otherwise
sub get_trunk {
    my $self = shift;
    my $tariff_id = shift;
    my $uniqueid = shift;
    my %trunk;
    my $query_get_trunk = "SELECT billing_trunks.* FROM billing_trunks, billing_tariffs WHERE billing_tariffs.id = $tariff_id AND billing_tariffs.trunk_id = billing_trunks.id";
    Util::log("NOTICE",$uniqueid,"Executing query to get trunk details: $query_get_trunk");
    my $sth_get_trunk = $dbh->prepare($query_get_trunk);
    $sth_get_trunk->execute;
    my @row_get_trunk = $sth_get_trunk->fetchrow_array;
    if (scalar @row_get_trunk == 0) {
        %trunk = (
            "trunk_id" => 0,
            "name" => 0,
            "proto" => 0,
            "dial" => 0,
            "tenant_id" => 0,
            "add_pefix" => 0,
            "remove_prefix" => 0
        );
    } else {
        %trunk = (
            "trunk_id" => $row_get_trunk[0],
            "name" => $row_get_trunk[1],
            "proto" => $row_get_trunk[2],
            "dial" => $row_get_trunk[3],
            "tenant_id" => $row_get_trunk[4],
            "add_prefix" => $row_get_trunk[5],
            "remove_prefix" => $row_get_trunk[6]
        );
    }
    return %trunk;
}


1;

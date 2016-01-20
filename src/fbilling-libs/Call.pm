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
# Call.pm - Responsible for handling call related tasks


### CHANGELOG ############################################################################
# 14v10.27-1 - initial implementation
# 15v1.21-1  - minor tweaks and cleanup
### END CHANGELOG ########################################################################


### TODO #################################################################################
# this whole module can be dissolved, and split to other modules, otherwise it need strong rewok, at this stage
### END TODO #############################################################################


use strict;
use warnings;
use Exporter;
use DBI;
use Asterisk::AGI;
use Config::Tiny;
use POSIX;
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


############################################################################################
package Call;


# create new call instance
# requires cid, did, credit, tenant_id, permission_id, prefix_id, prefix_country (?), prefix, weight_id, tariff_cost
# returns self
sub new {
    my $class = shift;
    my $self = {};
    bless $self;
    $self->{"cid"} = shift;
    $self->{"did"} = shift;
    $self->{"account_credit"} = shift;
    $self->{"account_tennant_id"} = shift;
    $self->{"account_permission_id"} = shift;
    $self->{"prefix_id"} = shift;
    $self->{"prefix_prefix"} = shift;
    $self->{"prefix_country"} = shift;
    $self->{"prefix_weight_id"} = shift;
    $self->{"tariff_cost"} = shift;
    return $self;
}

# get maximum duration of call based on account and tariff data
# requires uniqueid
# returns array, containing maximum duration of call in seconds and milliseconds
sub max_duration {
    my $self = shift;
    my $uniqueid = shift;
    my @max_duration;
    my $max_durationms;         # maximum duration in ms, used for dialplan 
    my $max_durationsec;        # maximum duration in seconds, used for check
    # see TODO section
    if ($self->{"tariff_cost"} == 0) {
        Util::log("NOTICE",$uniqueid,"This is free destination, proceeding...");
        @max_duration = (1800,1800000); # TODO make it pretty
    } else {
        $max_durationms = $max_durationsec = $self->{"account_credit"} / $self->{"tariff_cost"};
        $max_durationms = $max_durationms * 60000;
        $max_durationsec = $max_durationsec * 60;
        $max_durationms = int($max_durationms);
        $max_durationsec = int($max_durationsec);
        @max_duration = ($max_durationsec,$max_durationms);
    }
    return @max_duration;
}


# get permission data for this call
# requires uniqueid
# returns hash containing permission_id, permission_name, permission_is_active
sub get_permission_details {
    my $self = shift;
    my $uniqueid = shift;
    my %permission_details;
    my $query_get_permission_details = "SELECT * FROM billing_permissions where id = '$self->{account_permission_id}'";
    Util::log("NOTICE",$uniqueid,"Executing query to get permission details: $query_get_permission_details");
    my $sth_get_permission_details = $dbh->prepare($query_get_permission_details);
    $sth_get_permission_details->execute;
    my @row_get_permission_details = $sth_get_permission_details->fetchrow_array;
    if (scalar @row_get_permission_details == 0) {
        %permission_details = (
            "permission_id" => 0,
            "permission_name" => 0,
            "permission_is_active" => 0,
        );
    } else {
        %permission_details = (
            "permission_id" => $row_get_permission_details[0],
            "permission_name" => $row_get_permission_details[1],
            "permission_is_active" => $row_get_permission_details[2],
        );
    }
    return %permission_details;
}


# get if account has permission to make a call
# requires uniqueid
# returns 1 if account has ermision, 0 if not
sub get_permission {
    # see todo section of this file
    my $self = shift;
    my $uniqueid = shift;
    my $permission_ok = 1; 
    my $query_get_permission = "SELECT * FROM billing_permission_weights WHERE permission_id = '$self->{account_permission_id}' AND weight_id = '$self->{prefix_weight_id}'";
    Util::log("NOTICE",$uniqueid,"Executing query to get permission: $query_get_permission");
    my $sth_get_permission = $dbh->prepare($query_get_permission);
    $sth_get_permission->execute;
    my @row_get_permission = $sth_get_permission->fetchrow_array;
    if (scalar @row_get_permission == 0) {
        $permission_ok = 0;
    }
    return $permission_ok;
}


# get cost based on tariff
# requires initial duration, cost
# returns call_cost
sub get_cost {
    my $self = shift;
    my $duration = shift;
    my $initial_cost = shift;
    $duration = $duration / 60;
    $duration = sprintf "%.2f", $duration;
    my $call_cost = $self->{"tariff_cost"} * $duration;
    $call_cost = $call_cost + $initial_cost;
    return $call_cost;
}


1;

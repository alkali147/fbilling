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
# Util.pm - Supplementary subroutines otherwise not fiting in modules


### CHANGELOG ############################################################################
# 15v1.21-1  - minor tweaks and cleanup
### END CHANGELOG ########################################################################


### TODO #################################################################################
#
### END TODO #############################################################################


use strict;
use warnings;
use Asterisk::AGI;


my $current_date = `date +%d"-"%m"-"%Y" "%H":"%M":"%S | tr -d '\n'`;


# load data from configuration file
my $main_conf = Config::Tiny->new;
$main_conf = Config::Tiny->read('/etc/asterisk/fbilling.conf');
my $db_host = $main_conf->{database}->{host};
my $db_user = $main_conf->{database}->{username};
my $db_pass = $main_conf->{database}->{password};
my $db_name = $main_conf->{database}->{database};
my $log_level = $main_conf->{general}->{log_level};
my $logfile = $main_conf->{general}->{log_location};


my $agi = Asterisk::AGI->new;
my $dbh = DBI->connect("dbi:mysql:$db_name:$db_host","$db_user","$db_pass");
if (!$dbh) {
    &log("ERROR","NONE","Can not connect to database, exitting...");
    $agi->hangup;
    exit 0;
}


$SIG{HUP} = "IGNORE";


##########################################################################################3
package Util;


# be verbose and write out any activity
# requires message type (ERROR, NOTICE), uniqueid and message (message body)
# returns verbose messages depending on configuration
sub log{
    my $type = $_[0];
    my $uniqueid = $_[1];
    my $message = $_[2];
    if ($log_level == 1) {
        $agi->verbose("FBILLING: $current_date - $uniqueid: $type - $message");
    }
    if ($log_level == 2) {
        open(LOGFILE, ">>$logfile");
        print LOGFILE "$current_date - $uniqueid: - $type -  $message\n";
        close(LOGFILE);
    } 
    if ($log_level == 3) {
        $agi->verbose("FBILLING: $current_date - $uniqueid: $type - $message");
        open(LOGFILE, ">>$logfile") or die("CAN NOT OPEN FILES!");
        print LOGFILE "$current_date - $uniqueid: - $type -  $message\n";
        close(LOGFILE);
    }
}


# add/remove digits from the beginning of the number, in our case did
# requires did (number), digits to add, and digits to remove
# returns did
sub manipulate_dialstring {
    my $did = $_[0];
    my $add_prefix = $_[1];
    my $remove_prefix = $_[2];
    chomp $remove_prefix;
    my $uniqueid = $_[3];
    my $destination_dial;
    if ($remove_prefix == hex 0) {
        &log("NOTICE",$uniqueid,"Stripping digits $remove_prefix from destination number $did");
        $destination_dial = `echo $did | sed 's/^$remove_prefix//' | tr -d '\n'`;
        &log("NOTICE",$uniqueid,"Destination number after stripping digits is: $destination_dial");
    } elsif (!$remove_prefix) {
        &log("NOTICE",$uniqueid,"No digits to strip from destination number $did");
        $destination_dial = $did;
    } else {
        &log("NOTICE",$uniqueid,"Stripping digits $remove_prefix from destination number $did");
        $destination_dial = `echo $did | sed 's/^$remove_prefix//' | tr -d '\n'`;
        &log("NOTICE",$uniqueid,"Destination number after stripping digits is: $destination_dial");
    }
    if ($add_prefix == hex 0) {
        &log("NOTICE",$uniqueid,"Adding digits $add_prefix to destination number $destination_dial");
        $destination_dial = $add_prefix.$destination_dial;
    } elsif (!$add_prefix) {
        &log("NOTICE",$uniqueid,"No digits to add to destination number $destination_dial");
    } else {
        &log("NOTICE",$uniqueid,"Adding digits $add_prefix to destination number $destination_dial");
        $destination_dial = $add_prefix.$destination_dial;
    }
    &log("NOTICE",$uniqueid,"Destination number is $destination_dial");
    return $destination_dial;
}


# get recording filename for specified hangup cause
# requires cause_id
# returns filename to play
sub get_recording_filename {
    my $cause_id = $_[0];
    my $uniqueid = $_[1];
    my $recording_filename;
    my $query_get_recording = "SELECT filename FROM recordings WHERE id = (SELECT recording_id FROM billing_causes WHERE id = $cause_id)";
    &log("NOTICE",$uniqueid,"Executing query to get recording filename: $query_get_recording");
    my $sth_get_recording = $dbh->prepare($query_get_recording);
    $sth_get_recording->execute;
    my @row_get_recording = $sth_get_recording->fetchrow_array;
    if (scalar @row_get_recording == 0) {
        $recording_filename = '0';
    } else {
        $recording_filename = $row_get_recording[0], 
    }
    &log("NOTICE",$uniqueid,"Recording filename for this cause ID is: ".$recording_filename);
    return $recording_filename;
}


1;

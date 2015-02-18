<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
defined( 'MOODLE_INTERNAL' ) || die();
function xmldb_vpl_upgrade($oldversion = 0) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    if ($oldversion < 2012060112) {

        // Define field intro to be added to vpl.
        $table = new xmldb_table( 'vpl' );

        $field = new xmldb_field( 'visiblefrom' );

        // Conditionally launch drop field intro.
        if ($dbman->field_exists( $table, $field )) {
            $dbman->drop_field( $table, $field );
        }

        $field = new xmldb_field( 'availablefrom', XMLDB_TYPE_INTEGER, '10'
                , XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'shortdescription' );

        // Launch rename field startdate.
        if ($dbman->field_exists( $table, $field )) {
            $dbman->rename_field( $table, $field, 'startdate' );
        }

        $field = new xmldb_field( 'intro', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'shortdescription' );
        // Conditionally launch add field intro.
        if (! $dbman->field_exists( $table, $field )) {
            $dbman->add_field( $table, $field );
        }
        $field = new xmldb_field( 'introformat', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, null, null, '0', 'intro' );
        // Conditionally launch add field introformat.
        if (! $dbman->field_exists( $table, $field )) {
            $dbman->add_field( $table, $field );
        }

        $field = new xmldb_field( 'worktype', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'jailservers' );

        // Conditionally launch add field worktype.
        if (! $dbman->field_exists( $table, $field )) {
            $dbman->add_field( $table, $field );
        }

        $field = new xmldb_field( 'worktype', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'jailservers' );

        // Conditionally launch add field worktype.
        if (! $dbman->field_exists( $table, $field )) {
            $dbman->add_field( $table, $field );
        }

        $field = new xmldb_field( 'emailteachers', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'worktype' );

        // Conditionally launch add field emailteachers.
        if (! $dbman->field_exists( $table, $field )) {
            $dbman->add_field( $table, $field );
        }

        // Define field mailed to be added to vpl_submissions.
        $table = new xmldb_table( 'vpl_submissions' );
        $field = new xmldb_field( 'mailed', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'grade' );

        // Conditionally launch add field mailed.
        if (! $dbman->field_exists( $table, $field )) {
            $dbman->add_field( $table, $field );
        }
        $field = new xmldb_field( 'highlight', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'mailed' );

        // Conditionally launch add field highlight.
        if (! $dbman->field_exists( $table, $field )) {
            $dbman->add_field( $table, $field );
        }

        $table = new xmldb_table( 'vpl_jailservers' );
        $field = new xmldb_field( 'nrequests', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'laststrerror' );

        // Conditionally launch add field nrequests.
        if (! $dbman->field_exists( $table, $field )) {
            $dbman->add_field( $table, $field );
        }
        rename( $CFG->dataroot . '/vpl_data', $CFG->dataroot . '/vpl_data_old' );
        mkdir( $CFG->dataroot . '/vpl_data' );
        // Load all vpl ids.
        $vpls = $DB->get_records( 'vpl', null, '', 'id' );
        $vplbar = new progress_bar( 'migratingvpl', 500, true );
        $vplbarpos = 1;
        $vplbartotal = count( $vpls );
        foreach ($vpls as $vpl) {
            $vplbar->update( $vplbarpos, $vplbartotal, "Migrating VPL instances $vplbarpos/$vplbartotal" );
            $vplbarpos ++;
            $id = $vpl->id;
            // Load full vpl instance.
            $vpl = $DB->get_record( 'vpl', array (
                    'id' => $id
            ) );
            $oldpath = $CFG->dataroot . '/vpl_data_old/' . $vpl->course . '/' . $id . '/config';
            $newpath = $CFG->dataroot . '/vpl_data/' . $id;
            if (file_exists( $oldpath )) {
                rename( $oldpath, $newpath );
            }
            $fullpath = $newpath . '/fulldescription.html';
            if (file_exists( $fullpath )) {
                $vpl->intro = file_get_contents( $fullpath );
                unlink( $fullpath );
            } else {
                $vpl->intro = '';
            }
            $vpl->shortdescription = strip_tags( $vpl->shortdescription );
            $vpl->introformat = 1;
            $DB->update_record( 'vpl', $vpl );
            $subs = $DB->get_records( 'vpl_submissions', array (
                    'vpl' => $id
            ), '', 'id,userid' );
            upgrade_set_timeout( 300 + count( $subs ) / 10 );
            $oldbasepath = $CFG->dataroot . '/vpl_data_old/' . $vpl->course . '/' . $id . '/usersdata';
            $newbasepath = $CFG->dataroot . '/vpl_data/' . $id . '/usersdata';
            @mkdir( $newbasepath, $CFG->directorypermissions, true );
            foreach ($subs as $sub) {
                $oldpath = $oldbasepath . '/' . $sub->userid . '/' . $sub->id;
                $newpath = $newbasepath . '/' . $sub->userid;
                @mkdir( $newpath, $CFG->directorypermissions, true );
                $newpath .= '/' . $sub->id;
                if (file_exists( $oldpath )) {
                    rename( $oldpath, $newpath );
                }
                $olddir = $newpath . '/submitedfiles';
                $oldfile = $newpath . '/submitedfilelist.txt';
                $newdir = $newpath . '/submittedfiles';
                $newfile = $newpath . '/submittedfiles.lst';
                if (file_exists( $olddir )) {
                    rename( $olddir, $newdir );
                }
                if (file_exists( $oldfile )) {
                    rename( $oldfile, $newfile );
                }
            }
        }
        // VPL savepoint reached.
        upgrade_mod_savepoint( true, 2012060112, 'vpl' );
    }
    if ($oldversion < 2012100212) {

        $table = new xmldb_table( 'vpl_jailservers' );
        $field = new xmldb_field( 'nbusy', XMLDB_TYPE_INTEGER, '10', ! XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'nrequests' );

        // Conditionally launch add field nbusy.
        if (! $dbman->field_exists( $table, $field )) {
            $dbman->add_field( $table, $field );
        }

        // VPL savepoint reached.
        upgrade_mod_savepoint( true, 2012100212, 'vpl' );
    }
    if ($oldversion < 2013111512) {
        // Define table vpl_running_processes to be created.
        $table = new xmldb_table( 'vpl_running_processes' );

        // Adding fields to table vpl_running_processes.
        $table->add_field( 'id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null );
        $table->add_field( 'userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null );
        $table->add_field( 'vpl', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null );
        $table->add_field( 'server', XMLDB_TYPE_CHAR, '256', null, XMLDB_NOTNULL, null, null );
        $table->add_field( 'start_time', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null );
        $table->add_field( 'adminticket', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null );

        // Adding keys to table vpl_running_processes.
        $table->add_key( 'primary', XMLDB_KEY_PRIMARY, array (
                'id'
        ) );

        // Adding indexes to table vpl_running_processes.
        $table->add_index( 'userid_id', XMLDB_INDEX_UNIQUE, array (
                'userid',
                'id'
        ) );

        // Conditionally launch create table for vpl_running_processes.
        if (! $dbman->table_exists( $table )) {
            $dbman->create_table( $table );
        }

        // VPL savepoint reached.
        upgrade_mod_savepoint( true, 2013111512, 'vpl' );
    }
    return true;
}

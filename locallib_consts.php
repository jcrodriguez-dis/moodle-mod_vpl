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
 * Common constant definitions for the VPL module.
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

/**
 * @var string VPL Table name for VPL instances.
 *
 * This table stores the configuration and metadata for each VPL instance,
 * including the course ID, name, description, and other relevant details.
 */
const VPL = 'vpl';

/**
 * @var string VPL_SUBMISSIONS Table name for VPL submissions.
 *
 * This table stores the submissions made by users in a VPL instance,
 * including the user ID, submission time, and other relevant details.
 */
const VPL_SUBMISSIONS = 'vpl_submissions';

/**
 * @var string VPL_JAILSERVERS Table name for jail servers.
 *
 * This table stores information about the jail servers used in the VPL module,
 * including their URL and status.
 */
const VPL_JAILSERVERS = 'vpl_jailservers';

/**
 * @var string VPL_RUNNING_PROCESSES Table name for running processes.
 *
 * This table stores information about processes that are currently running
 * in the VPL module, including their status, start time, and other relevant details.
 */
const VPL_RUNNING_PROCESSES = 'vpl_running_processes';

/**
 * @var string VPL_VARIATIONS Table name for variations.
 *
 * This table stores the variations that can be applied to a VPL instance,
 */
const VPL_VARIATIONS = 'vpl_variations';

/**
 * @var string VPL_ASSIGNED_VARIATIONS Table name for assigned variations.
 *
 * This table stores the variations that have been assigned to specific users or groups
 * in a VPL instance.
 */
const VPL_ASSIGNED_VARIATIONS = 'vpl_assigned_variations';

/**
 * @var string VPL_OVERRIDES Table name for VPL overrides.
 *
 * This table stores the overrides that can be applied to a VPL instance,
 * such as due dates or submission settings.
 */
const VPL_OVERRIDES = 'vpl_overrides';

/**
 * @var string VPL_ASSIGNED_OVERRIDES Table name for assigned overrides.
 *
 * This table stores the overrides that have been assigned to specific users or groups
 * in a VPL instance.
 */
const VPL_ASSIGNED_OVERRIDES = 'vpl_assigned_overrides';

/**
 * @var string VPL_GRADE_CAPABILITY Capability string for grading VPL.
 *
 * This capability allows users to grade submissions in a VPL instance.
 */
const VPL_GRADE_CAPABILITY = 'mod/vpl:grade';

/**
 * @var string VPL_EDITOTHERSGRADES_CAPABILITY Capability string for editing grades set by others.
 *
 * This capability allows users to edit the grades set by other users in a VPL instance.
 */
const VPL_EDITOTHERSGRADES_CAPABILITY = 'mod/vpl:editothersgrades';

/**
 * @var string VPL_VIEW_CAPABILITY Capability string for viewing a VPL instance description.
 *
 * This capability allows users to view the description and details of a VPL instance.
 */
const VPL_VIEW_CAPABILITY = 'mod/vpl:view';

/**
 * @var string VPL_SUBMIT_CAPABILITY Capability string for submitting VPL.
 *
 * This capability allows users to submit their work to the VPL module.
 */
const VPL_SUBMIT_CAPABILITY = 'mod/vpl:submit';

/**
 * @var string VPL_SIMILARITY_CAPABILITY Capability string for similarity.
 *
 * This capability allows users to access and use the similarity features of the VPL module.
 */
const VPL_SIMILARITY_CAPABILITY = 'mod/vpl:similarity';

/**
 * @var string VPL_ADDINSTANCE_CAPABILITY Capability string for adding a new VPL instance.
 *
 * This capability allows users to add new instances of the VPL module in Moodle.
 */
const VPL_ADDINSTANCE_CAPABILITY = 'mod/vpl:addinstance';

/**
 * @var string VPL_SETJAILS_CAPABILITY Capability string for setting local jails.
 *
 * This capability allows users to set up and manage local jails for VPL instances.
 */
const VPL_SETJAILS_CAPABILITY = 'mod/vpl:setjails';

/**
 * @var string VPL_MANAGE_CAPABILITY Capability string for managing VPL.
 *
 * This capability allows users to manage VPL instances, including creating,
 * editing, and deleting them.
 */
const VPL_MANAGE_CAPABILITY = 'mod/vpl:manage';

/**
 * @var string VPL_EVENT_TYPE_SUBMIT Event type string for due date.
 */
const VPL_EVENT_TYPE_DUE = 'duedate';

/**
 * @var int VPL_LOCK_TIMEOUT Time in seconds to wait for a lock.
 */
const VPL_LOCK_TIMEOUT = 10;

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Comment area class.
 *
 * @package   assignsubmission_comments
 * @copyright 2021 TU Berlin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_comments;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');


/**
 * Comment area class.
 *
 * @package   assignsubmission_comments
 * @copyright 2021 TU Berlin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class area extends \core_comment\area {
    /**
     * This function is called when the (most recent) comments in an area are fetched.
     *
     * If users should be able to view comments in all sections withing an area, the option 'viewallcommentsinarea'
     * in the component's db/comments.php file should be enabled.
     *
     * This function should return the SQL clauses in order to limit a query to only fetch
     * comments from sections (with specific itemids) that $user is allowed to view. By default, this function does not
     * limit the query.
     *
     * @param \stdClass $user
     * @param int[] context ids (contains at least $this->context->id and possibly child contexts of that)
     * @return \core\dml\sql_join with three elements (joins, where, params)
     *     1. joins: any joins with other tables that are needed.
     *     2. wheres: WHERE clauses
     *     3. params: array of placeholder values that are needed by the SQL. You must
     *        use named placeholders, and the placeholder names should start with the
     *        plugin name, to avoid collisions.
     */
    public function get_comments_sql_join(\stdClass $user, array $contextids) : \core\dml\sql_join {
        global $DB;

        // Argument $contextids must only contain one context.
        if (count($contextids) != 1 || current($contextids) != $this->context->id) {
            throw new \coding_exception('assignsubmission_comments does not support fetching comments from child contexts');
        }

        if (has_any_capability(['mod/assign:viewgrades', 'mod/assign:grade'], $this->context, $user)) {
            // User is allowed to view all comment sections.
            return new \core\dml\sql_join('', '1 = 1', []);
        } else {
            // User can only view own submissions.
            $assignment = new \assign($this->context, null, $this->course);
            $submissions = $assignment->get_all_submissions($user->id);
            $submissionids = array_column($submissions, 'id');
            if (count($submissionids) == 0) {
                // User has no submissions and can view no comment section.
                return new \core\dml\sql_join('', '1 = 2', [], true);
            }
            [$insql, $inparams] = $DB->get_in_or_equal($submissionids, SQL_PARAMS_NAMED, 'assignsubmission_comments');
            return new \core\dml\sql_join('', 'c.itemid ' . $insql, $inparams);
        }
    }
}

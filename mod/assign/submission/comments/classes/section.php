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
 * Comment section class.
 *
 * @package   assignsubmission_comments
 * @copyright 2021 TU Berlin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignsubmission_comments;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

use core_comment\area;
use core_comment\comment;
use core_comment\comment_search;

/**
 * Comment section class.
 *
 * @package   assignsubmission_comments
 * @copyright 2021 TU Berlin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends \core_comment\section {
    /** @var \assign */
    public $assignment;

    /** @var \stdClass  */
    public $submission;

    /** @var bool blind marking enabled in assignment */
    public $isblindmarking;

    /** @var array user id -> unique id */
    protected $cacheduniqueids = [];

    /** @var string|null */
    protected $cacheditemtitle = null;

    /**
     * Comment section constructor.
     *
     * Optionally, an item object that belongs to the item id can be passed in order to avoid fetching
     * more information from the database. When a subclass makes use of $item, it has to check in the
     * constructor whether $item is the expected object.
     *
     * @param area $area
     * @param int $itemid
     * @param mixed $item
     */
    public function __construct(area $area, int $itemid, $item = null) {
        global $DB;

        parent::__construct($area, $itemid, $item);

        if (isset($this->item['submission']) && isset($this->item['assignment'])) {
            $this->submission = $this->item['submission'];
            $this->assignment = $this->item['assignment'];
        } else {
            if (!$this->submission = $DB->get_record('assign_submission', array('id' => $itemid))) {
                throw new \comment_exception('invalidcommentitemid');
            }
            $this->assignment = new \assign($this->area->get_context(), null, $this->area->get_course());
        }

        if ($this->assignment->get_instance()->id != $this->submission->assignment) {
            throw new \comment_exception('invalidcontext');
        }

        $this->isblindmarking = $this->assignment->is_blind_marking();
    }

    /**
     * Get the capability manager for a user in the section.
     *
     * @param \stdClass $user
     * @return \core_comment\capability
     */
    public function get_capability(\stdClass $user) : \core_comment\capability {
        return new class($this, $user) extends \core_comment\capability_simple {
            /** @var bool user can view real names during blind marking */
            private $viewrealidentity = false;

            public function __construct(section $section, \stdClass $user) {
                $assignment = $section->assignment;
                $submission = $section->submission;

                $teamsubmission = $assignment->get_instance()->teamsubmission;
                $canview = ($teamsubmission && $assignment->can_view_group_submission($submission->groupid)) ||
                    (!$teamsubmission && $assignment->can_view_submission($submission->userid));
                // TODO assignment->can_view_* methods only check for $USER, not the given $user

                parent::__construct($section, $user, $canview);

                if ($this->section->isblindmarking) {
                    $this->viewrealidentity =
                        has_capability('mod/assign:viewblinddetails', $this->context, $this->user) ||
                        ($teamsubmission && $assignment->can_edit_group_submission($submission->groupid)) ||
                        (!$teamsubmission && $submission->userid == $this->user->id);
                }
            }

            public function can_view_real_identity(comment $comment) : bool {
                if ($this->section->isblindmarking) {
                    return $comment->is_owned_by_user($this->user->id) || $this->viewrealidentity;
                }
                return false;
            }
        };
    }

    /**
     * Get a title that shortly describes the item belonging to the item id.
     *
     * This title is displayed in notifications and the list of recent comments.
     *
     * @return string
     */
    public function get_item_title() : string {
        if ($this->cacheditemtitle === null) {
            if ($this->assignment->get_instance()->teamsubmission) {
                $group = groups_get_group($this->submission->groupid, 'name');
                $this->cacheditemtitle = ($group !== false) ? $group->name : '';
            } else {
                $user = \core_user::get_user($this->submission->userid, implode(',', \core_user\fields::get_name_fields()));
                $this->cacheditemtitle = ($user !== false) ? $this->assignment->fullname($user) : '';
            }
        }

        return $this->cacheditemtitle;
    }


    /**
     * Get a URL to the item beloging to the item id.
     *
     * This title is displayed in notifications and the list of recent comments.
     *
     * @return \moodle_url
     */
    public function get_item_url() : \moodle_url {
        return new \moodle_url('/mod/assign/view.php', ['id' => $this->assignment->get_course_module()->id,
            'action' => 'viewsubmission', 'sid' => $this->itemid]);
    }

    /**
     * Construct a comment object from database data.
     *
     * @param \stdClass $record db data
     * @param comment_search|null $search the search this comment
     * @return comment
     */
    public function construct_comment_from_db(\stdClass $record, ?comment_search $search = null) : comment {
        $comment = comment::construct_from_db($this, $record, $search);

        if ($this->isblindmarking) {
            $userid = $comment->get_usercreated_id(true);
            if (!isset($this->cacheduniqueids[$userid])) {
                $this->cacheduniqueids[$userid] = $this->assignment->get_uniqueid_for_user($userid);
            }

            $name = get_string('blindmarkingname', 'assignsubmission_comments', $this->cacheduniqueids[$userid]);
            $comment->set_temporary_pseudonym($name);
        }
        return $comment;
    }
}

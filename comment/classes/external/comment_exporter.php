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
 * Exporting a comment.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_comment\external;

defined('MOODLE_INTERNAL') || die();

use core_comment\capability;
use core_comment\comment;
use renderer_base;
use stdClass;

/**
 * Class for exporting a comment.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_exporter extends \core\external\exporter {
    /** @var \core_comment\comment The comment. */
    protected $comment;

    /** @var \core_comment\section */
    protected $section;

    /** @var \core_comment\capability */
    protected $capability;

    public function __construct(comment $comment) {
        global $USER;

        $this->comment = $comment;
        $this->section = $this->comment->get_section();
        $this->capability = $this->section->get_capability($USER);

        $data = new stdClass();
        $data->component = $this->section->get_area()->get_component();
        $data->commentarea = $this->section->get_area()->get_area();
        $data->itemid = $this->section->get_item_id();
        $data->contextid = $this->section->get_context()->id;
        $data->id = $comment->get_id();
        $data->pseudonymous = $comment->is_pseudonymous_author();
        $data->replytoid = $comment->get_replytoid();
        $data->content = $comment->get_content();
        $data->contentformat = $comment->get_content_format();
        $data->customdata = $this->section->export_comment_custom_data_json($comment, $this->capability);
        $related = array(
            'context' => $this->section->get_context()
        );
        parent::__construct($data, $related);
    }

    protected static function define_related() {
        return array('context' => 'context');
    }

    protected static function define_properties() {
        return array(
            'component' => array(
                'type' => PARAM_COMPONENT,
            ),
            'commentarea' => array(
                'type' => PARAM_AREA,
            ),
            'contextid' => array(
                'type' => PARAM_INT,
            ),
            'itemid' => array(
                'type' => PARAM_INT,
            ),
            'id' => array(
                'type' => PARAM_INT,
            ),
            'pseudonymous' => array(
                'type' => PARAM_BOOL,
            ),
            'replytoid' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ),
            'content' => array(
                'type' => PARAM_RAW,
            ),
            'contentformat' => array(
                'type' => PARAM_INT,
                'default' => FORMAT_MOODLE,
            ),
            'customdata' => array(
                'type' => PARAM_RAW,
                'default' => '',
            ),
        );
    }

    protected static function define_other_properties() {
        return array(
            'contentraw' => array(
                'type' => PARAM_RAW,
            ),
            'timecreated' => array(
                'type' => PARAM_INT,
            ),
            'timecreatedtext' => array(
                'type' => PARAM_RAW,
            ),
            'timemodified' => array(
                'type' => PARAM_INT,
            ),
            'timemodifiedtext' => array(
                'type' => PARAM_RAW,
            ),
            'strftimeformat' => array(
                'type' => PARAM_RAW,
            ),
            'profileurl' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED
            ),
            'fullname' => array(
                'type' => PARAM_RAW,
            ),
            'avatar' => array(
                'type' => PARAM_RAW,
            ),
            'replies' => array(
                'type' => PARAM_INT,
            ),
            'userid' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
            ),
            'usermodifiedid' => array(
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
            ),
            'usermodifiedfullname' => array(
                'type' => PARAM_RAW,
            ),
            'isown' => array(
                'type' => PARAM_BOOL,
            ),
            'canreply' => array(
                'type' => PARAM_BOOL,
            ),
            'allowpseudonymreply' => array(
                'type' => PARAM_BOOL,
            ),
            'allowrealnamereply' => array(
                'type' => PARAM_BOOL,
            ),
            'canedit' => array(
                'type' => PARAM_BOOL,
            ),
            'candelete' => array(
                'type' => PARAM_BOOL,
            ),
            'delete' => array( // deprecated
                'type' => PARAM_BOOL,
                'description' => 'deprecated, replaced by candelete',
            ),
            'time' => array( // deprecated
                'type' => PARAM_RAW,
                'description' => 'deprecated, replaced by timecreatedtext',
            ),
            'format' => array( // deprecated
                'type' => PARAM_INT,
                'description' => 'deprecated, replaced by contentformat',
            ),
        );
    }

    public function get_other_values(renderer_base $output) {
        global $USER;

        $viewrealidentity = !$this->comment->is_pseudonymous_author() || $this->capability->can_view_real_identity($this->comment);
        $values = array();

        $values['contentraw'] = $this->comment->get_content();
        $values['strftimeformat'] = get_string('strftimerecentfull', 'langconfig');
        $values['time'] = $values['timecreated'] = $this->comment->get_timecreated();
        $values['timecreatedtext'] = userdate($values['timecreated'], $values['strftimeformat']);
        $values['timemodified'] = $this->comment->get_timemodified();
        $values['timemodifiedtext'] = userdate($values['timemodified'], $values['strftimeformat']);
        $usercreated = $this->comment->get_usercreated($viewrealidentity);
        $values['profileurl'] = null;
        $values['userid'] = null;
        if ($viewrealidentity) {
            $courseid = $this->section->get_area()->get_course_id();
            $url = new \moodle_url('/user/view.php', array('id' => $usercreated->id, 'course' => $courseid));
            $values['profileurl'] = $url->out(false);
            $values['userid'] = $usercreated->id;
        }
        //$values['usercreatedfullname'] = $this->comment->get_usercreated_fullname(); TODO remove?
        $values['usermodifiedid'] = null;
        $usermodifiedid = $this->comment->get_usermodified_id();
        if ($usercreated->id != $usermodifiedid || $viewrealidentity) {
            $values['usermodifiedid'] = $usermodifiedid;
        }
        $values['usermodifiedfullname'] = $this->comment->get_usermodified_fullname($viewrealidentity);
        $values['fullname'] = $this->comment->get_usercreated_fullname($viewrealidentity);
        $values['avatar'] = $output->user_picture($usercreated, array(
            'size' => 35,
            'link' => $viewrealidentity
        ));
        $values['replies'] = $this->comment->get_replies();
        $values['isown'] = $this->comment->is_owned_by_user($USER->id);
        $values['allowpseudonymreply'] = $this->capability->can_post(capability::POST_PSEUDONYM, $this->comment);
        $values['allowrealnamereply'] = $this->capability->can_post(capability::POST_REALNAME, $this->comment);
        $values['canreply'] = $values['allowpseudonymreply'] || $values['allowrealnamereply'];
        $values['canedit'] = $this->capability->can_edit($this->comment);
        $values['delete'] = $values['candelete'] = $this->capability->can_delete($this->comment);
        $values['format'] = $this->comment->get_content_format();
        return $values;
    }
}

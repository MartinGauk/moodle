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
 * Exporting a comment section.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_comment\external;
defined('MOODLE_INTERNAL') || die();

use core_comment\capability;
use core_comment\section;
use core_comment\subscription;
use core_comment_external;
use renderer_base;
use stdClass;

/**
 * Class for exporting a comment section.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_section_exporter extends \core\external\exporter {

    /** @var section The comment section. */
    protected $section = null;

    public function __construct(section $section, $related = array()) {
        $this->section = $section;
        $data = new stdClass();
        $data->component = $section->get_area()->get_component();
        $data->commentarea = $section->get_area()->get_area();
        $data->itemid = $section->get_item_id();
        $data->contextid = $section->get_context()->id;

        parent::__construct($data, $related);
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
        );
    }

    protected static function define_other_properties() {
        return array(
            'canpost' => array(
                'type' => PARAM_BOOL,
            ),
            'allowpseudonym' => array(
                'type' => PARAM_BOOL,
            ),
            'allowrealname' => array(
                'type' => PARAM_BOOL,
            ),
            'subscription' => array(
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
            ),
            'subscriptiondefault' => array(
                'type' => PARAM_RAW,
            ),
            'itemtitle' => array(
                'type' => PARAM_RAW,
            ),
            'itemurl' => array(
                'type' => PARAM_RAW,
            ),
            'renderoptions' => array(
                'type' => ['key' => ['type' => PARAM_RAW], 'value' => ['type' => PARAM_RAW]],
                'multiple' => true,
            )
        );
    }

    public function get_other_values(renderer_base $output) {
        global $USER;
        $values = array();
        $cap = $this->section->get_capability($USER);
        $values['allowpseudonym'] = $cap->can_post(capability::POST_PSEUDONYM);
        $values['allowrealname'] = $cap->can_post(capability::POST_REALNAME);
        $values['canpost'] = $values['allowpseudonym'] || $values['allowrealname'];
        $values['subscription'] = core_comment_external::format_subscription(subscription::get_subscription_status($USER, $this->section));
        $values['subscriptiondefault'] = core_comment_external::format_subscription($this->section->get_default_subscription_status($USER));
        $values['itemtitle'] = $this->section->get_item_title();
        $values['itemurl'] = $this->section->get_item_url()->out(false);
        $renderoptions = $this->section->get_section_render_options();
        array_walk($renderoptions, function (&$value, $key) {
            $value = ['key' => $key, 'value' => $value];
        });
        $values['renderoptions'] = array_values($renderoptions);
        return $values;
    }
}

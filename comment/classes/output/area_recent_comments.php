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
 * Recent comments in area renderable.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Recent comments in area renderable.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class area_recent_comments implements \renderable, \templatable {

    // TODO docs
    public $area;
    public $displayoptions;

    /**
     * Recent comments in area renderable constructor.
     *
     * @param \core_comment\area $area
     * @param \stdClass $displayoptions Object with the following optional properties:
     * <ul>
     * <li>int <b>displaymode</b>: One of \core_comment\output\renderer::DISPLAYMODE_*. Defaults to DISPLAYMODE_MODAL.</li>
     * <li>bool <b>fillheight</b>: Whether the comment section will fill the available height. Defaults to true for modals.</li>
     * <li>bool <b>startfrombottom</b>: Whether the comment form is below the comment list. Defaults to true if fillheight is true.</li>
     * <li>string <b>sortdirection</b>: One of 'ASC' and 'DESC'. Comments are sorted based on creation date. Defaults to 'DESC'.</li>
     * <li>int <b>maxlistheight</b>: Maximum height of the list of comments in pixels.</li>
     * <li>int <b>pagesize</b>: Number of comments to load at the same time.</li>
     * </ul>
     */
    public function __construct(\core_comment\area $area, \stdClass $displayoptions = null) {
        $this->area = $area;
        $this->displayoptions = $displayoptions;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $renderer the render to be used for formatting the message
     * @param bool $plaintext whether the target is a plaintext target
     * @return array data ready for use in a mustache template
     */
    public function export_for_template(\renderer_base $renderer) : array {
        // TODO
        return [];
    }
}

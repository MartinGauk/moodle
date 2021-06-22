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
 * Comment section renderable.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Comment section renderable.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section implements \renderable, \templatable {
    /**
     * Comment section renderable constructor.
     *
     * @param \core_comment\section $section
     */
    public function __construct(\core_comment\section $section) {
        // TODO
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
    }
}

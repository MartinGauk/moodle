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
 * Contains renderer class.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer class.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    // TODO docs
    const DISPLAYMODE_INLINE = 0;
    const DISPLAYMODE_MODAL = 1;

    /**
     * Render a comment section.
     *
     * @param comments $comments
     * @return string HTML
     * @throws \moodle_exception
     */
    public function render_comments(\core_comment\output\comments $comments): string {
        $a = $comments->export_for_template($this);
        return $this->output->render_from_template('core_comment/comments', $a);
    }

}

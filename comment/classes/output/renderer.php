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
    /**
     * Render a comment section.
     *
     * @param section $section
     * @return string HTML
     */
    public function render_section(\core_comment\output\section $section) : string {
        $this->page->requires->js_call_amd('core_comment/comments', 'init');
        // TODO use modal?
        $ncomments = $section->section->count_comments();
        $text = $ncomments ? "Show {$ncomments} comment(s)" : "No comments";
        return "<a href='#'
                    data-commentsection
                    data-modal
                    data-contextid='{$section->contextid}' 
                    data-component='{$section->component}'
                    data-commentarea='{$section->commentarea}'
                    data-itemid='{$section->itemid}'>{$text}</a>"; // TODO localize
    }

    /**
     * Render recent comments in a comment area.
     *
     * @param area_recent_comments $arearecentcomments
     * @return string HTML
     */
    public function render_area_recent_comments(\core_comment\output\area_recent_comments $arearecentcomments) : string {
        // TODO
    }

}

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
     * @param section $section
     * @return string HTML
     * @throws \comment_exception
     */
    public function render_section(\core_comment\output\section $section) : string {
        global $USER;
        $this->page->requires->js_call_amd('core_comment/comments', 'init');
        $contextid = $section->section->get_context()->id;
        $component = $section->section->get_area()->get_component();
        $area = $section->section->get_area()->get_area();
        $itemid = $section->section->get_item_id();
        switch ($section->displaymode) {
            case self::DISPLAYMODE_MODAL:
                $ncomments = $section->section->count_comments($USER);
                if ($ncomments) {
                    $text = get_string('showcomments', 'core_comment', $ncomments);
                } else {
                    $text = get_string('shownocomments', 'core_comment');
                }
                return "<a href='#'
                    data-commentsection
                    data-modal
                    data-contextid='{$contextid}' 
                    data-component='{$component}'
                    data-commentarea='{$area}'
                    data-itemid='{$itemid}'>{$text}</a>";
            case self::DISPLAYMODE_INLINE:
                return "<div
                    data-commentsection
                    data-contextid='{$contextid}' 
                    data-component='{$component}'
                    data-commentarea='{$area}'
                    data-itemid='{$itemid}'></div>";
            default:
                throw new \comment_exception('unknowndisplaymode'); // TODO localize
        }
    }

    /**
     * Render recent comments in a comment area.
     *
     * @param area_recent_comments $arearecentcomments
     * @return string HTML
     */
    public function render_area_recent_comments(\core_comment\output\area_recent_comments $arearecentcomments) : string {
        global $USER;
        $this->page->requires->js_call_amd('core_comment/comments', 'init');
        $contextid = $arearecentcomments->area->get_context()->id;
        $component = $arearecentcomments->area->get_component();
        $area = $arearecentcomments->area->get_area();
        switch ($arearecentcomments->displaymode) {
            case self::DISPLAYMODE_MODAL:
                $ncomments = $arearecentcomments->area->count_comments_in_area($USER);
                if ($ncomments) {
                    $text = get_string('showrecentcomments', 'core_comment', $ncomments);
                } else {
                    $text = get_string('shownorecentcomments', 'core_comment');
                }
                return "<a href='#'
                    data-commentsection
                    data-modal
                    data-contextid='{$contextid}' 
                    data-component='{$component}'
                    data-commentarea='{$area}'
                    data-sortdirection='DESC'
                    data-startatbottom='false'>{$text}</a>";
            case self::DISPLAYMODE_INLINE:
                return "<div
                    data-commentsection
                    data-contextid='{$contextid}' 
                    data-component='{$component}'
                    data-commentarea='{$area}'
                    data-sortdirection='DESC'
                    data-startatbottom='false'></div>";
            default:
                throw new \comment_exception('unknowndisplaymode'); // TODO localize
        }
    }

}

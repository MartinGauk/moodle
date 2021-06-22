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
 * Comments digest renderable.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Comments digest renderable.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class digest implements \renderable, \templatable {
    /**
     * Digest renderable constructor.
     *
     * @param \stdClass $userto
     */
    public function __construct(\stdClass $userto) {
        // TODO
    }

    /**
     * Add a comment to the digest.
     *
     * @param \core_comment\comment_search $search
     * @param \core_comment\comment $comment
     */
    public function add_comment(\core_comment\comment_search $search, \core_comment\comment $comment) {
        // TODO
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $renderer the render to be used for formatting the message
     * @param bool $plaintext whether the target is a plaintext target
     * @return array data ready for use in a mustache template
     */
    public function export_for_template(\renderer_base $renderer, $plaintext = false) : array {
        // TODO common variables
        $data = [];

        if ($plaintext) {
            return $data + $this->export_for_template_text($renderer);
        } else {
            return $data + $this->export_for_template_html($renderer);
        }
    }

    /**
     * Export this data so it can be used as the context for a mustache template in a text mail.
     *
     * @param \renderer_base $renderer The render to be used for formatting the message and attachments
     * @return array Data ready for use in a mustache template
     */
    protected function export_for_template_text(\mod_forum_renderer $renderer) : array {
        // TODO
    }

    /**
     * Export this data so it can be used as the context for a mustache template in an html mail.
     *
     * @param \renderer_base $renderer The render to be used for formatting the message and attachments
     * @return array Data ready for use in a mustache template
     */
    protected function export_for_template_html(\mod_forum_renderer $renderer) : array {
        // TODO
    }
}

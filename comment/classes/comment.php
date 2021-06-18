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
 * Class that represents a comment.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment;

defined('MOODLE_INTERNAL') || die();

/**
 * Class that represents a comment.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment {
    /** @var section comment section this comment belongs to */
    protected $section;

    /** @var int|null id of the comment in the database (null if not yet inserted) */
    protected $id;

    /** @var string comment's text content */
    protected $content;

    /** @var int text format */
    protected $format;

    /** @var int id of the user who posted this comment */
    protected $usercreated;

    /** @var int id of the user who last modified this comment */
    protected $usermodified;

    /** @var string|null pseudonym specified when the comment was posted */
    protected $pseudonym;

    /** @var int time when this comment was posted */
    protected $timecreated;

    /** @var int time when this comment was last modified */
    protected $timemodified;

    /** @var int id of the comment this is a reply to */
    protected $replytoid;

    /** @var int number of replies to this comment */
    protected $replies;

    /** @var int number of upvotes on this comment */
    protected $upvotes;

    /** @var array custom data associated with this comment */
    protected $customdata;

    /**
     * Comment constructor.
     * @param section $section
     */
    protected function __construct(section $section) {
        $this->section = $section;
    }

    /**
     * Construct a comment object from database data.
     *
     * @param section $section
     * @param \stdClass $record db data
     */
    static public function construct_from_db(section $section, \stdClass $record) {
        $comment = new comment($section);
        $comment->id = $record->id;
        // TODO
    }

    /**
     * Construct a new comment within a section.
     *
     * @param section $section
     * @param string $content
     * @param int $format
     * @param int $usercreated
     * @param string $pseudonym
     * @param int $replytoid
     * @param array $customdata
     */
    static public function construct_new(section $section, string $content, int $format, int $usercreated, string $pseudonym,
            int $replytoid, array $customdata) {
        $comment = new comment($section);
        $comment->id = null;
        // TODO
    }

    /**
     * Delete comment.
     */
    public function delete() {
        // TODO
    }

    /**
     * Save data to the database.
     */
    public function save() {
        // TODO If a db record already exists, only update content, format, usermodified, pseudonym, timemodified, customdata.
    }

    /**
     * Format the comment text for output. TODO Probably the caller has to format the text accordingly (format_text vs external_format_text)...
     *
     * @return string
     */
    public function format_text() : string {
        // TODO
    }

    /**
     * Get the user who posted the comment.
     *
     * @param bool $revealidentity return real user id even though the comment was posted under a pseudonym
     * @return int user id
     */
    public function get_usercreated(bool $revealidentity = false): int {
        // TODO
    }

    /**
     * Get the last user who modified the comment.
     *
     * @param bool $revealidentity return real user id even though the comment was posted under a pseudonym
     * @return int user id
     */
    public function get_usermodified(bool $revealidentity = false): int {
        // TODO
    }

    public function get_timecreated() : int {
        return $this->timecreated;
    }

    /**
     * Get the id of the comment that this was replied to.
     *
     * @return int|null
     */
    public function get_replytoid() : ?int {
        return $this->replytoid;
    }

    // TODO implement getters

    /**
     * Set comment content and text format.
     *
     * @param string $content
     * @param int $format
     * @param bool $updatetimeuser update timemodified and usermodified
     * @param int|null $userid if $updatetimeuser is true, supply the user who modified the comment
     */
    public function set_content(string $content, int $format) {
        $this->content = $content;
        $this->format = $format;
    }

    /**
     * Update timemodified and usermodified fields.
     *
     * @param int $userid user who modified the comment
     * @param int|null $time time to set for timemodified (current time if null)
     */
    public function update_time_user(int $userid, ?int $time = null) {
        $this->usermodified = $userid;
        $this->timemodified = $time ?? time();
    }

    // TODO more setters

    /**
     * Check if the given user authored this comment.
     *
     * @param int $userid the user to check
     * @return bool
     */
    public function is_owned_by_user(int $userid) : bool {
        return $this->usercreated === $userid;
    }

    /**
     * Get the comment's age (since creation) in seconds.
     *
     * @return int
     */
    public function get_age() : int {
        return time() - $this->timecreated;
    }

    /**
     * Did the author post the comment under a pseudonym?
     *
     * @return bool
     */
    public function is_pseudonymous_author() : bool {
        return !empty($this->pseudonym);
    }

    /**
     * Move this comment (and its replies) to another comment section.
     *
     * @param section $section
     */
    public function move_to_section(section $section) {
        // TODO
    }

    /**
     * Update the vote of an user for this comment.
     *
     * @param \stdClass $user
     * @param int $vote 1 for an upvote, 0 to remove vote
     */
    public function update_vote(\stdClass $user, int $vote) {
        // TODO
    }
}

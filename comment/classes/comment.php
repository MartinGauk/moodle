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

    /** @var comment_search|null the comment search object this comment is from */
    protected $search = null;

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

    /** @var string|null custom data associated with this comment as json */
    protected $customdatajson = null;

    /** @var array|null custom data associated with this comment */
    protected $customdata = null;

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
     * @return comment
     */
    static public function construct_from_db(section $section, \stdClass $record, ?comment_search $search = null) : comment {
        $comment = new comment($section);
        $comment->search = $search;
        $comment->id = $record->id;
        $comment->content = $record->content;
        $comment->format = $record->format;
        $comment->usercreated = $record->userid;
        $comment->timecreated = $record->timecreated;
        $comment->usermodified = $record->usermodified;
        $comment->timemodified = $record->timemodified;
        $comment->pseudonym = $record->pseudonym;
        $comment->replytoid = $record->replytoid;
        $comment->replies = $record->replies;
        $comment->upvotes = $record->upvotes;
        $comment->customdatajson = $record->customdata;
        return $comment;
    }

    /**
     * Construct a new comment within a section.
     *
     * @param section $section
     * @param string $content
     * @param int $format
     * @param int $usercreated
     * @param string $pseudonym
     * @param int|null $replytoid
     * @param string $customdatajson
     * @return comment
     */
    static public function construct_new(section $section, string $content, int $format, int $usercreated,
            string $pseudonym, ?int $replytoid, string $customdatajson) : comment {
        $comment = new comment($section);
        $comment->id = null;
        $comment->content = $content;
        $comment->format = $format;
        $comment->usermodified = $comment->usercreated = $usercreated;
        $comment->timemodified = $comment->timecreated = time();
        $comment->pseudonym = $pseudonym;
        $comment->replytoid = $replytoid;
        $comment->customdatajson = $customdatajson;
        $comment->replies = 0;
        $comment->upvotes = 0;
        return $comment;
    }

    /**
     * Delete comment.
     */
    public function delete() : bool {
        global $DB;
        if (is_null($this->id)) {
            return false;
        }
        try {
            $transaction = $DB->start_delegated_transaction();

            // Delete record and replies and decrement reply counter in a transaction.
            // TODO delete replies recursively
            $DB->delete_records('comments', ['replytoid' => $this->id]);
            $DB->delete_records('comments', ['id' => $this->id]);
            if (!is_null($this->replytoid)) {
                $DB->execute('UPDATE {comments} SET replies = replies - 1 WHERE id = :replytoid', [
                    'replytoid' => $this->replytoid
                ]);
            }

            $transaction->allow_commit();
        } catch (\Exception $e) {
            if (!empty($transaction) && !$transaction->is_disposed()) {
                $transaction->rollback($e);
            }
            throw $e;
        }
        return true;
    }

    /**
     * Save data to the database.
     */
    public function save() {
        global $DB;
        $data = new \stdClass();
        $data->content = $this->content;
        $data->format = $this->format;
        $data->pseudonym = $this->pseudonym;
        $data->usermodified = $this->usermodified;
        $data->timemodified = $this->timemodified;
        $data->customdata = $this->customdatajson;

        if (!is_null($this->id)) {
            $data->id = $this->id;
            $DB->update_record('comments', $data);
        } else {
            $data->contextid = $this->get_section()->get_context()->id;
            $data->component = $this->get_section()->get_area()->get_component();
            $data->commentarea = $this->get_section()->get_area()->get_area();
            $data->itemid = $this->get_section()->get_item_id();
            $data->replytoid = $this->replytoid;
            $data->userid = $this->usercreated;
            $data->timecreated = $this->timecreated;
            $data->replies = $this->replies;
            $data->upvotes = $this->upvotes;

            try {
                $transaction = $DB->start_delegated_transaction();

                // Create record and increment reply counter in a transaction.
                $this->id = $DB->insert_record('comments', $data);
                if (!is_null($this->replytoid)) {
                    $DB->execute('UPDATE {comments} SET replies = replies + 1 WHERE id = :replytoid', [
                        'replytoid' => $this->replytoid
                    ]);
                }

                $transaction->allow_commit();
            } catch (\Exception $e) {
                if (!empty($transaction) && !$transaction->is_disposed()) {
                    $transaction->rollback($e);
                }
                throw $e;
            }
        }
    }

    /**
     * Format the comment text for output. TODO Probably the caller has to format the text accordingly (format_text vs external_format_text)...
     *
     * @return string
     */
    public function format_text() : string {
        // TODO
    }

    protected function get_user(int $id) : \stdClass {
        if ($this->search) {
            return $this->search->get_user($id);
        }
        global $DB;
        return $DB->get_record('user', array('id' => $this->usermodified), '*', MUST_EXIST);
    }

    /**
     * Get the user who posted the comment.
     *
     * @param bool $revealidentity return real user even though the comment was posted under a pseudonym
     * @return \stdClass user
     */
    public function get_usercreated(bool $revealidentity = false) : \stdClass {
        if ($this->is_pseudonymous_author() && !$revealidentity) {
            return \core_user::get_noreply_user();
        } else {
            return $this->get_user($this->usercreated);
        }
    }

    /**
     * Get the id of the user who posted the comment.
     *
     * @param bool $revealidentity return real user id even though the comment was posted under a pseudonym
     * @return int user id
     */
    public function get_usercreated_id(bool $revealidentity = false) : int {
        if ($this->is_pseudonymous_author() && !$revealidentity) {
            return \core_user::get_noreply_user()->id;
        } else {
            return $this->usercreated;
        }
    }

    /**
     * Get the full name of the user who posted the comment or the pseudonym, if set.
     *
     * @return string full user name or pseudonym
     */
    public function get_usercreated_fullname() : string {
        if ($this->is_pseudonymous_author()) {
            return $this->pseudonym;
        } else {
            return fullname($this->get_usercreated());
        }
    }

    /**
     * Get the last user who modified the comment.
     *
     * @param bool $revealidentity return real user even though the comment was posted under a pseudonym
     * @return \stdClass user
     */
    public function get_usermodified(bool $revealidentity = false) : \stdClass {
        if ($this->is_pseudonymous_author() && $this->usermodified == $this->usercreated && !$revealidentity) {
            return \core_user::get_noreply_user();
        } else {
            return $this->get_user($this->usermodified);
        }
    }

    /**
     * Get the id of the last user who modified the comment.
     *
     * @param bool $revealidentity return real user id even though the comment was posted under a pseudonym
     * @return int user id
     */
    public function get_usermodified_id(bool $revealidentity = false) : int {
        if ($this->is_pseudonymous_author() && $this->usermodified == $this->usercreated && !$revealidentity) {
            return \core_user::get_noreply_user()->id;
        } else {
            return $this->usermodified;
        }
    }

    /**
     * Get the full name of the user who last modified the comment or the pseudonym, if set.
     *
     * @return string full user name or pseudonym
     */
    public function get_usermodified_fullname() : string {
        if ($this->is_pseudonymous_author() && $this->usermodified == $this->usercreated) {
            return $this->pseudonym;
        } else {
            return fullname($this->get_usermodified());
        }
    }

    /**
     * Get the pseudonym set by the author or null, if none was set.
     *
     * @return string|null
     */
    public function get_pseudonym() : ?string {
        return $this->pseudonym;
    }

    /**
     * Get the timestamp when this comment was created.
     *
     * @return int
     */
    public function get_timecreated() : int {
        return $this->timecreated;
    }

    /**
     * Get the timestamp when this comment was last modified.
     *
     * @return int
     */
    public function get_timemodified() : int {
        return $this->timemodified;
    }

    /**
     * Get the id of the comment that this is a reply to.
     *
     * @return int|null
     */
    public function get_replytoid() : ?int {
        return $this->replytoid;
    }

    /**
     * Get the comment that this is a reply to.
     *
     * @return comment|null
     */
    public function get_replyto() : ?comment {
        if (is_null($this->replytoid)) {
            return null;
        }
        return $this->get_section()->get_comment($this->replytoid);
    }

    /**
     * Get the section this comment belongs to.
     *
     * @return section
     */
    public function get_section() : section {
        return $this->section;
    }

    /**
     * Get the comment id or null, if the comment has not been saved, yet.
     *
     * @return int|null
     */
    public function get_id() : ?int {
        return $this->id;
    }

    /**
     * Get the comment's content (unformatted).
     *
     * @return string
     */
    public function get_content() : string {
        return $this->content;
    }

    /**
     * Get the format of the comment's content.
     *
     * @return int
     */
    public function get_content_format() : int {
        return $this->format;
    }

    /**
     * Get the custom data associated with this comment as an array.
     *
     * @return array
     */
    public function get_custom_data() : array {
        if ($this->customdata === null) {
            if (strlen($this->customdatajson) == 0) {
                return [];
            }
            $this->customdata = json_decode($this->customdatajson, true);
        }
        return $this->customdata;
    }

    /**
     * Get the custom data associated with this comment as a JSON encoded string.
     *
     * @return string
     */
    public function get_custom_data_json() : string {
        if ($this->customdatajson === null) {
            if (empty($this->customdata)) {
                return "";
            }
            $this->customdatajson = json_encode($this->customdata);
        }
        return $this->customdatajson;
    }

    /**
     * Get the number of replies on this comment.
     *
     * @return int
     */
    public function get_replies() : int {
        return $this->replies;
    }

    /**
     * Get the number of upvotes on this comment.
     *
     * @return int
     */
    public function get_upvotes() : int {
        return $this->upvotes;
    }

    /**
     * Set comment content and text format.
     *
     * @param string $content
     * @param int $format
     */
    public function set_content(string $content, int $format) {
        $this->content = $content;
        $this->format = $format;
    }

    /**
     * Set the pseudonym. A pseudonym cannot be removed completely, it can only be changed.
     *
     * @param string $pseudonym
     */
    public function set_pseudonym(string $pseudonym) {
        $pseudonym = trim($pseudonym);
        if (strlen($pseudonym) == 0 && $this->is_pseudonymous_author()) {
            throw new \comment_exception('cannotremovepseudonym'); //TODO add to error.php
        }
        $this->pseudonym = $pseudonym;
    }

    /**
     * Set the custom data to the given JSON encoded array.
     *
     * @param string $customdatajson
     */
    public function set_custom_data_json(string $customdatajson) {
        $this->customdatajson = $customdatajson;
        $this->customdata = null;
    }

    /**
     * Set the custom data to the given array.
     *
     * @param array $customdata
     */
    public function set_custom_data(array $customdata) {
        $this->customdatajson = null;
        $this->customdata = $customdata;
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
     * Update the vote of a user for this comment.
     *
     * @param \stdClass $user
     * @param int $vote 1 for an upvote, 0 to remove vote
     */
    public function update_vote(\stdClass $user, int $vote) {
        // TODO
    }
}

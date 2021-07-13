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
 * Abstract class that represents a comment section in a plugin.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment;

defined('MOODLE_INTERNAL') || die();

/**
 * Capability manager for a comment section.
 *
 * Defines what a user is allowed to do within a comment section.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class capability {
    /** @var int bitmask that defines the comment post modes */
    const POST_REALNAME = 0x01;
    const POST_PSEUDONYM = 0x02;
    const POST_BOTH = self::POST_REALNAME | self::POST_PSEUDONYM;

    /** @var section comment section that these capabilities refer to */
    protected $section;

    /** @var \stdClass capabilities of this user */
    protected $user;

    /**
     * Capability constructor.
     *
     * @param section $section
     * @param \stdClass $user
     */
    public function __construct(section $section, \stdClass $user) {
        $this->section = $section;
        $this->user = $user;
    }

    /**
     * Get user.
     *
     * @return \stdClass
     */
    public function get_user() : \stdClass {
        return $this->user;
    }

    // TODO add phpdocs
    abstract public function can_view() : bool;
    abstract public function can_post(int $postmode, ?comment $replyto = null) : bool;
    abstract public function can_edit(comment $comment = null) : bool;
    abstract public function can_delete(comment $comment = null) : bool;
    abstract public function can_upvote(comment $comment = null) : bool;

    /**
     * Can the user (un)subscribe to the comment thread/comment section?
     *
     * @param int $currentstatus
     * @param int $newstatus
     * @param comment|null $comment comment thread
     * @return bool
     */
    abstract public function can_modify_subscription_status(int $currentstatus, int $newstatus, comment $comment = null) : bool;
}

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
 * This class is a convenience for most use cases.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capability_simple extends capability {
    /** @var bool user is allowed to view this section */
    protected $canview;

    /** @var bool user is allowed to reply to other comments */
    protected $allowreplies;

    /** @var bool user is allowed to upvote comments */
    protected $allowupvotes;

    /** @var bool user is allowed to post under their real name */
    protected $allowrealname;

    /** @var bool user is allowed to post under a pseudonym */
    protected $allowpseudonym;

    /** @var int allowed post modes (bitmask of the capability::POST_* constants) */
    protected $allowedpostmodes;

    /** @var bool user is allowed to subscribe to the section and comments */
    protected $allowsubscriptions;

    /** @var \context */
    protected $context;

    /**
     * Simple capability constructor.
     *
     * @param section $section
     * @param \stdClass $user
     * @param bool $canview Is the user allowed to view this comment section?
     * @param bool $allowreplies Is the user allowed to reply to other comments in this section?
     * @param bool $allowupvotes Is the user allowed to upvote other comments?
     * @param int $allowedpostmodes bitmask of the capability::POST_* constants (defining whether the user can post under
     *     the real name and/or under a pseudonym)
     * @param bool $allowsubscriptions Is the user allowed to change the subscription to the section?
     * @throws \coding_exception
     */
    public function __construct(section $section, \stdClass $user, bool $canview, bool $allowreplies = false,
            bool $allowupvotes = true, int $allowedpostmodes = self::POST_REALNAME, bool $allowsubscriptions = true) {
        parent::__construct($section, $user);
        $this->context = $section->get_context();
        $this->canview = $canview && has_capability('moodle/comment:view', $this->context, $this->user);
        $this->allowreplies = $allowreplies;
        $this->allowupvotes = $allowupvotes;
        $this->allowedpostmodes = $allowedpostmodes;
        $this->allowrealname = ($allowedpostmodes & self::POST_REALNAME) &&
            has_capability('moodle/comment:post', $this->context, $this->user);
        $this->allowpseudonym = ($allowedpostmodes & self::POST_PSEUDONYM) &&
            has_capability('moodle/comment:postpseudonym', $this->context, $this->user);
        $this->allowsubscriptions = $allowsubscriptions;
    }

    // TODO add phpdocs
    public function can_view() : bool {
        return $this->canview;
    }

    public function can_post(int $postmode, ?comment $replyto = null) : bool {
        // Check that replies are allowed when trying to reply someone. Do not allow replies to replies.
        // Check that the requested post mode is allowed.
        return $this->canview && ($replyto === null || ($this->allowreplies && $replyto->get_replytoid() === null)) && (
            ($postmode === self::POST_REALNAME && $this->allowrealname) ||
            ($postmode === self::POST_PSEUDONYM && $this->allowpseudonym)
        );
    }

    public function can_edit(comment $comment = null) : bool {
        global $CFG;

        return $this->canview && (
            ($comment->is_owned_by_user($this->user->id) && $comment->get_age() < $CFG->maxeditingtime) ||
            has_capability('moodle/comment:editany', $this->context, $this->user)
        );
    }

    public function can_delete(comment $comment = null) : bool {
        return $this->canview && has_capability('moodle/comment:delete', $this->context, $this->user);
    }

    public function can_upvote(comment $comment = null) : bool {
        return $this->canview && $this->allowupvotes && $comment->get_usercreated(true) !== $this->user->id;
    }

    /**
     * Can the user (un)subscribe to the comment section?
     *
     * @param int $currentstatus
     * @return bool
     */
    public function can_modify_subscription_status(int $currentstatus) : bool {
        return $this->canview && $this->allowsubscriptions;
    }
}

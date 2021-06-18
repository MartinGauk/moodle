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
 * Collection that stores the fetched comments.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment;

defined('MOODLE_INTERNAL') || die();

/**
 * Collection that stores the fetched comments.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comments_found implements \IteratorAggregate {
    /** @var comment[] comments in this collection */
    protected $comments;

    /**
     * Constructor.
     *
     * @param comment[] $comments
     */
    public function __construct(array $comments) {
        $this->comments = $comments;
    }

    /**
     * Get an iterator to walk through the fetched comments.
     *
     * @return \Traversable|void
     */
    public function getIterator() {
        // TODO
    }

    /**
     * Get a user by its id.
     *
     * When first called, it fetches the user data from all users in this collection.
     *
     * @param int $userid
     * @return \stdClass user record
     */
    public function get_user(int $userid) : \stdClass {
        // TODO
    }

    /**
     * Count the number of comments that are in this collection.
     *
     * @param bool $includingreplies also include the number of replies to the fetched comments
     * @return int
     */
    public function count(bool $includingreplies = true): int {
        // TODO
    }

    /**
     * Get the first comment in this collection.
     *
     * This is a helper to easily get a comment when you know there is only one
     * (as it is when you fetch one comment by its id).
     *
     * @return \comment
     */
    public function get_first() : \comment {
        // TDOO
    }
}

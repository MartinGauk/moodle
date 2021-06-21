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
 * Comment search.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment;

defined('MOODLE_INTERNAL') || die();

/**
 * Comment search.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_search implements \IteratorAggregate {

    // TODO docs
    protected $area;
    protected $section;
    protected $timefrom;
    protected $timeto;
    protected $page;
    protected $pagesize;
    protected $sortdirection;
    protected $includechildcontexts;
    protected $user;

    protected $results;
    protected $count;
    protected $countwithreplies;
    protected $totalcount;
    protected $totalcountwithreplies;
    protected $loadedusers;

    /**
     * Constructor.
     *
     * Pass a user if only comments should be returned that this user is allowed to view.
     *
     * @param area $area comments from this area
     * @param section|null $section comments from this section
     * @param int|null $timefrom only comments that were created at or after this time
     * @param int|null $timeto only comments that were created at or before this time
     * @param int $page
     * @param int $pagesize maximum number of comments to fetch
     * @param string $sortdirection ASC or DESC (comments are ordered by timecreated)
     * @param bool $includechildcontexts also fetch comments from child contexts (only if the context is a \course_context)
     * @param \stdClass|null $user
     */
    public function __construct(area $area, ?section $section = null, ?int $timefrom = null, ?int $timeto = null,
                                int $page = 0, int $pagesize = -1, string $sortdirection = 'DESC',
                                bool $includechildcontexts = false, ?\stdClass $user = null) {
        $this->area = $area;
        $this->section = $section;
        $this->timefrom = $timefrom;
        $this->timeto = $timeto;
        $this->page = $page;
        $this->pagesize = $pagesize;
        $this->sortdirection = $sortdirection;
        $this->includechildcontexts = $includechildcontexts;
        $this->user = $user;
    }

    // TODO getters

    /**
     * Get an iterator to walk through the fetched comments.
     *
     * @return \Traversable|void
     */
    public function getIterator() {
        // TODO
    }

    /**
     * Fetch all matching comments.
     *
     * @return comment[] The matching comments.
     */
    public function fetch() : array {
        if ($this->results) {
            return $this->results;
        }
        // TODO fetch results
    }

    /**
     * Get search object for the next page if there are comments remaining, null otherwise.
     *
     * @return comment_search|null
     */
    public function next_page() : ?comment_search {
        if ($this->pagesize > 0 && ($this->page + 1) * $this->pagesize < $this->count_total(false)) {
            $nextpage = new comment_search($this->area, $this->section, $this->timefrom, $this->timeto,
                $this->page + 1, $this->pagesize, $this->sortdirection, $this->includechildcontexts, $this->user);
            $nextpage->totalcount = $this->totalcount;
            $nextpage->loadedusers = $this->loadedusers;
            return $nextpage;
        }
        return null;
    }

    /**
     * Get a user by its id.
     *
     * When first called, it fetches the user data from all users in the search results.
     *
     * @param int $userid
     * @return \stdClass user record
     */
    public function get_user(int $userid) : \stdClass {
        if (array_key_exists($userid, $this->loadedusers)) {
            return $this->loadedusers[$userid];
        }
        // TODO load users
    }

    /**
     * Count the number of matched comments on the current page.
     *
     * @param bool $includingreplies also include the number of replies to the fetched comments
     * @return int
     */
    public function count(bool $includingreplies = true) : int {
        // TODO
    }

    /**
     * Count the number of matched comments on all pages.
     *
     * @param bool $includingreplies also include the number of replies to the fetched comments
     * @return int
     */
    public function count_total(bool $includingreplies = true) : int {
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
        // TODO
    }
}

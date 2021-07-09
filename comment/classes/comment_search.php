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
    protected $replytoid;
    protected $timefrom;
    protected $timeto;
    protected $page;
    protected $pagesize;
    protected $sortdirection;
    protected $includechildcontexts;
    protected $includereplies;
    protected $user;

    /** @var array array of preloaded results */
    protected $results;
    protected $totalcount;
    protected $totalcountwithreplies;
    protected $loadedusers = [];

    /**
     * Constructor.
     *
     * Pass a user if only comments should be returned that this user is allowed to view.
     *
     * @param area $area comments from this area
     * @param section|null $section comments from this section
     * @param int|null $replytoid comments that are replies to this comment
     * @param int|null $timefrom only comments that were created at or after this time
     * @param int|null $timeto only comments that were created at or before this time
     * @param int $page
     * @param int $pagesize maximum number of comments to fetch
     * @param string $sortdirection ASC or DESC (comments are ordered by timecreated)
     * @param bool $includechildcontexts also fetch comments from child contexts (only if the context is a \course_context)
     * @param bool $includereplies include all replies. Cannot be used combined with replytoid.
     * @param \stdClass|null $user
     */
    public function __construct(area $area, ?section $section = null, ?int $replytoid = null,
                                ?int $timefrom = null, ?int $timeto = null,
                                int $page = 0, int $pagesize = -1, string $sortdirection = 'DESC',
                                bool $includechildcontexts = false, bool $includereplies = false,
                                ?\stdClass $user = null) {
        $this->area = $area;
        $this->section = $section;
        $this->replytoid = $replytoid;
        $this->timefrom = $timefrom;
        $this->timeto = $timeto;
        $this->page = $page;
        $this->pagesize = $pagesize;
        $this->sortdirection = $sortdirection;
        $this->includechildcontexts = $includechildcontexts;
        $this->includereplies = $includereplies;
        $this->user = $user;
    }

    // TODO getters

    /**
     * Get an iterator to walk through the fetched comments.
     *
     * @return \Traversable|void
     */
    public function getIterator() {
        return new \ArrayIterator($this->get_all());
        // TODO use recordset if count too large? or if pagesize too large?
        // TODO recordset needs to be closed after use. how?
    }

    protected function get_sql(bool $count = false, ?bool $includereplies = null) : array {
        if (!in_array($this->sortdirection, ['ASC', 'DESC'])) {
            throw new \moodle_exception('invalidsortdirection', 'core'); // TODO error message
        }

        $where = 'contextid = :contextid AND component = :component AND commentarea = :commentarea';
        $params = [
            'contextid' => $this->area->get_context()->id,
            'component' => $this->area->get_component(),
            'commentarea' => $this->area->get_area(),
        ];
        if ($this->section) {
            $where .= ' AND itemid = :itemid';
            $params['itemid'] = $this->section->get_item_id();
        }
        if ($this->replytoid) {
            $where .= ' AND replytoid = :replytoid';
            $params['replytoid'] = $this->replytoid;
        } else if ($includereplies === false || (is_null($includereplies) && !$this->includereplies)) {
            $where .= ' AND replytoid IS NULL';
        }
        if (!is_null($this->timefrom)) {
            $where .= ' AND timecreated >= :timefrom';
            $params['timefrom'] = $this->timefrom;
        }
        if (!is_null($this->timeto)) {
            $where .= ' AND timecreated <= :timeto';
            $params['timeto'] = $this->timeto;
        }

        if ($count) {
            $sql = 'SELECT COUNT(*) FROM {comments} WHERE ' . $where;
        } else {
            $sql = 'SELECT * FROM {comments} WHERE ' . $where . ' ORDER BY timecreated ' . $this->sortdirection;
        }

        return array($sql, $params);
    }

    /**
     * Fetch all matching comments.
     *
     * @return comment[] The matching comments.
     */
    public function get_all() : array {
        if (!is_null($this->results)) {
            return $this->results;
        }

        // Check permission for single section.
        if ($this->section && $this->user && !$this->section->get_capability($this->user)->can_view()) {
            $this->results = [];
            return $this->results;
        }

        // Fetch comment records.
        global $DB;
        list($sql, $params) = $this->get_sql();
        $limitfrom = 0;
        $limitnum = 0;
        if ($this->pagesize > 0) {
            $limitfrom = $this->pagesize * $this->page;
            $limitnum = $this->pagesize;
        }
        $records = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);

        // Construct comment objects from records.
        $this->results = [];
        $sections = [];
        foreach ($records as $record) {
            $section = $this->section;
            if (is_null($section)) {
                $sectionkey = section::make_unique_key($record->component, $record->commentarea, $record->contextid, $record->itemid);
                if (!array_key_exists($sectionkey, $sections)) {
                    // Initialize section and check capability.
                    $section = $this->area->get_section($record->itemid);
                    if ($this->user && !$section->get_capability($this->user)->can_view()) {
                        $sections[$sectionkey] = null;
                    } else {
                        $sections[$sectionkey] = $section;
                    }
                } else {
                    $section = $sections[$sectionkey];
                }
            }

            // Create comment if user has capability.
            if (!is_null($section)) {
                $this->results[] = $section->construct_comment_from_db($record, $this);
            }
        }
        return $this->results;
    }

    /**
     * Get the first comment in this collection.
     *
     * This is a helper to easily get a comment when you know there is only one
     * (as it is when you fetch one comment by its id).
     *
     * @return comment|null
     */
    public function get_first() : ?comment {
        $all = $this->get_all();
        return sizeof($all) > 0 ? $all[0] : null;
    }

    /**
     * Get a user by their id.
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
        $results = $this->get_all();
        // Get all user ids from the results (plus the userid passed to this function) that are not loaded yet.
        $userids = array_diff(
            array_unique(array_merge(
                array_column($results, 'userid'),
                array_column($results, 'usermodified'),
                array($userid)
            )),
            array_keys($this->loadedusers)
        );
        if (!empty($userids)) {
            global $DB;
            $users = $DB->get_records_list('user', 'id', $userids);
            foreach ($users as $user) {
                $this->loadedusers[$user->id] = $user;
            }
        }
        return $this->loadedusers[$userid];
    }

    /**
     * Count the number of matched comments on the current page.
     *
     * @return int
     */
    public function count() : int {
        if (!is_null($this->results)) {
            return sizeof($this->results);
        } else {
            $totalcount = $this->count_total($this->includereplies);
            return max(0, $totalcount - $this->page * max(0, $this->pagesize));
        }
    }

    /**
     * Count the number of matched comments on all pages.
     *
     * @param bool $includingreplies also include the number of replies to the fetched comments
     * @return int
     */
    public function count_total(bool $includingreplies = true) : int {
        $count = $includingreplies ? $this->totalcountwithreplies : $this->totalcount;
        if (!is_null($count)) {
            return $count;
        }

        global $DB;
        list($sql, $params) = $this->get_sql(true, $includingreplies);
        $count = $DB->count_records_sql($sql, $params);

        if ($includingreplies) {
            $this->totalcountwithreplies = $count;
        } else {
            $this->totalcount = $count;
        }
        return $count;
    }
}

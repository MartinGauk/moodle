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
     * @param int $pagesize maximum number of comments to fetch (-1 for unlimited)
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
        global $DB;

        if (!in_array($this->sortdirection, ['ASC', 'DESC'])) {
            throw new \moodle_exception('invalidsortdirection', 'core'); // TODO error message
        }

        $joins = '';
        $where = 'c.component = :component AND c.commentarea = :commentarea';
        $params = [
            'component' => $this->area->get_component(),
            'commentarea' => $this->area->get_area(),
        ];

        if ($this->section === null && $this->includechildcontexts && $this->area->get_context() instanceof \context_course) {
            $contextids = $this->area->get_component_course_child_contextids($this->user);
        } else {
            $contextids = [$this->area->get_context()->id];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'contexts');
        $where .= ' AND c.contextid ' . $insql;
        $params = array_merge($params, $inparams);

        if ($this->section === null && $this->user) {
            $itemjoins = $this->area->get_comments_sql_join($this->user, $contextids);
            $joins = $itemjoins->joins;
            $where .= ' AND ' . $itemjoins->wheres;
            $params = array_merge($params, $itemjoins->params);
        } else if ($this->section) {
            $where .= ' AND c.itemid = :itemid';
            $params['itemid'] = $this->section->get_item_id();
        }

        if ($this->replytoid) {
            $where .= ' AND c.replytoid = :replytoid';
            $params['replytoid'] = $this->replytoid;
        } else if ($includereplies === false || (is_null($includereplies) && !$this->includereplies)) {
            $where .= ' AND c.replytoid IS NULL';
        }

        if ($this->timefrom !== null) {
            $where .= ' AND c.timecreated >= :timefrom';
            $params['timefrom'] = $this->timefrom;
        }

        if ($this->timeto !== null) {
            $where .= ' AND c.timecreated <= :timeto';
            $params['timeto'] = $this->timeto;
        }

        if ($count) {
            $sql = 'SELECT COUNT(*) FROM {comments} c ' . $joins . ' WHERE ' . $where;
        } else {
            $sql = 'SELECT * FROM {comments} c ' . $joins . ' WHERE ' . $where . ' ORDER BY c.timecreated ' . $this->sortdirection;
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
        [$sql, $params] = $this->get_sql();
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
                        debugging('The query returned comments from a section that the user is not allowed to view.', DEBUG_DEVELOPER);
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
     * Get a user by their id.
     *
     * When first called, it fetches the user data from all users in the search results.
     *
     * @param int $userid
     * @return \stdClass user record
     */
    public function get_user(int $userid) : \stdClass {
        if (isset($this->loadedusers[$userid])) {
            return $this->loadedusers[$userid];
        }
        $results = $this->get_all();
        // Get all user ids from the results (plus the userid passed to this function) that are not loaded yet.
        $userids = [$userid => $userid];
        foreach ($results as $comment) {
            $usercreated = $comment->get_usercreated_id(true);
            $usermodified = $comment->get_usermodified_id(true);
            if (!isset($userids[$usercreated]) && !isset($this->loadedusers[$usercreated])) {
                $userids[$usercreated] = $usercreated;
            }
            if (!isset($userids[$usermodified]) && !isset($this->loadedusers[$usermodified])) {
                $userids[$usermodified] = $usermodified;
            }
        }

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
            $offset = min($totalcount, $this->page * max(0, $this->pagesize));
            return $this->pagesize > 0 ? min($this->pagesize, $totalcount - $offset) : $totalcount - $offset;
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
        if ($count !== null) {
            return $count;
        }

        // Maybe we can avoid another query if the comments were already fetched.
        if ($this->results !== null && (count($this->results) < $this->pagesize || $this->pagesize == -1)) {
            $count = count($this->results);
            if ($includingreplies) {
                $count = array_reduce($this->results, function(int $carry, comment $comment) {
                    return $carry + $comment->get_replies();
                }, $count);
            }
        } else {
            global $DB;
            [$sql, $params] = $this->get_sql(true, $includingreplies);
            $count = $DB->count_records_sql($sql, $params);
        }

        if ($includingreplies) {
            $this->totalcountwithreplies = $count;
        } else {
            $this->totalcount = $count;
        }
        return $count;
    }
}

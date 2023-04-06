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
 * Task to cleanup old question statistics cache.
 *
 * @package    core
 * @copyright  2019 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;

defined('MOODLE_INTERNAL') || die();

/**
 * A task to cleanup old question statistics cache.
 *
 * @copyright  2019 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_stats_cleanup_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskquestionstatscleanupcron', 'admin');
    }

    /**
     * Perform the cleanup task.
     */
    public function execute() {
        global $DB;

        mtrace("\n  Cleaning up old question statistics cache records...", '');

        $expiretime = time() - 5 * HOURSECS;
        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records_select('question_statistics', 'timemodified < ?', [$expiretime]);
        $DB->delete_records_subquery('question_response_count', 'analysisid', 'id',
                'SELECT id FROM {question_response_analysis} WHERE timemodified < ?', [$expiretime]);
        $DB->delete_records_select('question_response_analysis', 'timemodified < ?', [$expiretime]);

        $transaction->allow_commit();
        mtrace('done.');
    }
}

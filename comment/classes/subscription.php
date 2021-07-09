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
 * Comment subscriptions manager.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_comment;

defined('MOODLE_INTERNAL') || die();

/**
 * Comment subscriptions manager.
 *
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subscription {
    /** @var int Setting will be inherited by parent contexts or component decides notification status. */
    const NOTIFICATION_DEFAULT = -1;

    /** @var int Do not send any notifications. */
    const NOTIFICATION_OFF = 0;

    /** @var int Notify a user immediately when there is a new comment. */
    const NOTIFICATION_IMMEDIATE = 1;

    /** @var int Notify a user in a daily digest about new comments. */
    const NOTIFICATION_DAILY_DIGEST = 2;


    /**
     * Get the subscription status of a user.
     *
     * @param \stdClass $user
     * @param section $section
     * @param comment|null $comment
     * @return int one of the \core_comment\subscription::NOTIFICATION_* constants
     */
    static public function get_subscription_status(\stdClass $user, section $section, ?comment $comment = null) : int {
        // TODO
        // TODO For the external services it would be better to return the final status, what the section says and what the database stored?
        return self::NOTIFICATION_OFF;
    }

    /**
     * Get user's subscription as stored in database.
     *
     * If the comment section lies in a course, it also considers the course context.
     *
     * @param \stdClass $user
     * @param section $section
     * @param comment|null $comment
     * @return int one of the \core_comment\subscription::NOTIFICATION_* constants
     */
    static private function get_saved_subscription_status(\stdClass $user, section $section, ?comment $comment = null) : int {
        // TODO
    }


    /**
     * Get the users that subscribed to this comment section.
     *
     * Returns an array that defines whether the users want to receive immediate notifications or daily digests.
     *
     * @param section $section
     * @param comment|null $comment
     * @return array \core_comment\subscription::NOTIFICATION_* => user records
     */
    static public function get_subscribed_users(section $section, ?comment $comment = null) : array {
        // TODO
    }

    /**
     * Set an explicit subscription status für a user.
     *
     * This overrides the default subscription status as determined by the comment section.
     *
     * @param int $userid
     * @param int $subscription one of the \core_comment\subscription::NOTIFICATION_* constants
     * @param area $area
     * @param section|null $section
     * @param comment|null $comment
     */
    static public function update_subscription_status(int $userid, int $subscription, area $area, ?section $section,
            ?comment $comment) {
        // TODO
    }

    /**
     * Delete all subscription data of a user.
     *
     * TODO call this when a user gets deleted.
     *
     * @param int $userid
     */
    static public function delete_user_subscriptions(int $userid) {
        // TODO
    }
}

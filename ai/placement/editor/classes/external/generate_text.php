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

namespace aiplacement_editor\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;

/**
 * External API to call an action for this placement.
 *
 * @package    aiplacement_editor
 * @copyright  2024 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_text extends external_api {
    /**
     * Generate image parameters.
     *
     * @return external_function_parameters
     * @since  Moodle 4.5
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(
                PARAM_INT,
                'The context ID',
                VALUE_REQUIRED,
            ),
            'prompttext' => new external_value(
                PARAM_RAW,
                'The prompt text for the AI service',
                VALUE_REQUIRED,
            ),
        ]);
    }

    /**
     * Generate text from the AI placement.
     *
     * @param int $contextid The context ID.
     * @param string $prompttext The data encoded as a json array.
     * @return array The generated content.
     * @since  Moodle 4.5
     */
    public static function execute(
        int $contextid,
        string $prompttext
    ): array {
        global $USER;
        // Parameter validation.
        [
            'contextid' => $contextid,
            'prompttext' => $prompttext,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'prompttext' => $prompttext,
        ]);
        // Context validation and permission check.
        // Get the context from the passed in ID.
        $context = \core\context::instance_by_id($contextid);

        // Check the user has permission to use the AI service.
        self::validate_context($context);
        require_capability('aiplacement/editor:generate_text', $context);

        // Prepare the action.
        $action = new \core_ai\aiactions\generate_text(
            contextid: $contextid,
            userid: $USER->id,
            prompttext: $prompttext,
        );

        // Send the action to the AI manager.
        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);
        // Return the response.
        return [
            'success' => $response->get_success(),
            'generatedcontent' => $response->get_response_data()['generatedcontent'] ?? '',
            'finishreason' => $response->get_response_data()['finishreason'] ?? '',
            'errorcode' => $response->get_errorcode(),
            'error' => $response->get_errormessage(),
            'timecreated' => $response->get_timecreated(),
            'prompttext' => $prompttext,
        ];
    }

    /**
     * Generate content return value.
     *
     * @return external_function_parameters
     * @since  Moodle 4.5
     */
    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'success' => new external_value(
                PARAM_BOOL,
                'Was the request successful',
                VALUE_REQUIRED,
            ),
            'timecreated' => new external_value(
                PARAM_INT,
                'The time the request was created',
                VALUE_REQUIRED,
            ),
            'prompttext' => new external_value(
                PARAM_RAW,
                'The prompt text for the AI service',
                VALUE_REQUIRED,
            ),
            'generatedcontent' => new external_value(
                PARAM_TEXT,
                'The text generated by AI.',
                VALUE_DEFAULT,
            ),
            'finishreason' => new external_value(
                PARAM_ALPHA,
                'The reason generation was stopped',
                VALUE_DEFAULT,
                'stop',
            ),
            'errorcode' => new external_value(
                PARAM_INT,
                'Error code if any',
                VALUE_DEFAULT,
                0,
            ),
            'error' => new external_value(
                PARAM_TEXT,
                'Error message if any',
                VALUE_DEFAULT,
                '',
            ),
        ]);
    }
}

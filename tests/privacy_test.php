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
 * Data provider tests.
 *
 * @package    mod_evaluation
 * @category   test
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use mod_evaluation\privacy\provider;

require_once($CFG->dirroot . '/mod/evaluation/lib.php');

/**
 * Data provider testcase class.
 *
 * @package    mod_evaluation
 * @category   test
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_evaluation_privacy_testcase extends provider_testcase {

    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test getting the contexts for a user.
     */
    public function test_get_contexts_for_userid() {
        global $DB;
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_evaluation');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm0a = $dg->create_module('evaluation', ['course' => SITEID]);
        $cm1a = $dg->create_module('evaluation', ['course' => $c1, 'anonymous' => EVALUATION_ANONYMOUS_NO]);
        $cm1b = $dg->create_module('evaluation', ['course' => $c1]);
        $cm2a = $dg->create_module('evaluation', ['course' => $c2]);
        $cm2b = $dg->create_module('evaluation', ['course' => $c2]);
        $cm2c = $dg->create_module('evaluation', ['course' => $c2]);

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        foreach ([$cm0a, $cm1a, $cm1b, $cm2a] as $evaluation) {
            $i1 = $fg->create_item_numeric($evaluation);
            $i2 = $fg->create_item_multichoice($evaluation);
            $answers = ['numeric_' . $i1->id => '1', 'multichoice_' . $i2->id => [1]];

            if ($evaluation == $cm1b) {
                $this->create_submission_with_answers($evaluation, $u2, $answers);
            } else {
                $this->create_submission_with_answers($evaluation, $u1, $answers);
            }
        }

        // Unsaved submission for u1 in cm2b.
        $evaluation = $cm2b;
        $i1 = $fg->create_item_numeric($evaluation);
        $i2 = $fg->create_item_multichoice($evaluation);
        $answers = ['numeric_' . $i1->id => '1', 'multichoice_' . $i2->id => [1]];
        $this->create_tmp_submission_with_answers($evaluation, $u1, $answers);

        // Unsaved submission for u2 in cm2c.
        $evaluation = $cm2c;
        $i1 = $fg->create_item_numeric($evaluation);
        $i2 = $fg->create_item_multichoice($evaluation);
        $answers = ['numeric_' . $i1->id => '1', 'multichoice_' . $i2->id => [1]];
        $this->create_tmp_submission_with_answers($evaluation, $u2, $answers);

        $contextids = provider::get_contexts_for_userid($u1->id)->get_contextids();
        $this->assertCount(4, $contextids);
        $this->assertTrue(in_array(context_module::instance($cm0a->cmid)->id, $contextids));
        $this->assertTrue(in_array(context_module::instance($cm1a->cmid)->id, $contextids));
        $this->assertTrue(in_array(context_module::instance($cm2a->cmid)->id, $contextids));
        $this->assertFalse(in_array(context_module::instance($cm1b->cmid)->id, $contextids));
        $this->assertTrue(in_array(context_module::instance($cm2b->cmid)->id, $contextids));
        $this->assertFalse(in_array(context_module::instance($cm2c->cmid)->id, $contextids));

        $contextids = provider::get_contexts_for_userid($u2->id)->get_contextids();
        $this->assertCount(2, $contextids);
        $this->assertFalse(in_array(context_module::instance($cm0a->cmid)->id, $contextids));
        $this->assertFalse(in_array(context_module::instance($cm1a->cmid)->id, $contextids));
        $this->assertFalse(in_array(context_module::instance($cm2a->cmid)->id, $contextids));
        $this->assertTrue(in_array(context_module::instance($cm1b->cmid)->id, $contextids));
        $this->assertFalse(in_array(context_module::instance($cm2b->cmid)->id, $contextids));
        $this->assertTrue(in_array(context_module::instance($cm2c->cmid)->id, $contextids));
    }

    /**
     * Create an submission with answers.
     *
     * @param object $evaluation The evaluation.
     * @param object $user The user.
     * @param array $answers Answers.
     * @param int $submissioncount The number of submissions expected after this entry.
     * @return void
     */
    protected function create_submission_with_answers($evaluation, $user, $answers, $submissioncount = 1) {
        global $DB;

        $modinfo = get_fast_modinfo($evaluation->course);
        $cm = $modinfo->get_cm($evaluation->cmid);

        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $evaluation->course, false, null, null, $user->id);
        $evaluationcompletion->save_response_tmp((object) $answers);
        $evaluationcompletion->save_response();
        $this->assertEquals($submissioncount, $DB->count_records('evaluation_completed', ['evaluation' => $evaluation->id,
                'userid' => $user->id]));
        $this->assertEquals(count($answers), $DB->count_records('evaluation_value', [
                'completed' => $evaluationcompletion->get_completed()->id]));
    }

    /**
     * Create a temporary submission with answers.
     *
     * @param object $evaluation The evaluation.
     * @param object $user The user.
     * @param array $answers Answers.
     * @return void
     */
    protected function create_tmp_submission_with_answers($evaluation, $user, $answers) {
        global $DB;

        $modinfo = get_fast_modinfo($evaluation->course);
        $cm = $modinfo->get_cm($evaluation->cmid);

        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $evaluation->course, false, null, null, $user->id);
        $evaluationcompletion->save_response_tmp((object) $answers);
        $this->assertEquals(1,
                $DB->count_records('evaluation_completedtmp', ['evaluation' => $evaluation->id, 'userid' => $user->id]));
        $this->assertEquals(2, $DB->count_records('evaluation_valuetmp', [
                'completed' => $evaluationcompletion->get_current_completed_tmp()->id]));
    }

    /**
     * Test getting the users in a context.
     */
    public function test_get_users_in_context() {
        global $DB;
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_evaluation');
        $component = 'mod_evaluation';

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm0 = $dg->create_module('evaluation', ['course' => SITEID]);
        $cm1a = $dg->create_module('evaluation', ['course' => $c1, 'anonymous' => EVALUATION_ANONYMOUS_NO]);
        $cm1b = $dg->create_module('evaluation', ['course' => $c1]);
        $cm2 = $dg->create_module('evaluation', ['course' => $c2]);

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        foreach ([$cm0, $cm1a, $cm1b, $cm2] as $evaluation) {
            $i1 = $fg->create_item_numeric($evaluation);
            $i2 = $fg->create_item_multichoice($evaluation);
            $answers = ['numeric_' . $i1->id => '1', 'multichoice_' . $i2->id => [1]];

            if ($evaluation == $cm1b) {
                $this->create_submission_with_answers($evaluation, $u2, $answers);
            } else {
                $this->create_submission_with_answers($evaluation, $u1, $answers);
            }
        }

        // Unsaved submission for u2 in cm1a.
        $evaluation = $cm1a;
        $i1 = $fg->create_item_numeric($evaluation);
        $i2 = $fg->create_item_multichoice($evaluation);
        $answers = ['numeric_' . $i1->id => '1', 'multichoice_' . $i2->id => [1]];
        $this->create_tmp_submission_with_answers($evaluation, $u2, $answers);

        // Only u1 in cm0.
        $context = context_module::instance($cm0->cmid);
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);

        $this->assertCount(1, $userlist);
        $this->assertEquals([$u1->id], $userlist->get_userids());

        $context = context_module::instance($cm1a->cmid);
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);

        // Two submissions in cm1a: saved for u1, unsaved for u2.
        $this->assertCount(2, $userlist);

        $expected = [$u1->id, $u2->id];
        $actual = $userlist->get_userids();
        sort($expected);
        sort($actual);

        $this->assertEquals($expected, $actual);

        // Only u2 in cm1b.
        $context = context_module::instance($cm1b->cmid);
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);

        $this->assertCount(1, $userlist);
        $this->assertEquals([$u2->id], $userlist->get_userids());

        // Only u1 in cm2.
        $context = context_module::instance($cm2->cmid);
        $userlist = new \core_privacy\local\request\userlist($context, $component);
        provider::get_users_in_context($userlist);

        $this->assertCount(1, $userlist);
        $this->assertEquals([$u1->id], $userlist->get_userids());
    }

    /**
     * Test deleting user data.
     */
    public function test_delete_data_for_user() {
        global $DB;
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_evaluation');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm0a = $dg->create_module('evaluation', ['course' => SITEID]);
        $cm1a = $dg->create_module('evaluation', ['course' => $c1, 'anonymous' => EVALUATION_ANONYMOUS_NO]);
        $cm2a = $dg->create_module('evaluation', ['course' => $c2]);

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        // Create a bunch of data.
        foreach ([$cm1a, $cm0a, $cm2a] as $evaluation) {
            $i1 = $fg->create_item_numeric($evaluation);
            $i2 = $fg->create_item_multichoice($evaluation);
            $answers = ['numeric_' . $i1->id => '1', 'multichoice_' . $i2->id => [1]];

            // Create u2 user data for this module.
            if ($evaluation == $cm1a) {
                $this->create_submission_with_answers($evaluation, $u2, $answers);
                $this->create_tmp_submission_with_answers($evaluation, $u2, $answers);
            }

            $this->create_submission_with_answers($evaluation, $u1, $answers);
            $this->create_tmp_submission_with_answers($evaluation, $u1, $answers);
        }

        $appctx = new approved_contextlist($u1, 'mod_evaluation', [
                context_module::instance($cm0a->cmid)->id,
                context_module::instance($cm1a->cmid)->id
        ]);
        provider::delete_data_for_user($appctx);

        // Confirm all data is gone in those, except for u2.
        foreach ([$cm0a, $cm1a] as $evaluation) {
            $this->assert_no_evaluation_data_for_user($evaluation, $u1);
            if ($evaluation == $cm1a) {
                $this->assert_evaluation_data_for_user($evaluation, $u2);
                $this->assert_evaluation_tmp_data_for_user($evaluation, $u2);
            }
        }

        // Confirm cm2a wasn't affected.
        $this->assert_evaluation_data_for_user($cm2a, $u1);
        $this->assert_evaluation_tmp_data_for_user($cm2a, $u1);

    }

    /**
     * Assert there is no evaluation data for a user.
     *
     * @param object $evaluation The evaluation.
     * @param object $user The user.
     * @return void
     */
    protected function assert_no_evaluation_data_for_user($evaluation, $user) {
        global $DB;
        $this->assertFalse($DB->record_exists('evaluation_completed', ['evaluation' => $evaluation->id, 'userid' => $user->id]));
        $this->assertFalse($DB->record_exists('evaluation_completedtmp', ['evaluation' => $evaluation->id, 'userid' => $user->id]));

        // Check that there aren't orphan values because we can't check by userid.
        $sql = "
            SELECT fv.id
              FROM {%s} fv
         LEFT JOIN {%s} fc
                ON fc.id = fv.completed
             WHERE fc.id IS NULL";
        $this->assertFalse($DB->record_exists_sql(sprintf($sql, 'evaluation_value', 'evaluation_completed'), []));
        $this->assertFalse($DB->record_exists_sql(sprintf($sql, 'evaluation_valuetmp', 'evaluation_completedtmp'), []));
    }

    /**
     * Assert there are submissions and answers for user.
     *
     * @param object $evaluation The evaluation.
     * @param object $user The user.
     * @param int $submissioncount The number of submissions.
     * @param int $valuecount The number of values per submission.
     * @return void
     */
    protected function assert_evaluation_data_for_user($evaluation, $user, $submissioncount = 1, $valuecount = 2) {
        global $DB;
        $completeds = $DB->get_records('evaluation_completed', ['evaluation' => $evaluation->id, 'userid' => $user->id]);
        $this->assertCount($submissioncount, $completeds);
        foreach ($completeds as $record) {
            $this->assertEquals($valuecount, $DB->count_records('evaluation_value', ['completed' => $record->id]));
        }
    }

    /**
     * Assert there are temporary submissions and answers for user.
     *
     * @param object $evaluation The evaluation.
     * @param object $user The user.
     * @param int $submissioncount The number of submissions.
     * @param int $valuecount The number of values per submission.
     * @return void
     */
    protected function assert_evaluation_tmp_data_for_user($evaluation, $user, $submissioncount = 1, $valuecount = 2) {
        global $DB;
        $completedtmps = $DB->get_records('evaluation_completedtmp', ['evaluation' => $evaluation->id, 'userid' => $user->id]);
        $this->assertCount($submissioncount, $completedtmps);
        foreach ($completedtmps as $record) {
            $this->assertEquals($valuecount, $DB->count_records('evaluation_valuetmp', ['completed' => $record->id]));
        }
    }

    /**
     * Test deleting data within a context for an approved userlist.
     */
    public function test_delete_data_for_users() {
        global $DB;
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_evaluation');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm0 = $dg->create_module('evaluation', ['course' => SITEID]);
        $cm1 = $dg->create_module('evaluation', ['course' => $c1, 'anonymous' => EVALUATION_ANONYMOUS_NO]);
        $cm2 = $dg->create_module('evaluation', ['course' => $c2]);
        $context0 = context_module::instance($cm0->cmid);
        $context1 = context_module::instance($cm1->cmid);

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        // Create a bunch of data.
        foreach ([$cm0, $cm1, $cm2] as $evaluation) {
            $i1 = $fg->create_item_numeric($evaluation);
            $i2 = $fg->create_item_multichoice($evaluation);
            $answers = ['numeric_' . $i1->id => '1', 'multichoice_' . $i2->id => [1]];

            $this->create_submission_with_answers($evaluation, $u1, $answers);
            $this->create_tmp_submission_with_answers($evaluation, $u1, $answers);

            $this->create_submission_with_answers($evaluation, $u2, $answers);
            $this->create_tmp_submission_with_answers($evaluation, $u2, $answers);
        }

        // Delete u1 from cm0, ensure u2 data is retained.
        $approveduserlist = new core_privacy\local\request\approved_userlist($context0, 'mod_evaluation', [$u1->id]);
        provider::delete_data_for_users($approveduserlist);

        $this->assert_no_evaluation_data_for_user($cm0, $u1);
        $this->assert_evaluation_data_for_user($cm0, $u2);
        $this->assert_evaluation_tmp_data_for_user($cm0, $u2);

        // Ensure cm1 unaffected by cm1 deletes.
        $this->assert_evaluation_data_for_user($cm1, $u1);
        $this->assert_evaluation_tmp_data_for_user($cm1, $u1);
        $this->assert_evaluation_data_for_user($cm1, $u2);
        $this->assert_evaluation_tmp_data_for_user($cm1, $u2);

        // Delete u1 and u2 from cm1, ensure no data is retained.
        $approveduserlist = new core_privacy\local\request\approved_userlist($context1, 'mod_evaluation', [$u1->id, $u2->id]);
        provider::delete_data_for_users($approveduserlist);

        $this->assert_no_evaluation_data_for_user($cm1, $u1);
        $this->assert_no_evaluation_data_for_user($cm1, $u2);

        // Ensure cm2 is unaffected by any of the deletes.
        $this->assert_evaluation_data_for_user($cm2, $u1);
        $this->assert_evaluation_tmp_data_for_user($cm2, $u1);
        $this->assert_evaluation_data_for_user($cm2, $u2);
        $this->assert_evaluation_tmp_data_for_user($cm2, $u2);
    }

    /**
     * Test deleting a whole context.
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_evaluation');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm0a = $dg->create_module('evaluation', ['course' => SITEID]);
        $cm1a = $dg->create_module('evaluation', ['course' => $c1, 'anonymous' => EVALUATION_ANONYMOUS_NO]);

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        // Create a bunch of data.
        foreach ([$cm1a, $cm0a] as $evaluation) {
            $i1 = $fg->create_item_numeric($evaluation);
            $i2 = $fg->create_item_multichoice($evaluation);
            $answers = ['numeric_' . $i1->id => '1', 'multichoice_' . $i2->id => [1]];

            $this->create_submission_with_answers($evaluation, $u1, $answers);
            $this->create_tmp_submission_with_answers($evaluation, $u1, $answers);

            $this->create_submission_with_answers($evaluation, $u2, $answers);
            $this->create_tmp_submission_with_answers($evaluation, $u2, $answers);
        }

        provider::delete_data_for_all_users_in_context(context_module::instance($cm1a->cmid));

        $this->assert_no_evaluation_data_for_user($cm1a, $u1);
        $this->assert_no_evaluation_data_for_user($cm1a, $u2);
        $this->assert_evaluation_data_for_user($cm0a, $u1);
        $this->assert_evaluation_data_for_user($cm0a, $u2);
        $this->assert_evaluation_tmp_data_for_user($cm0a, $u1);
        $this->assert_evaluation_tmp_data_for_user($cm0a, $u2);
    }

    /**
     * Test exporting data.
     */
    public function test_export_user_data() {
        global $DB;
        $dg = $this->getDataGenerator();
        $fg = $dg->get_plugin_generator('mod_evaluation');

        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $cm0a = $dg->create_module('evaluation', ['course' => SITEID]);
        $cm1a = $dg->create_module('evaluation', ['course' => $c1, 'anonymous' => EVALUATION_ANONYMOUS_NO]);
        $cm2a = $dg->create_module('evaluation',
                ['course' => $c2, 'anonymous' => EVALUATION_ANONYMOUS_YES, 'multiple_submit' => 1]);
        $cm2b = $dg->create_module('evaluation', ['course' => $c2]);
        $cm2c = $dg->create_module('evaluation', ['course' => $c2]);

        $u1 = $dg->create_user();
        $u2 = $dg->create_user();

        // Create a bunch of data.
        foreach ([$cm0a, $cm1a, $cm2a, $cm2b] as $evaluation) {
            $i1 = $fg->create_item_numeric($evaluation, ['name' => 'Q1', 'label' => 'L1']);
            $i2 = $fg->create_item_multichoice($evaluation, ['name' => 'Q2', 'label' => 'L2']);
            $answersu1 = ['numeric_' . $i1->id => '1', 'multichoice_' . $i2->id => [1]];
            $answersu2 = ['numeric_' . $i1->id => '2', 'multichoice_' . $i2->id => [2]];

            if ($cm0a == $evaluation) {
                $this->create_submission_with_answers($evaluation, $u1, $answersu1);
                $this->create_tmp_submission_with_answers($evaluation, $u1, $answersu1);
            } else if ($cm1a == $evaluation) {
                $this->create_tmp_submission_with_answers($evaluation, $u1, $answersu1);
            } else if ($cm2a == $evaluation) {
                $this->create_submission_with_answers($evaluation, $u1, $answersu1);
                $this->create_submission_with_answers($evaluation, $u1, ['numeric_' . $i1->id => '1337'], 2);
            } else if ($cm2c == $evaluation) {
                $this->create_submission_with_answers($evaluation, $u1, $answersu1);
                $this->create_tmp_submission_with_answers($evaluation, $u1, $answersu1);
            }

            $this->create_submission_with_answers($evaluation, $u2, $answersu2);
            $this->create_tmp_submission_with_answers($evaluation, $u2, $answersu2);
        }

        $appctx = new approved_contextlist($u1, 'mod_evaluation', [
                context_module::instance($cm0a->cmid)->id,
                context_module::instance($cm1a->cmid)->id,
                context_module::instance($cm2a->cmid)->id,
                context_module::instance($cm2b->cmid)->id,
        ]);
        provider::export_user_data($appctx);

        // CM0A.
        $data = writer::with_context(context_module::instance($cm0a->cmid))->get_data();
        $this->assertCount(2, $data->submissions);
        $submission = $data->submissions[0];
        $this->assertEquals(transform::yesno(false), $submission['inprogress']);
        $this->assertEquals(transform::yesno(true), $submission['anonymousresponse']);
        $this->assertCount(2, $submission['answers']);
        $this->assertEquals('Q1', $submission['answers'][0]['question']);
        $this->assertEquals('1', $submission['answers'][0]['answer']);
        $this->assertEquals('Q2', $submission['answers'][1]['question']);
        $this->assertEquals('a', $submission['answers'][1]['answer']);
        $submission = $data->submissions[1];
        $this->assertEquals(transform::yesno(true), $submission['inprogress']);
        $this->assertEquals(transform::yesno(true), $submission['anonymousresponse']);
        $this->assertCount(2, $submission['answers']);
        $this->assertEquals('Q1', $submission['answers'][0]['question']);
        $this->assertEquals('1', $submission['answers'][0]['answer']);
        $this->assertEquals('Q2', $submission['answers'][1]['question']);
        $this->assertEquals('a', $submission['answers'][1]['answer']);

        // CM1A.
        $data = writer::with_context(context_module::instance($cm1a->cmid))->get_data();
        $this->assertCount(1, $data->submissions);
        $submission = $data->submissions[0];
        $this->assertEquals(transform::yesno(true), $submission['inprogress']);
        $this->assertEquals(transform::yesno(false), $submission['anonymousresponse']);
        $this->assertCount(2, $submission['answers']);
        $this->assertEquals('Q1', $submission['answers'][0]['question']);
        $this->assertEquals('1', $submission['answers'][0]['answer']);
        $this->assertEquals('Q2', $submission['answers'][1]['question']);
        $this->assertEquals('a', $submission['answers'][1]['answer']);

        // CM2A.
        $data = writer::with_context(context_module::instance($cm2a->cmid))->get_data();
        $this->assertCount(2, $data->submissions);
        $submission = $data->submissions[0];
        $this->assertEquals(transform::yesno(false), $submission['inprogress']);
        $this->assertEquals(transform::yesno(true), $submission['anonymousresponse']);
        $this->assertCount(2, $submission['answers']);
        $this->assertEquals('Q1', $submission['answers'][0]['question']);
        $this->assertEquals('1', $submission['answers'][0]['answer']);
        $this->assertEquals('Q2', $submission['answers'][1]['question']);
        $this->assertEquals('a', $submission['answers'][1]['answer']);
        $submission = $data->submissions[1];
        $this->assertEquals(transform::yesno(false), $submission['inprogress']);
        $this->assertEquals(transform::yesno(true), $submission['anonymousresponse']);
        $this->assertCount(1, $submission['answers']);
        $this->assertEquals('Q1', $submission['answers'][0]['question']);
        $this->assertEquals('1337', $submission['answers'][0]['answer']);

        // CM2B (no data).
        $data = writer::with_context(context_module::instance($cm2b->cmid))->get_data();
        $this->assertEmpty($data);

        // CM2C (not exported).
        $data = writer::with_context(context_module::instance($cm2b->cmid))->get_data();
        $this->assertEmpty($data);
    }
}

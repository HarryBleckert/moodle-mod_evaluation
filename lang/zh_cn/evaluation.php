
<?php
// This file is part of Moodle - https://moodle.org/
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
 * Strings for component 'evaluation', language 'de', branch 'MOODLE_405_STABLE'
 *
 * @package     mod_evaluation
 * @category    string
 * @copyright 1999 onwards Martin Dougiamas  {@link https://moodle.com}
 * @copyright 2021 onwards Harry Bleckert for ASH Berlin  {@link https://ash-berlin.eu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// $string[''] = '';
$string['language'] = 'zh_cn';

$string['index_group_by_tag'] = '群组';
// 结束 index.php

// 发送提醒
$string['send_reminders_noreplies_teachers'] = '仅针对提交次数少于 {$a->min_results_text} 的教师。';
$string['send_reminders_noreplies_students'] = '仅限尚未参加评估的学生。';
$string['send_reminders_pmsg'] = '今天，有关正在进行的评估的提醒电子邮件已发送给所有课程正在参与评估的{$a->role}。下面您可以看到一个例子。 '；
$string['send_reminders_remaining'] = '距离 {$a->lastEvaluationDay} 仅剩 {$a->remaining_evaluation_days} 天';
$string['send_reminders_students'] = '{$a->testmsg} <p>您好 {$a->fullname}
<p>请{$a->also}参与{$a->reminder}正在进行的评估<br>
    此调查是匿名的，每个课程和讲师只需花费几分钟的时间。<br>
    对于您已评估过的每个课程，如果至少有 {$a->minResults} 份提交，您可以立即查看评估。<br>
    出于数据保护的原因，个人信息和开放式问题的答案被排除在外。
</p>
<p><b>通过参与，您正在帮助提高教学水平！</b></p>
<p>以下是您在
    <a href="{$a->evUrl}"><b>{$a->ev_name}></b></a>参与：</p>
{$a->我的课程}
<p style="margin-bottom: 0cm">此致敬意<br>
    {$a->签名}
    {$a->signum}';

    $string['send_reminders_teachers'] = '{$a->testmsg} <p>您好 {$a->fullname}
    {$a->少数}
<p>请鼓励您的学生参与{$a->reminder}正在进行的评估<br>
    如果你能通过发出激励性的号召，将参与融入到你的活动中，那将是理想的。
    给学生几分钟时间参与活动吧！</p>
<p>如果您的其中一门课程<b>有</b>至少 {$a->minResults} 份提交，您可以查看对您提交的评估。<br>
    只有当您至少提交了 {$a->min_results_text} 个文本答案时，您才可以自己查看文本答案。</p>
<p>以下是您在
    <a href="{$a->evUrl}"><b>{$a->ev_name}></b></a>参与：</p>
{$a->我的课程}
<p style="margin-bottom: 0cm">此致敬意<br>
    {$a->签名}
    {$a->signum}';

    $string['send_reminders_no_replies'] = '您的{$a->distinct_s}名学生尚未参加。 '；
    $string['send_reminders_few_replies'] = '目前为止，只有 {$a->replies} 个 {$a->submissions} 来自您的 {$a->distinct_s} 名学生。 '；
    $string['send_reminders_many_replies'] = '到目前为止您的 {$a->distinct_s} 名学生共提交了 {$a->replies} 条回复';
    $string['send_reminders_privileged'] = '您收到这封电子邮件是为了了解信息，因为您被授权查看此次评估的评估结果。';
    // 结束发送提醒

    // 翻译功能，Magik 为 Berthe 工作
    $string['evaluation_of_courses'] = '课程评估';
    $string['by_students'] = '由学生';
    $string['of_'] = 'des';
    $string['for_'] = '为了那个';
    $string['sose_'] = '夏季学期';
    $string['wise_'] = '冬季学期';
    // 结束翻译函数

    // 图形库
    $string['show_graphic_data'] = '显示图形数据';
    $string['hide_graphic_data'] = '隐藏图形数据';
    // 结束图库

    // 视图.php
    $string['是'] = '是';
    $string['是'] = '是';
    $string['teamteachingtxt'] = '在团队教学的研讨会上，讲师将接受单独评估。';
    $string['is_WM_disabled'] = '继续教育硕士学位课程被排除在外。';
    $string['siteadmintxt'] = '管理员因此';
    $string['andrawdata'] = '和原始数据';

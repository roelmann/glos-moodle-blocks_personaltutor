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
 * Main block file.
 *
 * @package    block_personaltutor
 * @copyright  2019 Richard Oelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
class block_personaltutor extends block_base {

    public function init() {
        $this->title = get_string('blocktitle', 'block_personaltutor');
    }

    public function has_config() {
        return false;
    }

    public function applicable_formats() {
        return array(
        'site-index' => true,
        'my-index' => true,
        'all' => false
        );
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function hide_header() {
        return true;
    }

    public function get_content() {

        global $COURSE, $DB, $PAGE, $USER, $OUTPUT;
        if (!isloggedin()) {
            return false;
        }
        // If added to site-home then applicable formats does not appear to restrict display.
        if ($PAGE->pagetype != 'my-index' && $PAGE->pagetype != 'site-index') {
            return false;
        }
        $tutorrole = 'mentor'; // Make this a setting.
        $tutroleid = $DB->get_field('role', 'id', array('shortname' => $tutorrole));
        $context = context_user::instance($USER->id);
        $usrctxt = $context->id;
        $this->content = new stdClass;

        if ($DB->record_exists('role_assignments', array('contextid' => $usrctxt, 'roleid' => $tutroleid))) {
            // User is student with personal tutor.
            $tutorid = $DB->get_field('role_assignments', 'userid', array('contextid' => $usrctxt, 'roleid' => $tutroleid));
            $tutor = $DB->get_record('user', array('id' => $tutorid));
            $tutorimg = $OUTPUT->user_picture($tutor, array('size' => 150));

            // Create actual block with image and text - for single link.
            $this->content->text = '<h5 class="mx-auto text-center">Personal tutor</h5>';
            $this->content->text .= '<div class="tutorimg mx-auto" style="width:150px;">'.$tutorimg.'</div>';
            $this->content->text .= '<div class="tutordesc">'.$tutor->description.'</div>';
            $this->content->text .= '<strong class = "" style = "margin-top:1rem;">';
            if ($tutor->phone1) {
                $this->content->text .= '<i class="fa fa-phone">&nbsp;</i> &nbsp;'.$tutor->phone1.'<br>';
            }
            if ($tutor->email) {
                $this->content->text .= '<a href="mailto:'.$tutor->email.'">
                    <i class=" fa fa-envelope">&nbsp;</i>&nbsp;Send email</a><br>';
            }
            if (core_plugin_manager::instance()->get_plugin_info('report_myfeedback')) {
                $myfeedbacklink = new moodle_url('/report/myfeedback/index.php?userid='.$USER->id);
                $this->content->text .= '<a href="'.$myfeedbacklink.'">
                    <i class=" fa fa-commenting">&nbsp;</i>&nbsp;My Feedback Report</a><br>';
            }
            $this->content->text .= '</strong>';
            return $this->content;

        } else if ($DB->record_exists('role_assignments', array('userid' => $USER->id, 'roleid' => $tutroleid))) {
            // User is tutor with tutees.
            $tutees = $DB->get_records('role_assignments', array('userid' => $USER->id, 'roleid' => $tutroleid));
            $this->content->text = '<h5 class="mx-auto text-center">Personal tutees</h5></br>';
            $this->content->text .= '<ul class="perstutlist">';
            foreach ($tutees as $t) {
                $tuteeid = $DB->get_field('context', 'instanceid', array('id' => $t->contextid));
                $tutee = $DB->get_record('user', array('id' => $tuteeid));
                $tuteeimg = $OUTPUT->user_picture($tutee, array('size' => 45));

                $this->content->text .= '<li class="row">';
                    $this->content->text .= '<div class="tuteeimg" style="width:20%;">'.$tuteeimg.'</div>';
                    $this->content->text .= '<strong class="tuteename"style="width:60%;">'.
                        $tutee->firstname.' '.$tutee->lastname.'</strong>';
                    $this->content->text .= '<div class="tuteecontact" style="width:15%;">';
                        $this->content->text .= '&nbsp;<a href="mailto:'.$tutee->email.'">
                            <i class=" fa fa-envelope">&nbsp;</i></a><br>';
                    $this->content->text .= '</div>';
                $this->content->text .= '</li>';
            }
            $this->content->text .= '</ul></br>';
            if (core_plugin_manager::instance()->get_plugin_info('report_myfeedback')) {
                $myfeedbacklink = new moodle_url('/report/myfeedback/index.php?userid='.$USER->id);
                $this->content->text .= '<a href="'.$myfeedbacklink.'">
                    <i class=" fa fa-commenting">&nbsp;</i>&nbsp;My Feedback Report</a><br>';
            }

            return $this->content;

        } else {
            return false;
        }
    }

    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('defaulttitle', 'block_personaltutor');
            } else {
                $this->title = $this->config->title;
            }

            if (empty($this->config->text)) {
                $this->config->text = get_string('defaulttext', 'block_personaltutor');
            }
        }
    }

    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values.
        $attributes['class'] .= ' block_'. $this->name(); // Append our class to class attribute.
        $attributes['class'] .= ' block_'. $this->title; // Append our class to class attribute.
        return $attributes;
    }
}

<?php
// This file is part of the Contact Form plugin for Moodle - http://moodle.org/
//
// Contact Form is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Contact Form is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Contact Form.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin for Moodle is used to send emails through a web form.
 *
 * @package    local_contact
 * @copyright  2016-2017 TNG Consulting Inc. - www.tngconsulting.ca
 * @author     Michael Milette
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/contact/class/local_contact.php');
if (false) { // This is only included to avoid code checker warning.
    require_login();
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/contact/index.php');
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_contact'));
$PAGE->navbar->add('');

$contact = new local_contact();
if ($contact->isspambot) {
    header('HTTP/1.0 403 Forbidden');
    if ($CFG->debugdisplay == 1 || is_siteadmin()) {
        die(get_string('forbidden', 'local_contact') . '. ' . $contact->errmsg);
    } else {
        die(get_string('forbidden', 'local_contact')) . '.';
    }
}

// Display page header.
echo $OUTPUT->header();

// Determine the recipient's name and email address.

// The default recipient is the Moodle site's support contact. This will
// be used if no recipient was specified or if the recipient is unknown.
$name = $CFG->supportname;
$email = $CFG->supportemail;

// If the form includes a recipient's alias, search the plugin's config recipient list for a name and email address.
$recipient = optional_param('recipient', null, PARAM_TEXT);
if (trim($recipient) != '' || empty($recipient)) {
    $lines = explode("\n", get_config('local_contact', 'recipient_list'));
    foreach ($lines as $linenumbe => $line) {
        $line = trim($line);
        if (strlen($line) == 0) {
            continue;
        }
        // See if this entry matches the one we are looking for.
        $thisrecipient = explode('|', $line);
        if (count($thisrecipient) == 3) {
            // 0 = alias, 1 = email address, 2 = name.
            if (trim($thisrecipient[0]) == $recipient && trim($thisrecipient[1]) != '' && trim($thisrecipient[2]) != '') {
                $email = $thisrecipient[1];
                $name = $thisrecipient[2];
                break;
            }
        }
    }
}

// Send the message.

if ($contact->sendmessage($email, $name)) {
    // Share a gratitude and Say Thank You! Your user will love to know their message was sent.
    echo '<h3>' . get_string('eventmessagesent', 'message') . '</h3>';
    echo get_string('confirmationmessage', 'local_contact');
} else {
    // Oh no! What are the chances. Looks like we failed to meet user expectations (message not sent).
    echo '<h3>'.get_string('errorsendingtitle', 'local_contact').'</h3>';
    echo get_string('errorsending', 'local_contact');
}
echo $OUTPUT->continue_button($CFG->wwwroot);

// Display page footer.
echo $OUTPUT->footer();

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
 * Kronos training manager request block.
 *
 * @package    block_kronostmrequest
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */
class block_kronostmrequest_edit_form extends block_edit_form {
    /**
     * Overridden from base class.  Add additional form elements to the block instance editing page.
     */
    protected function specific_definition($mform) {
        global $CFG, $DB;

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_kronostmrequest'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setDefault('config_title', get_string('newkronostmrequest', 'block_kronostmrequest'));

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->block->context);
        $element = $mform->addElement('editor', 'config_text', get_string('configcontent', 'block_kronostmrequest'), null, $editoroptions);
        // XSS is prevented when printing the block contents and serving files.
        $mform->setType('config_text', PARAM_RAW);
        $element->setValue(array('text' => get_string('newblockcontent', 'block_kronostmrequest', $CFG)));

        // Standard user fields.
        $fields = array(
            'no_field_selected' => get_string('no_field_selected', 'block_kronostmrequest'),
            'username' => get_user_field_name('username'),
            'email' => get_user_field_name('email'),
            'idnumber' => get_user_field_name('idnumber'),
            'lastnamephonetic' => get_user_field_name('lastnamephonetic'),
            'firstnamephonetic' => get_user_field_name('firstnamephonetic'),
            'middlename' => get_user_field_name('middlename'),
            'alternatename' => get_user_field_name('alternatename'),
            'institution' => get_user_field_name('institution'),
            'department' => get_user_field_name('department'),
            'description' => get_user_field_name('description'),
            'phone1' => get_user_field_name('phone1'),
            'phone2' => get_user_field_name('phone2'),
            'address' => get_user_field_name('address'),
            'lang' => get_string('language'),
            'theme' => get_user_field_name('theme'),
            'timezone' => get_user_field_name('timezone'),
            'url' => get_user_field_name('url'),
            'icq' => get_user_field_name('icq'),
            'skype' => get_user_field_name('skype'),
            'aim' => get_user_field_name('aim'),
            'yahoo' => get_user_field_name('yahoo'),
            'msn' => get_user_field_name('msn'),
        );

        // Custom fields.
        $customfields = $DB->get_records('user_info_field');
        $options = array('context' => context_system::instance());
        foreach ($customfields as $field) {
            $fields['profile_field_'.$field->shortname] = format_string($field->name, true, $options);
        }

        $mform->addElement('select', 'config_restrictby', get_string('restrictby', 'block_kronostmrequest'), $fields);
        $mform->setType('config_restrictby', PARAM_TEXT);
        $mform->addHelpButton('config_restrictby', 'restrictby', 'block_kronostmrequest');

        $mform->addElement('text', 'config_restrictbyvalue', get_string('restrictbyvalue', 'block_kronostmrequest'));
        $mform->setType('config_restrictbyvalue', PARAM_TEXT);
        $mform->addHelpButton('config_restrictbyvalue', 'restrictbyvalue', 'block_kronostmrequest');
    }

    /**
     * Overridden from base class. Set deafult data.
     *
     * @param object $defaults Object contianing default data.
     */
    public function set_data($defaults) {
        if (!empty($this->block->config) && is_object($this->block->config)) {
            $text = $this->block->config->text;
            $draftideditor = file_get_submitted_draft_itemid('config_text');

            if (empty($text)) {
                $currenttext = '';
            } else {
                $currenttext = $text;
            }

            $text = file_prepare_draft_area($draftideditor, $this->block->context->id, 'block_kronostmrequest', 'content', 0, array('subdirs' => true), $currenttext);
            $defaults->config_text['text'] = $text;
            $defaults->config_text['itemid'] = $draftideditor;
            $defaults->config_text['format'] = $this->block->config->format;
        } else {
            $text = '';
        }

        if (!$this->block->user_can_edit() && !empty($this->block->config->title)) {
            // If a title has been set but the user cannot edit it format it nicely.
            $title = $this->block->config->title;
            $defaults->config_title = format_string($title, true, $this->page->context);
            // Remove the title from the config so that parent::set_data doesn't set it.
            unset($this->block->config->title);
        }

        // Have to delete text here, otherwise parent::set_data will empty content of editor.
        unset($this->block->config->text);
        parent::set_data($defaults);
        // Restore text.
        if (!isset($this->block->config)) {
            $this->block->config = new stdClass();
        }
        $this->block->config->text = $text;
        if (isset($title)) {
            // Reset the preserved title.
            $this->block->config->title = $title;
        }
    }
}

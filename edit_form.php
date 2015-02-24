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
 * @package    block_kronoshtml
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */
class block_kronostmrequest_edit_form extends block_edit_form {
    /**
     * Overridden from base class.  Add additional form elements to the block instance editing page.
     */
    protected function specific_definition($mform) {
        global $CFG;

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_kronoshtml'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setDefault('config_title', get_string('newkronostmrequest', 'block_kronostmrequest'));

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->block->context);
        $element = $mform->addElement('editor', 'config_text', get_string('configcontent', 'block_kronoshtml'), null, $editoroptions);
        // XSS is prevented when printing the block contents and serving files.
        $mform->setType('config_text', PARAM_RAW);
        $element->setValue(array('text' => get_string('newblockcontent', 'block_kronostmrequest', $CFG)));
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

            $text = file_prepare_draft_area($draftideditor, $this->block->context->id, 'block_kronoshtml', 'content', 0, array('subdirs' => true), $currenttext);
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

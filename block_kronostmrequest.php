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
class block_kronostmrequest extends block_base {
    /** @var string Holds the block language string name. */
    public $blockname = null;

    /**
     * Set the initial properties for the block
     */
    public function init() {
        $this->blockname = get_class($this);
        $this->title = get_string('pluginname', $this->blockname);
    }

    /**
     * Overridden from parent class.  This block has a global settings page.
     */
    public function has_config() {
        return true;
    }

    /**
     * Allow instances to have their own configuration.
     *
     * @return boolean Return true to allow instances to have their own configuration.
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Overridden from parent class. Sets the formats.
     */
    public function applicable_formats() {
        return array('all' => true);
    }

    /**
     * Overridden from parent class. This game sets the formats.
     */
    public function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('newkronostmrequest', $this->blockname));
    }

    /**
     * Overridden from parent class. Set multiple instances.
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Overridden from parent class.
     */
    public function get_content() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/kronostmrequest/lib.php');

        // If restricted by is configured, check if the current user profile fields match.
        if (!empty($this->config->restrictby) && !empty($this->config->restrictbyvalue)) {
            // Load user record.
            $record = $DB->get_record('user', array('id' => $USER->id));
            // Load custom feilds.
            profile_load_data($record);
            $retrictby = $this->config->restrictby;
            if (empty($record->$retrictby) || $record->$retrictby != $this->config->restrictbyvalue) {
                $this->content = new stdClass;
                $this->content->footer = '';
                $this->config->text = '';
                return $this->content;
            }
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';

        if (is_siteadmin($USER->id)) {
            return $this->content;
        }
        if (kronostmrequest_has_role($USER->id) || !isloggedin()) {
            return $this->content;
        }
        if (isset($this->config->text)) {
            // Rewrite url.
            $this->config->text = file_rewrite_pluginfile_urls($this->config->text, 'pluginfile.php', $this->context->id, 'block_kronoshtml', 'content', null);
            // Default to FORMAT_HTML which is what will have been used before the editor was properly implemented for the block.
            $format = FORMAT_HTML;
            // Check to see if the format has been properly set on the config.
            if (isset($this->config->format)) {
                $format = $this->config->format;
            }
            $filteropt = new stdClass;
            $filteropt->overflowdiv = true;
            $filteropt->noclean = true;
            $this->content->text = format_text($this->config->text, $format, $filteropt);
        } else {
            $this->content->text = get_string('newblockcontent', 'block_kronostmrequest', $CFG);
        }

        return $this->content;
    }

    /**
     * Overridden from parent class.  Serialize and store config data
     */
    public function instance_config_save($data, $nolongerused = false) {
        global $DB;

        $config = clone($data);
        // Move embedded files into a proper filearea and adjust HTML links to match.
        $config->text = file_save_draft_area_files($data->text['itemid'], $this->context->id, 'block_html', 'content', 0, array('subdirs' => true), $data->text['text']);
        $config->format = $data->text['format'];

        parent::instance_config_save($config, $nolongerused);
    }

    /**
     * Overridden from parent class.  Delete file area.
     */
    public function instance_delete() {
        global $DB;
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_html');
        return true;
    }

    /**
     * Overridden from parent class.  The block should only be dockable when the title of the block is not empty
     * and when parent allows docking.
     */
    public function instance_can_be_docked() {
        return (!empty($this->config->title) && parent::instance_can_be_docked());
    }
}

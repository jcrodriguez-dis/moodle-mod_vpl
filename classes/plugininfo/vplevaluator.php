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

namespace mod_vpl\plugininfo;

use core\plugininfo\base;
use core_plugin_manager;
use moodle_url;

/**
 * VPL evaluator type subplugin info class.
 *
 * @package   mod_vpl
 * @copyright 2013 Petr Skoda {@link http://skodak.org}
 * @copyright 2024 Juan Calos Rodriguez del Pino {@ jc.rodriguezdelpino@ulpgc.es
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class vplevaluator extends base {
    /**
     * name of the plugin type
     */
    const PLUGIN_TYPE = 'vplevaluator';

    public static function plugintype_supports_disabling(): bool {
        return false;
    }

    /**
     * Finds all enabled plugins, the result may include missing plugins.
     * @return array|null of enabled plugins $pluginname=>$pluginname, null means unknown
     */
    public static function get_enabled_plugins() {
        global $DB;

        $plugins = core_plugin_manager::instance()->get_installed_plugins(self::PLUGIN_TYPE);
        if (!$plugins) {
            return [];
        }
        $installed = [];
        foreach ($plugins as $plugin => $version) {
            $installed[] = self::PLUGIN_TYPE . '_'.$plugin;
        }

        list($installed, $params) = $DB->get_in_or_equal($installed, SQL_PARAMS_NAMED);
        $disabled = $DB->get_records_select('config_plugins', "plugin $installed AND name = 'disabled'", $params, 'plugin ASC');
        foreach ($disabled as $conf) {
            if (empty($conf->value)) {
                continue;
            }
            list($type, $name) = explode('_', $conf->plugin, 2);
            unset($plugins[$name]);
        }

        $enabled = array();
        foreach ($plugins as $plugin => $version) {
            $enabled[$plugin] = $plugin;
        }

        return $enabled;
    }

    public static function enable_plugin(string $pluginname, int $enabled): bool {
        $haschanged = false;

        $plugin = elf::PLUGIN_TYPE . '_' . $pluginname;
        $oldvalue = get_config($plugin, 'disabled');
        $disabled = !$enabled;
        // Only set value if there is no config setting or if the value is different from the previous one.
        if ($oldvalue === false || ((bool) $oldvalue != $disabled)) {
            set_config('disabled', $disabled, $plugin);
            $haschanged = true;

            add_to_config_log('disabled', $oldvalue, $disabled, $plugin);
            \core_plugin_manager::reset_caches();
        }
        return $haschanged;
    }

    public function is_uninstall_allowed() {
        // Check if used by some activity
        return false;
    }


    public function get_settings_section_name() {
        return $this->type . '_' . $this->name;
    }
}

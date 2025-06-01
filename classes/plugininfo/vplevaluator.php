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

/**
 * VPL evaluator type subplugin info class.
 *
 * @package   mod_vpl
 * @copyright 2024 Juan Calos Rodriguez del Pino {@jc.rodriguezdelpino@ulpgc.es}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vplevaluator extends base {
    /**
     * The plugin type.
     * @var string
     */
    public const PLUGIN_TYPE = 'vplevaluator';

    private static $plugin_lib_loaded = [];

    /**
     * Object to evaluate the plugin.
     * @var ?\mod_vpl\plugininfo\vplevaluator_base
     */
    private ?\mod_vpl\plugininfo\vplevaluator_base $evaluator = null;

    public static function plugintype_supports_disabling(): bool {
        return true;
    }

    /**
     * Returns full pass to lib file.
     * @param string $name of the plugin.
     * @return string
     */
    public static function get_lib_path(string $name): string {
        global $CFG;
        return "{$CFG->dirroot}/mod/vpl/evaluator/{$name}/lib.php";
    }

    /**
     * Finds all subplugins.
     * @return array of enabled plugins $pluginname=>$pluginname
     */
    public static function get_enabled_plugins(): array {
        global $DB;
        global $CFG;
        $pluginmanager = \core_plugin_manager::instance();
        $plugins = $pluginmanager->get_plugins_of_type(self::PLUGIN_TYPE);
        $enabledplugins = [];
        foreach ($plugins as $plugin => $version) {
            $plugininfo = $pluginmanager->get_plugin_info(self::PLUGIN_TYPE . "_{$plugin}");
            if (!$plugininfo) {
                // Plugin not found.
                continue;
            }
            $filename = self::get_lib_path($plugin);
            if (!$plugininfo->is_enabled() || !file_exists($filename)) {
                // Plugin disable or disk missing.
                continue;
            }
            $enabledplugins[$plugin] = $plugin;
        }
        return $enabledplugins;
    }

    /**
     * Returns plugin full name.
     * @return string
     */
    public function get_plugin_fullname(): string {
        return self::PLUGIN_TYPE . "_{$this->name}";
    }

    /**
     * Returns if the plugin is enabled.
     * @return bool
     */
    public function is_enabled(): bool {
        return !get_config($this->get_plugin_fullname(), 'disabled');
    }

    /**
     * Returns if it is allowed to uninstall the plugin.
     * @return bool
     */
    public function is_uninstall_allowed(): bool {
        // It is correct to uninstall a plugin in use?
        // Do not allow to uninstall the biotest plugin.
        return false;
    }

    /**
     * Returns the setting section name.
     * @return string
     */
    public function get_settings_section_name(): string {
        return $this->get_plugin_fullname();
    }

    /**
     * Returns instance of a vplevaluator plugin.
     * @param string $name of plugin of type vplevaluator.
     * @return \mod_vpl\plugininfo\vplevaluator_base
     * @throws \moodle_exception if the plugin is not found.
     */
    public static function get_evaluator($name): \mod_vpl\plugininfo\vplevaluator_base {
        global $CFG;
        $pluginfullname = self::PLUGIN_TYPE . "_{$name}";
        $classname = "\\mod_vpl\\evaluator\\{$name}";
        if (!class_exists($classname)) {
            // The class is not loaded, so we need to load it.
            $pluginmanager = \core_plugin_manager::instance();
            $plugininfo = $pluginmanager->get_plugin_info($pluginfullname);
            if (!$plugininfo || !$plugininfo->is_enabled()) {
                throw new \moodle_exception('error:invalidevaluator', 'vpl', '', $name);
            }
            $filename = self::get_lib_path($name);
            if (!file_exists($filename)) {
                throw new \moodle_exception('error:invalidevaluator', 'vpl', '', $name);
            }
            include_once($filename);
        }
        if (!class_exists($classname)) {
            throw new \moodle_exception('error:invalidevaluator', 'vpl', '', $name);
        }
        return new $classname($name);
    }
}

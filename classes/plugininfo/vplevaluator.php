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

    /**
     * Returns if the plugin type supports disabling.
     * @return bool
     */
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
        $pluginmanager = \core_plugin_manager::instance();
        $plugins = $pluginmanager->get_plugins_of_type(self::PLUGIN_TYPE);
        $enabledplugins = [];
        foreach (array_keys($plugins) as $plugin) {
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

    /**
     * Get printable evaluator plugin information.
     * @param \mod_vpl $vpl instance of the vpl activity.
     * @param string $evaluatorname name of the evaluator name '' for effective evaluator from $vpl.
     * @return string HTML formatted string
     */
    public static function get_printable_evaluator_help($vpl, $evaluatorname = ''): string {
        global $OUTPUT;
        if (empty($evaluatorname)) {
            $evaluatorname = $vpl->get_effective_setting('evaluator');
        }
        if (empty($evaluatorname) || !$vpl->has_capability(VPL_MANAGE_CAPABILITY)) {
            return '';
        }
        try {
            $evaluator = self::get_evaluator($evaluatorname);
            return $evaluator->get_printable_help($vpl, $showlink);
        } catch (\Exception $e) {
            return $OUTPUT->notification(get_string('error:invalidevaluator', VPL, $evaluatorname), 'notifyproblem');
        }
        return '';
    }

    /**
     * Get printable evaluator plugin information.
     * @param \mod_vpl $vpl instance of the vpl activity.
     * @param string $evaluatorname name of the evaluator name '' for effective evaluator from $vpl.
     * @param bool $ifhelp if true return '' if no help available
     * @return string HTML formatted string
     */
    public static function get_printable_evaluator_help_link($vpl, $evaluatorname = '', $ifhelp = false): string {
        global $OUTPUT;
        if (empty($evaluatorname)) {
            $evaluatorname = $vpl->get_effective_setting('evaluator');
        }
        if (empty($evaluatorname) || !$vpl->has_capability(VPL_MANAGE_CAPABILITY)) {
            return '';
        }
        try {
            $evaluator = self::get_evaluator($evaluatorname);
            return $evaluator->get_printable_help_link($vpl, $ifhelp);
        } catch (\Exception $e) {
            return $OUTPUT->notification(get_string('error:invalidevaluator', VPL, $evaluatorname), 'notifyproblem');
        }
        return '';
    }
}

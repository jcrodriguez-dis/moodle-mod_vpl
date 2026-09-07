<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * SEB: Plist builder for downloadable Safe Exam Browser configuration.
 *
 * @package mod_vpl
 * @copyright 2026 Alejandro David Arzola Saavedra
 * @copyright 2026 8 Juan Carlos Rodríguez del Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 * @author Juan Carlos Rodríguez del Pino <jc.rodriguezdelpino@ulpgc.es>
 */

namespace mod_vpl\seb;

/**
 * Build plist XML for downloadable SEB configuration.
 */
class plist_builder {
    /**
     * Build SEB configuration XML.
     *
     * @param array $payload SEB configuration payload as array.
     * @return string
     */
    public static function build_config_xml(array $payload): string {
        $canonpayload = config_payload::canonicalize_payload($payload);
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">',
            '<plist version="1.0"><dict>',
            self::xml_from_dict($canonpayload),
            '</dict></plist>',
        ];

        return implode('', $xml) . "\n";
    }

    /**
     * Associative array as plist dict entries.
     *
     * @param array $dict Dictionary payload.
     * @return string
     */
    protected static function xml_from_dict(array $dict): string {
        $xml = '';
        foreach ($dict as $key => $value) {
            $xml .= '<key>' . htmlspecialchars((string)$key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</key>';
            $xml .= self::xml_from_value($value);
        }
        return $xml;
    }

    /**
     * Render generic plist value.
     *
     * @param mixed $value Value to render.
     * @return string XML represetation of value.
     */
    protected static function xml_from_value($value): string {
        if (is_bool($value)) {
            return '<' . ($value ? 'true' : 'false') . '/>';
        }

        if (is_int($value)) {
            return '<integer>' . $value . '</integer>';
        }

        if (is_float($value)) {
            $stringvalue = json_encode($value, JSON_PRESERVE_ZERO_FRACTION);
            return '<real>' . $stringvalue . '</real>';
        }

        if (is_array($value)) {
            if (config_payload::is_assoc_array($value)) {
                return '<dict>' . self::xml_from_dict($value) . '</dict>';
            }

            $xml = '<array>';
            foreach ($value as $item) {
                $xml .= self::xml_from_value($item);
            }
            $xml .= '</array>';
            return $xml;
        }

        return '<string>' . htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</string>';
    }
}

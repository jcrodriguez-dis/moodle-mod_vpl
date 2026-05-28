<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * SEB: Plist builder for downloadable Safe Exam Browser configuration.
 *
 * @package mod_vpl
 * @copyright 2026 Alejandro David Arzola Saavedra
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Alejandro David Arzola Saavedra <alejandro.arzola101@alu.ulpgc.es>
 */

namespace mod_vpl\seb;

defined('MOODLE_INTERNAL') || die();

/**
 * Build plist XML for downloadable SEB configuration.
 */
class plist_builder {
    /**
     * Build downloadable SEB configuration XML.
     *
     * @param \stdClass $record SEB record.
     * @param mixed $starturl URL or moodle_url.
     * @return string
     */
    public static function build_download_config_xml(\stdClass $record, $starturl, array $overrides = []): string {
        
        if (is_object($starturl) && method_exists($starturl, 'out')) {
            $starturl = $starturl->out(true);
        }

        $starturl = (string)$starturl;

        $payload = config_payload::get_download_payload($record);
        $payload['startURL'] = $starturl;
        
        foreach ($overrides as $key => $value) {
            $payload[$key] = $value;
        }

        $payload = config_payload::canonicalize_payload($payload);

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
        $xml[] = '<plist version="1.0"><dict>';
        $xml[] = self::xml_from_dict($payload);
        $xml[] = '</dict></plist>';

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
     * @return string
     */
    protected static function xml_from_value($value): string {
        
        if (is_bool($value)) {
            return '<' . ($value ? 'true' : 'false') . '/>';
        }

        if (is_int($value)) {
            return '<integer>' . $value . '</integer>';
        }

        if (is_float($value)) {
            return '<real>' . rtrim(rtrim((string)$value, '0'), '.') . '</real>';
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



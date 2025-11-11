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
 * Similarity base class preprocesing information of a file
 * from any source (directory, zip file or vpl activity)
 *
 * @package mod_vpl
 * @copyright 2022 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
namespace mod_vpl\similarity;

use mod_vpl\tokenizer\token_type;
use mod_vpl\tokenizer\token;

/**
 * Base class for similarity processors.
 * This class is used to define the interface for similarity processors.
 * @codeCoverageIgnore
 */
abstract class similarity_base {
    /**
     * @var object $from Source of the content to parse.
     * This is used to store the origin of the content, such as a file or a directory.
     */
    protected $from;

    /**
     * @var int $size Size of the current tokens.
     * This is used to store the number of tokens in the similarity instance.
     */
    protected $size;

    /**
     * @var int $sizeh Size of the hash table.
     * This is used to store the number of unique tokens in the hash table.
     */
    protected $sizeh;

    /**
     * @var array $vecfrec Array to store the vector of frequencies of tokens.
     * This is used to calculate the similarity between different files.
     */
    protected $vecfrec;

    /**
     * @var array $hashes Array to store the hashes of the tokens.
     * This is used to calculate the similarity between different files.
     */
    protected $hashes;

    /**
     * @var object $cluster Cluster object containing information about the cluster.
     * This is used to group similar files together in the similarity instance.
     */
    public $cluster;

    /**
     * @var int $fid Unique identifier for the file.
     * This is used to differentiate between different files in the similarity instance.
     */
    public $fid;

    /**
     * @var int $id Unique identifier for the similarity instance.
     * This is used to differentiate between different similarity instances.
     */
    public $id;

    /**
     * @var array $valueconverter Array to convert string operators to numbers.
     */
    protected static $valueconverter = [];

    /**
     * Get value id for a given value.
     * This method ensures that each unique value is assigned a unique integer id.
     *
     * @param string $value The value to convert to an id.
     * @return int The unique id for the value.
     */
    protected static function get_value_id($value) {
        if (!isset(self::$valueconverter[$value])) {
            self::$valueconverter[$value] = count(self::$valueconverter);
        }

        return self::$valueconverter[$value];
    }

    /**
     * Get integer type for current language
     *
     * @return int
     */
    abstract public function get_type();

    /**
     * Get tokenizer for current language
     *
     * @return tokenizer|vpl_tokenizer
     */
    abstract public function get_tokenizer();

    /**
     * Get size of current tokens
     * @return int size of current tokens
     */
    public function get_size() {
        return $this->size;
    }

    /**
     * Get size of current hash table
     * @return int size of current hash table
     */
    public function get_sizeh() {
        return $this->sizeh;
    }

    /**
     * Normalize current syntax of tokens parsed
     * by the current tokenizer
     * @param array $tokens tokens to normalize
     */
    public function sintax_normalize(&$tokens) {
    }

    /**
     * @var int HASH_SIZE This value is used to calculate the hash table.
     */
    const HASH_SIZE = 4;

    /**
     * @var int HASH_REDUCTION This value is used to reduce the size of the hash table.
     */
    const HASH_REDUCTION = 1000;

    /**
     * Initialize similarity processor
     *
     * @param string $data content to parse
     * @param int $from origin source of the content
     * @param object $toremove used to remove data that is in all submissions
     */
    public function init(&$data, $from, $toremove = null) {
        $this->from = $from;
        $this->size = 0;
        $this->sizeh = 0;
        $this->vecfrec = [];
        $this->hashes = [];

        // Prepare tokens using tokenizer.
        $tok = $this->get_tokenizer();
        $tok->parse($data, false);
        $tokens = $tok->get_tokens();
        $this->sintax_normalize($tokens);

        // Prepare hashes before its calculation.
        $last = [];

        for ($i = 0; $i < self::HASH_SIZE; $i++) {
            $last[$i] = '';
        }

        // Process tokens to get vector of frecuencies, size
        // and values for the hash table.
        foreach ($tokens as $token) {
            if ($token->type == token_type::OPERATOR) {
                // Calculate hashes table.
                for ($i = 0; $i < self::HASH_SIZE - 1; $i++) {
                    $last[$i] = $last[$i + 1];
                }

                $last[self::HASH_SIZE - 1] = $token->value;
                $item = '';

                for ($i = 0; $i < self::HASH_SIZE; $i++) {
                    $item .= $last[$i];
                }

                $hash = crc32($item) % self::HASH_REDUCTION;

                if (isset($this->hashes[$hash])) {
                    $this->hashes[$hash]++;
                } else {
                    $this->hashes[$hash] = 1;
                }

                $this->sizeh++;

                // Get operator id.
                $vid = self::get_value_id($token->value);

                if (isset($this->vecfrec[$vid])) {
                    $this->vecfrec[$vid]++;
                } else {
                    $this->vecfrec[$vid] = 1;
                }

                $this->size++;
            }
        }

        if ($toremove != null) {
            foreach ($toremove->vecfrec as $id => $frec) {
                if (isset($this->vecfrec[$id])) {
                    $this->vecfrec[$id] = $this->vecfrec[$id] > $frec ? $this->vecfrec[$id] - $frec : 0;
                }
            }

            foreach ($toremove->hashes as $id => $frec) {
                if (isset($this->hashes[$id])) {
                    $this->hashes[$id] = $this->hashes[$id] > $frec ? $this->hashes[$id] - $frec : 0;
                }
            }

            $newsize = 0;
            foreach ($this->vecfrec as $frec) {
                $newsize += $frec;
            }

            $this->size = $newsize;
            $newsize = 0;

            foreach ($this->hashes as $frec) {
                $newsize += $frec;
            }

            $this->sizeh = $newsize;
        }
    }

    /**
     * Show information at current form
     *
     * @param bool $ext if true, show extended information
     * @return string the information to show
     */
    public function show_info($ext = false) {
        $ret = $this->from->show_info();

        if ($ext) {
            $htmls = vpl_s(self::$valueconverter);
            $ret .= 'valueconverter=' . $htmls . '<br>';
            $htmls = vpl_s($this->vecfrec);
            $ret .= 'vecfrec=' . $htmls . '<br>';
            $htmls = vpl_s($this->hashes);
            $ret .= 'hashes=' . $htmls . '<br>';
        }

        return $ret;
    }

    /**
     * Check access based on "from" source
     */
    public function can_access() {
        return $this->from->can_access();
    }

    /**
     * Get GID of current user
     */
    public function get_userid() {
        return $this->from->get_userid();
    }

    /**
     * Link parameters
     *
     * @param string $t the text to link
     * @return string the linked text
     */
    public function link_parms($t) {
        return $this->from->link_parms($t);
    }

    /**
     * Get similarity-1 among this file and other
     *
     * @param Object $other with the file info
     * @return number 0-100 %
     */
    public function similarity1(&$other) {
        $dif1 = $taken = 0;

        foreach ($this->vecfrec as $op => $frec) {
            if (isset($other->vecfrec[$op])) {
                if ($frec != $other->vecfrec[$op]) {
                    $dif1++;
                }

                $taken++;
            } else {
                $dif1++;
            }
        }

        $dif2 = count($other->vecfrec) - $taken;
        return 100 * (1 - (($dif1 + $dif2) / (count($this->vecfrec) + count($other->vecfrec))));
    }

    /**
     * Get similarity-2 among this file and other
     *
     * @param Object $other with the file info
     * @return number 0-100 %
     */
    public function similarity2(&$other) {
        $dif = $taken = 0;

        foreach ($this->vecfrec as $op => $frec) {
            if (isset($other->vecfrec[$op])) {
                $dif += abs($other->vecfrec[$op] - $frec);
                $taken += $other->vecfrec[$op];
            } else {
                $dif += $frec;
            }
        }

        $dif += $other->get_size() - $taken;
        return 100 * (1 - ($dif / ($this->size + $other->get_size())));
    }

    /**
     * Get similarity-3 among this file and other
     *
     * @param Object $other with the file info
     * @return number 0-100 %
     */
    public function similarity3(&$other) {
        $dif = $taken = 0;

        foreach ($this->hashes as $hash => $frec) {
            if (isset($other->hashes[$hash])) {
                $dif += abs($other->hashes[$hash] - $frec);
                $taken += $other->hashes[$hash];
            } else {
                $dif += $frec;
            }
        }

        $dif += $other->get_sizeh() - $taken;
        return 100 * (1 - ($dif / ($this->sizeh + $other->get_sizeh())));
    }

    /**
     * Clone passed token updating its value
     *
     * @param token $token to clone
     * @param string $value value to update at new copy
     */
    public static function clone_token($token, $value) {
        return new token($token->type, $value, $token->line);
    }
}

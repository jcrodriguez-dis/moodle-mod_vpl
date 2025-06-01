/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#pragma once
#include <sys/time.h>

/**
 * Class Timer Declaration
 */
class Timer{
	time_t startTimeSec;
	suseconds_t startTimeUsec;
public:
	Timer();
	double elapsedTime();
	static double time();
};

/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#include <cstddef>
#include "timer.hpp"

/**
 * Class Timer Definitions
 */

 Timer::Timer() {
	struct timeval current_time;
    gettimeofday(&current_time, NULL);
	this->startTimeSec = current_time.tv_sec;
	this->startTimeUsec = current_time.tv_usec;
}

double Timer::elapsedTime() {
	struct timeval current_time;
    gettimeofday(&current_time, NULL);
	double value = current_time.tv_sec - this->startTimeSec;
	value += (current_time.tv_usec - this->startTimeUsec) / 1000000.0;
	return value;
}

double Timer::time() {
	struct timeval current_time;
    gettimeofday(&current_time, NULL);
	double value = current_time.tv_sec;
	value += current_time.tv_usec / 1000000.0;
	return value;
}

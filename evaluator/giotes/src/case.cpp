/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

 #include <limits>
 #include <string>
 #include <vector>
 #include "tools.hpp"
 #include "message_constants.hpp"
 #include "case.hpp"
 
 using namespace std;
 
 /**
 * Class Case Definitions
 * Case represents cases
 */

Case::Case() {
	reset();
}

void Case::reset() {
	input = "";
	output.clear();
	caseDescription = "";
	gradeReduction = std::numeric_limits<float>::min();
	failMessage = DefaultMessage::fail_output;
	passMessage = "";
	timeoutMessage = DefaultMessage::timeout;
	failExitCodeMessage = DefaultMessage::fail_exit_code;
	programToRun = "";
	programArgs = "";
	variation = "";
	expectedExitCode = -1;
	checkExitCode = false;
	checkExitCodeAndOutput = false;
	titleFormat = DefaultMessage::title_format;
	timeLimit = 0;
}

void Case::setInput(string s) {
	input = s;
}

const string& Case::getInput() const {
	return input;
}

void Case::addOutput(string o) {
	output.push_back(o);
}

const vector< string > & Case::getOutput() const {
	return output;
}

void Case::setFailMessage(const string &s) {
	failMessage = s;
}

const string& Case::getFailMessage() const {
	return failMessage;
}

void Case::setPassMessage(const string &s) {
	passMessage = s;
}

const string& Case::getPassMessage() const {
	return passMessage;
}

void Case::setTimeoutMessage(const string &s) {
	timeoutMessage = s;
}

const string& Case::getTimeoutMessage() const {
	return timeoutMessage;
}

void Case::setFailExitCodeMessage(const string &s) {
	failExitCodeMessage = s;
}

const string& Case::getFailExitCodeMessage() const {
	return failExitCodeMessage;
}

void Case::setCaseDescription(const string &s) {
	caseDescription = s;
}

const string& Case::getCaseDescription() const {
	return caseDescription;
}

void Case::setTitleFormat(const string &s) {
	titleFormat = s;
}

const string& Case::getTitleFormat() const {
	return titleFormat;
}

void Case::setGradeReduction(float g) {
	gradeReduction = g;
}

float Case::getGradeReduction() const {
	return gradeReduction;
}

void Case::setExpectedExitCode(int e) {
	checkExitCode = true;
	if (e < 0) {
		e = -e;
		checkExitCodeAndOutput = true;
	} else if(e > 0) {
		checkExitCodeAndOutput = false;
	}
	expectedExitCode = e;
}

int Case::getExpectedExitCode() const {
	return expectedExitCode;
}

bool Case::getCheckExitCode() const {
	return checkExitCode;
}

bool Case::getCheckExitCodeAndOutput() const {
	return checkExitCodeAndOutput;
}

void Case::setProgramToRun(const string &s) {
	programToRun = s;
}

const string& Case::getProgramToRun() const {
	return programToRun;
}

void Case::setProgramArgs(const string &s) {
	programArgs = s;
}

const string& Case::getProgramArgs() const {
	return programArgs;
}

void Case::setVariation(const string &s) {
	variation = Tools::toLower(Tools::trim(s));
}

const string& Case::getVariation() const {
	return variation;
}

void Case::setTimeLimit(double value) {
	if (value > 0) {
		timeLimit = value;
	}
}

double Case::getTimeLimit() const {
	return timeLimit;
}

/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#pragma once

#include "output_checker.hpp"

/**
 * Class Case Declaration
 * Case represents cases
 */
class Case {
	string input;
	vector<string> output;
	string caseDescription;
	float gradeReduction;
	string failMessage;
	string passMessage;
	string timeoutMessage;
	string failExitCodeMessage;
	string titleFormat;
	string programToRun;
	string programArgs;
	bool checkExitCode;
	bool checkExitCodeAndOutput;
	int expectedExitCode;
	string variation;
	double timeLimit;
public:
	Case();
	void reset();
	void setInput(string );
	const string& getInput() const;
	void addOutput(string );
	const vector< string > & getOutput() const;
	void setFailMessage(const string &);
	const string& getFailMessage() const;
	void setPassMessage(const string &);
	const string& getPassMessage() const;
	void setTimeoutMessage(const string &);
	const string& getTimeoutMessage() const;
	void setFailExitCodeMessage(const string &);
	const string& getFailExitCodeMessage() const;
	void setTitleFormat(const string &);
	const string& getTitleFormat() const;
	void setCaseDescription(const string &);
	const string& getCaseDescription() const;
	void setGradeReduction(float);
	float getGradeReduction() const;
	void setExpectedExitCode(int);
	int getExpectedExitCode() const;
	bool getCheckExitCode() const;
	bool getCheckExitCodeAndOutput() const;
	void setProgramToRun(const string &);
	const string& getProgramToRun() const;
	void setProgramArgs(const string &);
	const string& getProgramArgs() const;
	void setVariation(const string &);
	const string& getVariation() const;
	void setTimeLimit(double);
	double getTimeLimit() const;
};

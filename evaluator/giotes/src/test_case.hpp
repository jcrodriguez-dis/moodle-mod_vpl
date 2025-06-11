/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */
#pragma once
#include "case.hpp"
#define bufferSize 2048
#define argvSize 200
/**
 * Class TestCase Declaration
 * TestCase represents cases to tested
 */
class TestCase {
	string command;
	const char *argv[argvSize];
	char argvbuf[bufferSize];
	static const char **envv;
	int id;
	bool correctOutput;
	bool outputTooLarge;
	bool programTimeout;
	bool executionError;
	bool correctExitCode;
	string executionErrorReason;
	int sizeReaded;
	Case caso;
	vector< OutputChecker* > output;
	float gradeReductionApplied;
	int exitCode; // Default value std::numeric_limits<int>::min()
	string programOutputBefore, programOutputAfter, programOutputClean, programInput;

	void cutOutputTooLarge(string &output);
	void readWrite(int fdread, int fdwrite);
	void addOutput(const string &o, const string &actualCaseDescription);
	void setTerminalMode(struct termios &term);
	string formatCustomComment(const string &comment);
public:
	TestCase(int id, const Case &caso);
	TestCase(const TestCase &o);
	~TestCase();
	static void setEnvironment(const char **environment);
	void setDefaultCommand();
	TestCase& operator=(const TestCase &o);
	double getTimeLimit();
	void updateTimeLimit(double value);
	bool passTest();
	bool passExitCode();
	bool passError();
	bool isExitCodeTested();
	bool isExecutionError() {
		return executionError;
	}
	bool isOutputTooLarge() {
		return outputTooLarge;
	}
	bool isProgramTimeout() {
		return programTimeout;
	}
	bool isCorrectOutput() {
		return correctOutput;
	}
	bool isCorrectExitCode() {
		return correctExitCode;
	}
	float getGradeReduction();
	void setGradeReductionApplied(float r);
	float getGradeReductionApplied();
	string getCaseDescription();
	string getCommentTitle(bool withGradeReduction=false);
	string getComment();
	bool hasPassMessage() {
		return caso.getPassMessage().length() > 0;
	}
	string getPassMessage() {
		return caso.getPassMessage();
	}
	string getTestResultMark();
	void splitArgs(string);
	void runTest();
	bool match(string data);
};

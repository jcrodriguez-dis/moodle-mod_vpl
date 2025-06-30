/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#pragma once

#include <string>
#include <vector>
#include <iostream>
#include <cstring>
#include <cstdio>
#include <algorithm>

#include "limits.hpp"
#include "timer.hpp"
#include "stop.hpp"
#include "test_case.hpp"
#include "case.hpp"
#include "tools.hpp"
#include "message_constants.hpp"

using namespace std;


const char *CASE_TAG = "case=";
const char *INPUT_TAG = "input=";
const char *OUTPUT_TAG = "output=";
const char *MULTILINE_END_TAG = "multiline end=";
const char *GRADEREDUCTION_TAG = "grade reduction=";
const char *FAILMESSAGE_TAG = "fail message=";
const char *FAILOUTPUTMESSAGE_TAG = "fail output message=";
const char *PASSMESSAGE_TAG = "pass message=";
const char *TIMEOUTMESSAGE_TAG = "timeout message=";
const char *FAILEXITCODEMESSAGE_TAG = "fail exit code message=";
const char *PROGRAMTORUN_TAG = "program to run=";
const char *PROGRAMARGS_TAG = "program arguments=";
const char *EXPECTEDEXITCODE_TAG = "expected exit code=";
const char *VARIATION_TAG = "variation=";
const char *TIMELIMIT_TAG = "time limit=";
const char *FINALREPORMESSAGE_TAG = "final report message=";
const char *CASETITLEFORMAT_TAG = "case title format=";
const char *FAILMARK_TAG = "fail mark=";
const char *PASSMARK_TAG = "pass mark=";
const char *ERRORMARK_TAG = "error mark=";
const char *TIMEOUTMARK_TAG = "timeout mark=";
const char *allTags[] = {
	CASE_TAG,
	INPUT_TAG,
	OUTPUT_TAG,
	MULTILINE_END_TAG,
	GRADEREDUCTION_TAG,
	FAILMESSAGE_TAG,
	FAILOUTPUTMESSAGE_TAG,
	PASSMESSAGE_TAG,
	TIMEOUTMESSAGE_TAG,
	FAILEXITCODEMESSAGE_TAG,
	PROGRAMTORUN_TAG,
	PROGRAMARGS_TAG,
	EXPECTEDEXITCODE_TAG,
	VARIATION_TAG,
	TIMELIMIT_TAG,
	FINALREPORMESSAGE_TAG,
	CASETITLEFORMAT_TAG,
	FAILMARK_TAG,
	PASSMARK_TAG,
	ERRORMARK_TAG,
	TIMEOUTMARK_TAG,
	NULL
};

/**
 * Class Evaluation Declaration
 */
class Evaluation {
	double maxtime;
	float grademin, grademax;
	string variation;
	bool noGrade;
	float grade;
	int nruns, nfails, nerrors, ntimeout;
	vector<TestCase> testCases;
	char comments[MAXCOMMENTS + 1][MAXCOMMENTSLENGTH + 1];
	char titles[MAXCOMMENTS + 1][MAXCOMMENTSTITLELENGTH + 1];
	char titlesGR[MAXCOMMENTS + 1][MAXCOMMENTSTITLELENGTH + 1];
	volatile int ncomments;
	volatile bool stopping;
	static Evaluation *singlenton;
	string finalReportMessage;
	string failmark;
	string passmark;
	string errormark;
	string timeoutmark;
	string fatalErrors;
	Evaluation();
public:
	static Evaluation* getSinglenton();
	static void deleteSinglenton();
	void addTestCase(Case &);
	bool isEndTag(string &rawValue, const string &endTag);
	bool cutToEndTag(string &value, const string &endTag);
	bool isValidTag(const string& tag);
	void loadTestCases(string fname);
	bool loadParams();
	void setFinalReportMessage(const string &message) {
		finalReportMessage = message;
	}
	int getNumTestCases() {
		return testCases.size();
	}
	int getNumTestCasesRun() {
		return nruns;
	}
	int getNumTestCasesFailed() {
		return nfails;
	}
	int getNumTestCasesPassed() {
		return nruns - nfails - nerrors - ntimeout;
	}
	int getNumTestCasesTimeout() {
		return ntimeout;
	}
	int getNumTestCasesError() {
		return nerrors;
	}
	string getFailMark() {
		return failmark;
	}
	void setFailMark(const string &m) {
		failmark = m;
	}
	string getPassMark() {
		return passmark;
	}
	void setPassMark(const string &m) {
		passmark = m;
	}
	string getErrorMark() {
		return errormark;
	}
	void setErrorMark(const string &m) {
		errormark = m;
	}
	string getTimeoutMark() {
		return timeoutmark;
	}
	void setTimeoutMark(const string &m) {
		timeoutmark = m;
	}
	void addFatalError(const string &m);
	void addCaseReport(TestCase &testCase);
	void applyGradeReduction(TestCase &testCase, float defaultGradeReduction);
	void runTests();
	void outputVPLformat(const string &message);
	void outputEvaluation();
};

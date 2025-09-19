/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#include "evaluation.hpp"

Evaluation* Evaluation::singlenton = NULL;

/**
 * Class Evaluation Definitions
 */

 Evaluation::Evaluation() {
	grade = 0;
	ncomments = 0;
	nerrors = 0;
	nruns = 0;
	nfails = 0;
	ntimeout = 0;
	noGrade = true;
	stopping = false;
	maxtime = 0;
	grademin = 0;
	grademax = 0;
	variation = "";
	finalReportMessage = DefaultMessage::final_report;
	failmark = DefaultMessage::fail_mark;
	passmark = DefaultMessage::pass_mark;
	errormark = DefaultMessage::error_mark;
	timeoutmark = DefaultMessage::timeout_mark;
	for (int i = 0; i < MAXCOMMENTS + 1; i++) {
		comments[i][0] = '\0';
		titles[i][0] = '\0';
		titlesGR[i][0] = '\0';
	}
	ncomments = 0;
}

Evaluation* Evaluation::getSinglenton() {
	if (singlenton == NULL) {
		singlenton = new Evaluation();
	}
	return singlenton; // Fixes by Jan Derriks
}

void Evaluation::deleteSinglenton(){
	if (singlenton != NULL) {
		delete singlenton;
		singlenton = NULL;
	}
}

void Evaluation::addTestCase(Case &caso) {
	if ( caso.getVariation().size() && caso.getVariation() != variation ) {
		return;
	}
	if(caso.getOutput().size() == 0) {
		caso.addOutput("");
	}
	testCases.push_back(TestCase(testCases.size() + 1, caso));
}

bool Evaluation::isEndTag(string &rawValue, const string &endTag) {
	string value = Tools::trim(rawValue);
	if (value.size() < endTag.size()) {
		return false;
	}
	size_t pos = value.size() - endTag.size();
	return endTag == value.substr(pos);
}

bool Evaluation::cutToEndTag(string &value, const string &endTag) {
	size_t pos;
	if (endTag.size() && (pos = value.rfind(endTag)) != string::npos) {
		value.resize(pos);
		return true;
	}
	return false;
}

bool Evaluation::isValidTag(const string& tag) {
	for ( int i = 0; allTags[i] != NULL; i++) {
		if (tag == allTags[i]) {
			return true;
		}
	}
	return false;
}

void Evaluation::loadTestCases(string fname) {
	if(!Tools::existFile(fname)) return;
	enum {
		regular, inInput, inOutput,
		inFailMessage, inPassMessage, inFailExitCodeMessage,
		inTimeoutMessage, inFinalReportMessage,
	} state;
	bool inCase = false;
	vector<string> lines;
	{
		// Read the file and remove CRs
		string fileContent = Tools::readFile(fname);
		Tools::removeCRs(fileContent);
		lines = Tools::splitLines(fileContent);
	}
	#ifndef DEBUG
    remove(fname.c_str()); // Remove config file to avoid cheating
	#endif
	Case defaultCaseValues;
	Case currentCase;
	string multiLineParameter = "";
	string multiLineEnd = "";
	string tag, value;
	/* must be changed from String
	 * to pair type (regexp o no) and string. */
	state = regular;
	int nlines = lines.size();
	for (int i = 0; i < nlines; i++) {
		string &line = lines[i];
		Tools::parseLine(line, tag, value);
		if (state == inInput) {
			if (multiLineEnd.size()) { // Check for end of input.
				if (!isEndTag(line, multiLineEnd)) {
					multiLineParameter += line + '\n';
				} else {
					cutToEndTag(line, multiLineEnd);
					multiLineParameter += line + '\n';
					currentCase.setInput(multiLineParameter);
					multiLineEnd = "";
					state = regular;
					continue; // Next line.
				}
			} else if (isValidTag(tag)) {// New valid tag.
				currentCase.setInput(multiLineParameter);
				state = regular;
				// Go on to process the current tag.
			} else {
				multiLineParameter += line + '\n';
				continue; // Next line.
			}
		} else if (state == inOutput) {
			if (multiLineEnd.size()) { // Check for end of output.
				if (!isEndTag(line, multiLineEnd)) {
					multiLineParameter += line + "\n";
				} else {
					cutToEndTag(line, multiLineEnd);
					multiLineParameter += line;
					currentCase.addOutput(multiLineParameter);
					state = regular;
					multiLineEnd = "";
					continue; // Next line.
				}
			} else if (isValidTag(tag)) {// New valid tag.
				currentCase.addOutput(multiLineParameter);
				state = regular;
			} else {
				multiLineParameter += line + "\n";
				continue; // Next line.
			}
		} else if (state == inFailMessage ||
		           state == inFailExitCodeMessage ||
				   state == inTimeoutMessage ||
				   state == inFinalReportMessage ||
				   state == inPassMessage) {
			if (multiLineEnd.size()) { // Check for end of message.
				if (! isEndTag(line, multiLineEnd)) {
					multiLineParameter += line + "\n";
				} else {
					cutToEndTag(line, multiLineEnd);
					multiLineParameter += line;
					switch (state) {
					case inFailMessage:
						currentCase.setFailMessage(multiLineParameter);
						break;
					case inPassMessage:
						currentCase.setPassMessage(multiLineParameter);
						break;
					case inFailExitCodeMessage:
						currentCase.setFailExitCodeMessage(multiLineParameter);
						break;
					case inTimeoutMessage:
						currentCase.setTimeoutMessage(multiLineParameter);
						break;
					case inFinalReportMessage:
						this->setFinalReportMessage(multiLineParameter);
						break;
					}
					multiLineEnd = "";
					state = regular;
					continue; // Next line.
				}
			} else
			if (isValidTag(tag)) { // New valid tag.
				switch (state) {
				case inFailMessage:
					currentCase.setFailMessage(multiLineParameter);
					break;
				case inPassMessage:
					currentCase.setPassMessage(multiLineParameter);
					break;
				case inFailExitCodeMessage:
					currentCase.setFailExitCodeMessage(multiLineParameter);
					break;
				case inTimeoutMessage:
					currentCase.setTimeoutMessage(multiLineParameter);
					break;
				case inFinalReportMessage:
					this->setFinalReportMessage(multiLineParameter);
					break;
				}
				state = regular;
			} else {
				multiLineParameter += line + "\n";
				continue; // Next line.
			}
		}
		if (state == regular) {
			if (tag.size()) {
				if (tag == INPUT_TAG) {
					if (cutToEndTag(value, multiLineEnd)) {
						currentCase.setInput(value);
					} else {
						state = inInput;
						multiLineParameter = Tools::removeFirstSpace(value) + '\n';
					}
				} else if (tag == OUTPUT_TAG) {
					if (cutToEndTag(value, multiLineEnd)) {
						currentCase.addOutput(value);
					} else {
						state = inOutput;
						multiLineParameter = Tools::removeFirstSpace(value) + '\n';
					}
				} else if (tag == GRADEREDUCTION_TAG) {
					value = Tools::trim(value);
					// A percent value?
					if( value.size() > 1 && value[ value.size() - 1 ] == '%' ){
						float percent = atof(value.c_str());
						currentCase.setGradeReduction((grademax-grademin)*percent/100);
					}else{
						currentCase.setGradeReduction( atof(value.c_str()) );
					}
				} else if (tag == EXPECTEDEXITCODE_TAG) {
					currentCase.setExpectedExitCode( atoi(value.c_str()) );
				} else if (tag == PROGRAMTORUN_TAG) {
					currentCase.setProgramToRun(Tools::trim(value));
				} else if (tag == PROGRAMARGS_TAG) {
					currentCase.setProgramArgs(Tools::trim(value));
				} else if (tag == FAILMESSAGE_TAG ||
				           tag == FAILOUTPUTMESSAGE_TAG) {
					state = inFailMessage;
					multiLineParameter = Tools::removeFirstSpace(value) + '\n';
				} else if (tag == PASSMESSAGE_TAG) {
					state = inPassMessage;
					multiLineParameter = Tools::removeFirstSpace(value) + '\n';
				} else if (tag == TIMEOUTMESSAGE_TAG) {
					state = inTimeoutMessage;
					multiLineParameter = Tools::removeFirstSpace(value) + '\n';
				} else if (tag == FAILEXITCODEMESSAGE_TAG) {
					state = inFailExitCodeMessage;
					multiLineParameter = Tools::removeFirstSpace(value) + '\n';
				} else if (tag == FINALREPORMESSAGE_TAG) {
					state = inFinalReportMessage;
					multiLineParameter = Tools::removeFirstSpace(value) + '\n';
				} else if (tag == VARIATION_TAG) {
					currentCase.setVariation(value);
				} else if (tag == TIMELIMIT_TAG) {
					currentCase.setTimeLimit(atof(value.c_str()));
				} else if (tag == MULTILINE_END_TAG) {
					multiLineEnd = Tools::trim(value);
				} else if (tag == FAILMARK_TAG) {
					setFailMark(Tools::trim(value));
				} else if (tag == PASSMARK_TAG) {
					setPassMark(Tools::trim(value));
				} else if (tag == ERRORMARK_TAG) {
					setErrorMark(Tools::trim(value));
				} else if (tag == TIMEOUTMARK_TAG) {
					setTimeoutMark(Tools::trim(value));
				} else if (tag == CASETITLEFORMAT_TAG) {
					currentCase.setTitleFormat(Tools::trim(value));
				} else if (tag == CASE_TAG) {
					if (inCase) {
						addTestCase(currentCase);
						multiLineEnd = "";
						multiLineParameter = "";
						currentCase = defaultCaseValues;
					} else {
						inCase = true;
						defaultCaseValues = currentCase;
					}
					currentCase.setCaseDescription(Tools::trim(value));
				} else {
					addFatalError(getString(str_error_parameter_unknow, i + 1));
				}
			} else {
				if ( line.size() > 0 ) {
					string content = Tools::trim(line);
					if (content.size() > 0 && content[0] != '#') {
						addFatalError(getString(str_error_text_out, i + 1));
					}
				}
			}
		}
	}
	// TODO review
	switch (state) {
		case inOutput:
			Tools::removeLastNL(multiLineParameter);
			currentCase.addOutput(multiLineParameter);
			break;
		case inPassMessage:
		    currentCase.setPassMessage(multiLineParameter);
			break;
		case inInput:
			currentCase.setInput(multiLineParameter);
			break;
		case inFailMessage:
			currentCase.setFailMessage(multiLineParameter);
			break;
		case inFailExitCodeMessage:
			currentCase.setFailExitCodeMessage(multiLineParameter);
			break;
		case inTimeoutMessage:
			currentCase.setTimeoutMessage(multiLineParameter);
			break;
		case inFinalReportMessage:
			this->setFinalReportMessage(multiLineParameter);
			break;
	}
	if (inCase) { // Last case => save current.
		addTestCase(currentCase);
	}
	if (testCases.size() == 0) {
		addFatalError(getString(str_no_test_cases));
	}
}

bool Evaluation::loadParams() {
	grademin= Tools::getenv("VPL_GRADEMIN", 0.0);
	grademax = Tools::getenv("VPL_GRADEMAX", 10);
	maxtime = Tools::getenv("VPL_MAXTIME", 20);
	variation = Tools::toLower(Tools::trim(Tools::getenv("VPL_VARIATION","")));
	noGrade = grademin >= grademax;
	return true;
}

void Evaluation::addFatalError(const string &error) {
	fatalErrors += error + "\n";
	grade = grademin;
}

void Evaluation::addCaseReport(TestCase &testCase) {
	if(ncomments < MAXCOMMENTS){
		strncpy(titles[ncomments], testCase.getCommentTitle().c_str(),
				MAXCOMMENTSTITLELENGTH);
		strncpy(titlesGR[ncomments], testCase.getCommentTitle(true).c_str(),
				MAXCOMMENTSTITLELENGTH);
		strncpy(comments[ncomments], testCase.getComment().c_str(),
				MAXCOMMENTSLENGTH);
		ncomments++;
	}
}

void Evaluation::applyGradeReduction(TestCase &testCase, float defaultGradeReduction) {
	float gr = testCase.getGradeReduction();
	if (gr == std::numeric_limits<float>::min())
		testCase.setGradeReductionApplied(defaultGradeReduction);
	else
		testCase.setGradeReductionApplied(gr);
	grade -= testCase.getGradeReductionApplied();
	if (grade < grademin) {
		grade = grademin;
	}
}

void Evaluation::runTests() {
	printf("VPL GIOTES General Input Output Test Evaluation System\n");
	printf("Time limit: %.2f\n", maxtime);
	if (noGrade) {
		printf("No grade\n");
	} else {
		printf("Grade range: %.2f - %.2f\n", grademin, grademax);
	}
	if (variation.size() > 0) {
		printf("Variation: %s\n", variation.c_str());
	}
	printf("Test cases: %lu\n", (unsigned long)testCases.size());
	if (fatalErrors.size() > 0) {
		printf("Fatal errors: some tests not run\n");
		return;
	}
	nfails = 0;
	ntimeout = 0;
	nerrors = 0;
	nruns = 0;
	grade = grademax;
	float defaultGradeReduction = (grademax - grademin) / testCases.size();
	double defaultTestTimeLimit = maxtime / testCases.size();
	for (size_t i = 0; i < testCases.size(); i++) {
		testCases[i].updateTimeLimit(defaultTestTimeLimit);
	}
	Timer globalTimer;
	for (size_t i = 0; i < testCases.size(); i++) {
		printf("Testing %lu/%lu: %s\n", (unsigned long) i+1, (unsigned long)testCases.size(), testCases[i].getCaseDescription().c_str());
		if (globalTimer.elapsedTime() >= maxtime) {
			addFatalError(getString(str_global_timeout));
			return;
		}
		if (Stop::isTERMRequested()) {
			addFatalError(getString(str_stop_requested));
			return;
		}
		TestCase& testCase = testCases[i];
		testCase.runTest();
		nruns++;
		if (! testCase.passTest()) {
			applyGradeReduction(testCase, defaultGradeReduction);
			if (testCase.isProgramTimeout()) {
				ntimeout++;
			} else if (testCase.isExecutionError()) {
				nerrors++;
			} else {
				nfails++;
			}
			addCaseReport(testCase);
		} else {
			if (testCase.hasPassMessage()) {
				addCaseReport(testCase);
			}
		}
	}
}

void Evaluation::outputVPLformat(const string &message) {
	if (message.size() > 0) {
		printf("<|--\n");
		printf("%s", message.c_str());
		if (message[message.size() - 1] != '\n') {
			printf("\n");
		}
		printf("--|>\n");
	}
}

void Evaluation::outputEvaluation() {
	if (fatalErrors.size() > 0) {
		string report = "-" + getString(str_fatal_errors) + "\n" + fatalErrors;
		outputVPLformat(report);
	}
	if (ncomments > 0 ) {
		string report = "";
		for (int i = 0; i < ncomments; i++) {
			report += "-" + string(titlesGR[i]) + "\n";
			report += string(comments[i]) + "\n";
		}
		outputVPLformat(report);
	}
	if ( nruns > 0 ) {
		string finalReport = this->finalReportMessage;
		Tools::replaceAll(finalReport, MessageMarks::num_tests, Tools::int2str(getNumTestCases(), 2));
		Tools::replaceAll(finalReport, MessageMarks::num_tests_run, Tools::int2str(getNumTestCasesRun(), 2));
		Tools::replaceAll(finalReport, MessageMarks::num_tests_passed, Tools::int2str(getNumTestCasesPassed(), 2));
		Tools::replaceAll(finalReport, MessageMarks::num_tests_failed, Tools::int2str(getNumTestCasesFailed(), 2));
		Tools::replaceAll(finalReport, MessageMarks::num_tests_timeout, Tools::int2str(getNumTestCasesTimeout(), 2));
		Tools::replaceAll(finalReport, MessageMarks::num_tests_error, Tools::int2str(getNumTestCasesError(), 2));
		outputVPLformat(finalReport);
	}
	if ( ! noGrade ) {
		char buf[100];
		sprintf(buf, "%5.2f", grade);
		int len = strlen(buf);
		if (len > 3 && strcmp(buf + (len - 3), ".00") == 0)
			buf[len - 3] = 0;
		printf("\nGrade :=>>%s\n", buf);
	}
	fflush(stdout);
}

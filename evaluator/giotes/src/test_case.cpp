/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#include <cstdlib>
#include <cstdio>
#include <unistd.h>
#include <sys/types.h>
#include <sys/wait.h>
#include <poll.h>
#include <csignal>
#include <cstring>
#include <cerrno>
#include <fcntl.h>
#include <termios.h>
#include <pty.h>
#include <iostream>

#include "limits.hpp"
#include "stop.hpp"
#include "timer.hpp"
#include "test_case.hpp"

const char **TestCase::envv=NULL;

/**
 * Class TestCase Definitions
 * TestCase represents cases of test
 */

void TestCase::cutOutputTooLarge(string &output) {
	if (output.size() > MAXOUTPUT) {
		outputTooLarge = true;
		output.erase(0, output.size() - MAXOUTPUT);
	}
}

void TestCase::readWrite(int fdread, int fdwrite) {
	static string lastLineWritten;
	static bool isLastLineFullWritten = false;
	const int MAX = 1024* 10 ;
	// Buffer size to read
	const int POLLREAD = POLLIN | POLLPRI;
	// Poll to read from program
	struct pollfd devices[2];
	devices[0].fd = fdread;
	devices[1].fd = fdwrite;
	char buf[MAX];
	buf[0] = '\0';
	devices[0].events = POLLREAD;
	devices[1].events = POLLOUT;
	int res = poll(devices, 2, 500);
	if (res == -1) // Error
		return;
	if (res == 0) // Nothing to do
		return;
	if (devices[0].revents & POLLREAD) { // Read program output
		int readed = read(fdread, buf, MAX);
		if (readed > 0) {
			sizeReaded += readed;
			buf[readed] = '\0';
			string lastReaded(buf, readed);
			if (programInput.size() > 0) {
				programOutputBefore += lastReaded;
				cutOutputTooLarge(programOutputBefore);
			} else {
				programOutputAfter += lastReaded;
				cutOutputTooLarge(programOutputAfter);
			}
			Tools::removeCRs(lastReaded);
			int firstLineSize = Tools::nextLine(lastReaded);
			string firstLineReaded = lastReaded.substr(0, firstLineSize);
			#ifdef DEBUG
				if (isLastLineFullWritten) {
					cout << "Last line written: " << lastLineWritten << endl;
					cout << "VS first line readed: " << firstLineReaded << endl;
				}
			#endif
			if (isLastLineFullWritten && Tools::removeInputLine(lastReaded, lastLineWritten)) {
				isLastLineFullWritten = false;
				lastLineWritten = "";
			}
			programOutputClean += lastReaded;
			cutOutputTooLarge(programOutputClean);
		}
	}
	if (devices[1].revents & POLLOUT) { // Write to program
	    if (programInput.size() > 0) {
			int lineLength = Tools::nextLine(programInput);
    		int written = write(fdwrite, programInput.c_str(), lineLength);
    		if (written > 0) {
				if (isLastLineFullWritten) {
					lastLineWritten = "";
					isLastLineFullWritten = false;
				}
				lastLineWritten += programInput.substr(0, written);
				programInput.erase(0, written);
				if (written == lineLength) {
					isLastLineFullWritten = true;
					usleep(1000);
					#ifdef DEBUG
						cout << "Line written: " << lastLineWritten << endl;
					#endif
				}
			}
	    } else {
	        // End of input then send EOF
			#ifdef DEBUG
				cout << "Send EOT" << endl;
			#endif
	        write(fdwrite, "\x04", 1);
			close(fdwrite);
	    }
	}
}

void TestCase::addOutput(const string &o, const string &actualCaseDescription){
// actualCaseDescripction, used to get current test name for Output recognition
	if(ExactTextOutput::typeMatch(o))
		this->output.push_back(new ExactTextOutput(o));
	else if (RegularExpressionOutput::typeMatch(o))
		this->output.push_back(new RegularExpressionOutput(o, actualCaseDescription));
	else if(NumbersOutput::typeMatch(o))
		this->output.push_back(new NumbersOutput(o));
	else
		this->output.push_back(new TextOutput(o));
}

void TestCase::setEnvironment(const char **environment) {
	envv = environment;
}

void TestCase::setDefaultCommand() {
	command = "./vpl_test";
	argv[0] = command.c_str();
	argv[1] = NULL;
}

TestCase::TestCase(int id, const Case &caso) {
	this->envv = NULL;
	this->id = id;
	this->caso = caso;
	const vector<string> &caseOutput = caso.getOutput();
	for(size_t i = 0; i < caseOutput.size(); i++){
		addOutput(caseOutput[i], caso.getCaseDescription());
	}
	exitCode = std::numeric_limits<int>::min();
	outputTooLarge = false;
	programTimeout = false;
	executionError = false;
	correctOutput = false;
	correctExitCode = false;
	sizeReaded = 0;
	gradeReductionApplied =0;
	executionErrorReason = "";
	setDefaultCommand();
}

TestCase::TestCase(const TestCase &o) {
	this->envv = NULL;
	id = o.id;
	correctOutput = o.correctOutput;
	correctExitCode = o.correctExitCode;
	outputTooLarge = o.outputTooLarge;
	programTimeout = o.programTimeout;
	executionError = o.executionError;
	executionErrorReason = o.executionErrorReason;
	sizeReaded = o.sizeReaded;
	caso = o.caso;
	gradeReductionApplied = o.gradeReductionApplied;
	programOutputBefore = o.programOutputBefore;
	programOutputAfter = o.programOutputAfter;
	programOutputClean = o.programOutputClean;
	programInput = o.programInput;
	for(size_t i = 0; i < o.output.size(); i++){
		output.push_back(o.output[i]->clone());
	}
	setDefaultCommand();
}

TestCase& TestCase::operator=(const TestCase &o) {
	id = o.id;
	correctOutput = o.correctOutput;
	correctExitCode = o.correctExitCode;
	outputTooLarge = o.outputTooLarge;
	programTimeout = o.programTimeout;
	executionError = o.executionError;
	executionErrorReason = o.executionErrorReason;
	sizeReaded = o.sizeReaded;
	caso = o.caso;
	gradeReductionApplied = o.gradeReductionApplied;
	programOutputBefore = o.programOutputBefore;
	programOutputAfter = o.programOutputAfter;
	programOutputClean = o.programOutputClean;
	programInput = o.programInput;
	for(size_t i = 0; i < output.size(); i++)
		delete output[i];
	output.clear();
	for(size_t i = 0; i < o.output.size(); i++){
		output.push_back(o.output[i]->clone());
	}
	setDefaultCommand();
	return *this;
}

TestCase::~TestCase() {
	for(size_t i = 0; i < output.size(); i++)
		delete output[i];
}

double TestCase::getTimeLimit() {
	return caso.getTimeLimit();
}

void TestCase::updateTimeLimit(double timeLimit) {
	if (caso.getTimeLimit() == 0) {
		caso.setTimeLimit(timeLimit);
	}
}

bool TestCase::passError() {
	return ! programTimeout && ! outputTooLarge && ! executionError;
}

bool TestCase::passExitCode() {
	return isExitCodeTested() && correctExitCode;
}

bool TestCase::passTest() {
	if (caso.getCheckExitCodeAndOutput()) {
		return (isCorrectOutput() && passExitCode()) && passError();
	} else {
		return (isCorrectOutput() || passExitCode()) && passError();
	}
}

bool TestCase::isExitCodeTested() {
	return caso.getCheckExitCode();
}

float TestCase::getGradeReduction() {
	return caso.getGradeReduction();
}

void TestCase::setGradeReductionApplied(float r) {
	gradeReductionApplied = r;
}

float TestCase::getGradeReductionApplied() {
	return gradeReductionApplied;
}

string TestCase::getCaseDescription(){
	return caso.getCaseDescription();
}

string TestCase::getTestResultMark() {
	Evaluation *evaluation = Evaluation::getSinglenton();
	string testResultMark;
	if (passTest()) {
		testResultMark = evaluation->getPassMark();
	} else if (programTimeout) {
		testResultMark = evaluation->getTimeoutMark();
	} else if (outputTooLarge || executionError) {
		testResultMark = evaluation->getErrorMark();
	} else {
		testResultMark = evaluation->getFailMark();
	}
	return testResultMark;
}

string TestCase::getCommentTitle(bool withGradeReduction) {
	string formattedTitle = caso.getTitleFormat();
	Evaluation *evaluation = Evaluation::getSinglenton();
	Tools::replaceAll(formattedTitle, MessageMarks::case_id, Tools::int2str(id));
	Tools::replaceAll(formattedTitle, MessageMarks::case_title, caso.getCaseDescription());
	Tools::replaceAll(formattedTitle, MessageMarks::test_result_mark, getTestResultMark());
	Tools::replaceAll(formattedTitle, MessageMarks::num_tests, Tools::int2str(evaluation->getNumTestCases()));
	Tools::replaceAll(formattedTitle, MessageMarks::pass_mark, evaluation->getPassMark());
	Tools::replaceAll(formattedTitle, MessageMarks::fail_mark, evaluation->getFailMark());
	Tools::replaceAll(formattedTitle, MessageMarks::error_mark, evaluation->getErrorMark());
	Tools::replaceAll(formattedTitle, MessageMarks::timeout_mark, evaluation->getTimeoutMark());
	if(withGradeReduction && getGradeReductionApplied() > 0){
		formattedTitle += " (" + Tools::double2str(-getGradeReductionApplied(), 3) + ")";
	}
	return formattedTitle;
}

string TestCase::formatCustomComment(const string &comment) {
	Evaluation *evaluation = Evaluation::getSinglenton();
	string formatedComment = comment;
	Tools::replaceAllML(formatedComment, MessageMarks::input, caso.getInput());
	Tools::replaceAllIL(formatedComment, MessageMarks::input_inline, caso.getInput());
	Tools::replaceAll(formatedComment, MessageMarks::check_type, output[0]->type());
	Tools::replaceAllML(formatedComment, MessageMarks::expected_output, output[0]->studentOutputExpected());
	Tools::replaceAllIL(formatedComment, MessageMarks::expected_output_inline, output[0]->studentOutputExpected());
	string programOutput = programOutputBefore + programOutputAfter;
	Tools::replaceAllML(formatedComment, MessageMarks::program_output, programOutput);
	Tools::replaceAllIL(formatedComment, MessageMarks::program_output_inline, programOutput);
	Tools::replaceAll(formatedComment, MessageMarks::expected_exit_code, Tools::int2str(caso.getExpectedExitCode()));
	Tools::replaceAll(formatedComment, MessageMarks::exit_code, Tools::int2str(exitCode));
	Tools::replaceAll(formatedComment, MessageMarks::time_limit, Tools::double2str(caso.getTimeLimit(), 2));
	Tools::replaceAll(formatedComment, MessageMarks::case_id, Tools::int2str(id));
	Tools::replaceAll(formatedComment, MessageMarks::case_title, caso.getCaseDescription());
	Tools::replaceAll(formatedComment, MessageMarks::grade_reduction, Tools::double2str(getGradeReductionApplied(), 3));
	Tools::replaceAll(formatedComment, MessageMarks::test_result_mark, getTestResultMark());
	Tools::replaceAll(formatedComment, MessageMarks::pass_mark, evaluation->getPassMark());
	Tools::replaceAll(formatedComment, MessageMarks::fail_mark, evaluation->getFailMark());
	Tools::replaceAll(formatedComment, MessageMarks::error_mark, evaluation->getErrorMark());
	Tools::replaceAll(formatedComment, MessageMarks::timeout_mark, evaluation->getTimeoutMark());
	return formatedComment;
}

string TestCase::getComment() {
	if (passTest()) {
		string passMessage = caso.getPassMessage();
		if (passMessage.size() > 0) {
			return formatCustomComment(passMessage);
		}
		return "";
	}
	char buffer[100];
	string ret;
	if (executionError) {
		ret += executionErrorReason + "\n";
	}
	if (outputTooLarge) {
		ret += getString(str_output_too_large, sizeReaded / 1024) + "\n";
	}
	if (programTimeout) {
		ret += formatCustomComment(caso.getTimeoutMessage());
		return ret;
	}
	if (isExitCodeTested() && ! correctExitCode) {
		ret += formatCustomComment(caso.getFailExitCodeMessage());
		return ret;
	}
	if (! correctOutput) {
		ret += formatCustomComment(caso.getFailMessage());
		return ret;
	}
	return ret;
}

void TestCase::splitArgs(string programArgs) {
	int l = programArgs.size();
	int nargs = 1;
	if (l >= bufferSize - 1) {
		Evaluation::getSinglenton()->addFatalError(getString(str_command_line_too_long));
		return;
	}
	strncpy(argvbuf, programArgs.c_str(), bufferSize);
	argvbuf[bufferSize - 1] = '\0';
	argv[0] = command.c_str();
	bool inArg = false;
	char separator = ' ';
	for(int i=0; i < l; i++) {
		char c = argvbuf[i];
		if (c == '\0') {
			break;
		}
		if ( ! inArg ) {
			if ( c == ' ' ) {
				argvbuf[i] = '\0';
				separator = ' ';
				continue;
			}
			if ( c == '\'' || c == '"' ) {
				argvbuf[i] = '\0';
				separator = c;
			}
			inArg = true;
		} else {
			 if ( c == '\\' && argvbuf[i + 1] == separator ) {
				// Remove escape char
				memmove(argvbuf + i, argvbuf + i + 1, strlen(argvbuf + i));
			} else if ( c == separator  ) {
				argvbuf[i] = '\0';
				separator = ' ';
				inArg = false;
			}
		}
	}
	// Get and count args
	char prevc= '\0';
	for(int i=0; i < l; i++) {
		char c = argvbuf[i];
		if (c != '\0' && prevc == '\0') {
			argv[nargs++] = argvbuf + i;
			if (nargs == argvSize - 1) {
				Evaluation::getSinglenton()->addFatalError(getString(str_too_many_command_arguments));
				break;
			}
		}
		prevc = c;
	}
	argv[nargs] = NULL;
}

void TestCase::setTerminalMode(struct termios &term) {
    if (tcgetattr(STDIN_FILENO, &term) < 0) {
		// Full reset termios structure
		memset(&term, 0, sizeof(struct termios));
		// c_iflag - Input modes
		term.c_iflag = ICRNL | IXON;
		// c_cc - Special control characters		
        term.c_cc[VINTR] = 0x03;    // ^C
        term.c_cc[VQUIT] = 0x1c;    // ^backslash
        term.c_cc[VERASE] = 0x7f;   // ^?
        term.c_cc[VKILL] = 0x15;    // ^U
        term.c_cc[VSTART] = 0x11;   // ^Q
        term.c_cc[VSTOP] = 0x13;    // ^S
        term.c_cc[VSUSP] = 0x1a;    // ^Z
        term.c_cc[VREPRINT] = 0x12; // ^R
        term.c_cc[VWERASE] = 0x17;  // ^W
        term.c_cc[VLNEXT] = 0x16;   // ^V
        term.c_cc[VDISCARD] = 0x0f; // ^O
		cfsetospeed(&term, B115200);
	}
	term.c_cc[VMIN] = 1;
	term.c_cc[VTIME] = 0;
    term.c_cc[VEOF] = 0x04;     // ^D
	term.c_cflag |= CS8 | CREAD | CLOCAL;
    term.c_lflag |= ICANON | ISIG | IEXTEN;
	term.c_lflag &= ~(ECHO | ECHONL);
}

void TestCase::runTest() {// Timeout in seconds
	char buf[1024];
	double timeout = caso.getTimeLimit();
	if ( ! caso.getProgramToRun().empty() && caso.getProgramToRun().size() < 512) {
		command = caso.getProgramToRun();
	}
	if ( ! Tools::existFile(command) ){
		executionError = true;
		executionErrorReason = getString(str_execution_file_not_found, command);
		return;
	}
	pid_t pid;
	splitArgs(caso.getProgramArgs());
	struct termios term;
	setTerminalMode(term);
	int fdmaster = -1;
	if ((pid = forkpty(&fdmaster, NULL, &term, NULL)) == 0) {
		setpgrp();
		if (execve(command.c_str(), (char * const *) argv, (char * const *) envv) == -1) {
			perror("Internal error, execve fails");
			_exit(1); //end of child
		}
	}
	if (pid == -1 || fdmaster == -1) {
		executionError = true;
		executionErrorReason = getString(str_forkpty_error, string(strerror(errno)));
		return;
	}
	int fdread= dup(fdmaster);
	int fdwrite= dup(fdmaster);
	if (fdread == -1 || fdwrite == -1) {
		close(fdmaster);
		if(fdread != -1) close(fdread);
		if(fdwrite != -1) close(fdwrite);
		executionError = true;
		executionErrorReason = getString(str_forkpty_error, string(strerror(errno)));
		return;
	}
	// Set non-blocking mode
	Tools::fdblock(fdread, false);
	Tools::fdblock(fdwrite, false);
	programInput = caso.getInput();
	if(programInput.size() > 0 && programInput[programInput.size() - 1] != '\n') {
		programInput += "\n";
	}
	programOutputBefore = "";
	programOutputAfter = "";
	programOutputClean = "";
	pid_t pidr;
	int status;
	Timer timer;
	exitCode = std::numeric_limits<int>::min();
	while ((pidr = waitpid(pid, &status, WNOHANG | WUNTRACED)) == 0) {
		usleep(5000);
		readWrite(fdread, fdwrite);
		if (timer.elapsedTime() >= timeout) {
			programTimeout = true;
		}
		// TERMSIG or timeout or program output too large?
		if (Stop::isTERMRequested() || programTimeout || outputTooLarge) {
			kill(pid, SIGTERM); // Send SIGTERM normal termination
			int otherstatus;
			usleep(100000);
			if (waitpid(pid, &otherstatus, WNOHANG | WUNTRACED) == pid) {
			    status = otherstatus;
				break;
			}
			kill(pid, SIGKILL);
			if (waitpid(pid, &otherstatus, WUNTRACED) == pid) {
			    status = otherstatus;
			}
		}
	}
	if (pidr == pid) {
		if (WIFEXITED(status)) {
    			exitCode = WEXITSTATUS(status);
		} else {
			executionError = true;
			if (WIFSIGNALED(status)) {
				int signal = WTERMSIG(status);
				executionErrorReason = getString(str_program_terminated_by_signal,
												 "signal", string(strsignal(signal)),
												 "signum", Tools::int2str(signal));
			} else if (WIFSTOPPED(status)) {
				int signal = WSTOPSIG(status);
				executionErrorReason = getString(str_child_terminated_by_signal,
												 "signal", string(strsignal(signal)),
												 "signum", Tools::int2str(signal));
			} else if (WIFCONTINUED(status)) {
				executionErrorReason = getString(str_child_continued);
			} else {
				executionErrorReason = getString(str_program_terminated_by_unknown_reason, status);
			}
		}
	} else if (pidr != 0) {
		executionError = true;
		executionErrorReason = getString(str_waitpid_error);
	}
	readWrite(fdread, fdwrite);
	close(fdmaster);
	close(fdread);
	close(fdwrite);
	Tools::removeCRs(programOutputBefore);
	Tools::removeCRs(programOutputAfter);
	Tools::removeCRs(programOutputClean);
	#ifdef DEBUG
		cout << "Program exit code: " << exitCode << endl;
		cout << "Program output before: " << programOutputBefore << endl;
		cout << "Program output after: " << programOutputAfter << endl;
		cout << "Program output clean: " << programOutputClean << endl;
	#endif
	correctExitCode = isExitCodeTested() && caso.getExpectedExitCode() == exitCode;
	correctOutput = match(programOutputAfter)
				 || match(programOutputClean)
			     || match(programOutputBefore + programOutputAfter);
}

bool TestCase::match(string data) {
	if (output.size() == 0) {
		return true;
	}
	for (size_t i = 0; i < output.size(); i++)
		if (output[i]->match(data))
			return true;
	return false;
}

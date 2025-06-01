/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#include <csignal>
#include "i18n.hpp"
#include "message_constants.cpp"
#include "case.cpp"
#include "evaluation.cpp"
#include "output_checker.cpp"
#include "stop.cpp"
#include "test_case.cpp"
#include "tools.cpp"
#include "timer.cpp"

void nullSignalCatcher(int n) {
	#ifdef DEBUG
		printf("Signal ignored %d %s\n", n, strsignal(n));
	#endif
}

void signalCatcher(int n) {
	#ifdef DEBUG
		printf("Signal catched %d %s\n", n, strsignal(n));
	#endif
	Evaluation *eval = Evaluation::getSinglenton();
	if (Stop::isTERMRequested()) {
		eval->outputEvaluation();
		exit(0);
	}
	if (n == SIGTERM) {
		eval->addFatalError(getString(str_term_signal));
	} else {
		eval->addFatalError(getString(str_internal_error));
		eval->outputEvaluation();
		Stop::setTERMRequested();
		exit(0);
	}
	alarm(1);
}

void setSignalsCatcher() {
	// Removes as many signal controllers as possible
	for(int i=0;i<31; i++)
		signal(i, nullSignalCatcher);
	signal(SIGINT, signalCatcher);
	signal(SIGQUIT, signalCatcher);
	signal(SIGILL, signalCatcher);
	signal(SIGTRAP, signalCatcher);
	signal(SIGFPE, signalCatcher);
	signal(SIGSEGV, signalCatcher);
	signal(SIGALRM, signalCatcher);
	signal(SIGTERM, signalCatcher);
}

int main(int argc, char *argv[], const char **env) {
	#ifdef DEBUG
		printf("Debugging mode\n");
	#endif
	string caseFileName = "evaluate.cases";
	if (argc > 1) {
		caseFileName = argv[1];
	}
	TestCase::setEnvironment(env);
	setSignalsCatcher();
	Evaluation* eval = Evaluation::getSinglenton();
	eval->loadParams();
	eval->loadTestCases(caseFileName);
	eval->runTests();
	eval->outputEvaluation();
	return EXIT_SUCCESS;
}

	/**
	 * VPL builtin program for submissions evaluation
	 * @Copyright (C) 2019 Juan Carlos Rodríguez-del-Pino
	 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
	 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
	 */


	
	#include <cstdlib>
	#include <cstdio>
	#include <climits>
	#include <limits>
	#include <errno.h>
	#include <sys/types.h>
	#include <sys/wait.h>
	#include <poll.h>
	#include <unistd.h>
	#include <pty.h>
	#include <fcntl.h>
	#include <signal.h>
	#include <cstring>
	#include <string>
	#include <iostream>
	#include <sstream>
	#include <vector>
	#include <cmath>
	#include <execinfo.h>
	#include <regex.h>
	#include <string>

	//Added by Tamar
	#include <chrono>
	#include <regex>
	#include <random>
	#include <fstream>
	#include <sys/resource.h>
	#include <iomanip>
	#include "json.hpp"


	using namespace std;

	const int MAXCOMMENTS = 20;
	const int MAXCOMMENTSLENGTH = 100*1024;
	const int MAXCOMMENTSTITLELENGTH = 1024;
	const int MAXOUTPUT = 256* 1024 ;//256Kb

	/**
	 * Class Tools Declaration
	 */
	class Tools {
	public:
		static bool existFile(string name);
		static string readFile(string name);
		static vector<string> splitLines(const string &data);
		static int nextLine(const string &data);
		static string caseFormat(string text);
		static string toLower(const string &text);
		static string normalizeTag(const string &text);
		static bool parseLine(const string &text, string &name, string &data);
		static string trimRight(const string &text);
		static string trim(const string &text);
		static void fdblock(int fd, bool set);
		static bool convert2(const string& str, double &data);
		static bool convert2(const string& str, long int &data);
		static const char* getenv(const char* name, const char* defaultvalue);
		static double getenv(const char* name, double defaultvalue);
	};

	/**
	 * Class Stop Declaration
	 */
	class Stop{
		static volatile bool TERMRequested;
	public:
		static void setTERMRequested();
		static bool isTERMRequested();
	};

	/**
	 * Class Timer Declaration
	 */
	class Timer{
		static time_t startTime;
		static chrono::time_point<chrono::high_resolution_clock> startTimeInMs;
	public:
		static void start();
		static void startInMs();
		static int elapsedTime();
		static int elapsedTimeInMs();

	};

	/**
	 * Class I18n Declaration
	 */
	class I18n{
	public:
		void init();
		const char *get_string(const char *s);	
	};

	/**
	 * Interface OutputChecker
	 */
	class OutputChecker{
	protected:
		string text;

	public:
		OutputChecker(const string &t):text(t){}
		virtual ~OutputChecker(){};
		virtual string type(){return "";}
		virtual operator string (){return "";}
		virtual string outputExpected(){return text;}
		virtual string studentOutputExpected(){return text;}
		virtual bool match(const string&)=0;
		virtual OutputChecker* clone()=0;
	};

	/**
	 * Class NumbersOutput Declaration
	 */
	class NumbersOutput:public OutputChecker{
		struct Number{
			bool isInteger;
			long int integer;
			double cientific;

			bool set(const string& str);
			bool operator==(const Number &o)const;
			bool operator!=(const Number &o)const;
			operator string () const;
		};

		vector<Number> numbers;
		bool startWithAsterisk;
		string cleanText;

		static bool isNum(char c);
		static bool isNumStart(char c);
		bool calcStartWithAsterisk();

	public:
		NumbersOutput(const string &text);//:OutputChecker(text);
		string studentOutputExpected();
		bool operator==(const NumbersOutput& o)const;
		bool match(const string& output);
		OutputChecker* clone();
		static bool typeMatch(const string& text);
		string type();
		operator string () const;
	};

	/**
	 * Class TextOutput Declaration
	 */
	class TextOutput:public OutputChecker{
		vector<string> tokens;
		bool isAlpha(char c);

	public:
		TextOutput(const string &text);//:OutputChecker(text);
		bool operator==(const TextOutput& o);
		bool match(const string& output);
		OutputChecker* clone();
		static bool typeMatch(const string& text);
		string type();
	};

	/**
	 * Class ExactTextOutput Declaration
	 */
	class ExactTextOutput:public OutputChecker{
		string cleanText;
		bool startWithAsterix;
		bool isAlpha(char c);

	public:
		ExactTextOutput(const string &text);//:OutputChecker(text);
		string studentOutputExpected();
		bool operator==(const ExactTextOutput& o);
		bool match(const string& output);
		OutputChecker* clone();
		static bool typeMatch(const string& text);
		string type();
	};

	/**
	 * Class RegularExpressionOutput Declaration
	 * Regular Expressions implemented by:
	 * Daniel José Ojeda Loisel
	 * Juan David Vega Rodríguez
	 * Miguel Ángel Viera González
	 */
	class RegularExpressionOutput:public OutputChecker {
		string errorCase;
		string cleanText;
		regex_t expression;
		bool flagI;
		bool flagM;
		int reti;

	public:
		RegularExpressionOutput (const string &text, const string &actualCaseDescription);

		bool match (const string& output);
			// Regular Expression compilation (with flags in mind) and comparison with the input and output evaluation

		string studentOutputExpected();
			// Returns the expression without flags nor '/'

		OutputChecker* clone();

		static bool typeMatch(const string& text);
			// Tests if it's a regular expression. A regular expressions should be between /../

		string type();
	};
	/**
	 * Class Case Declaration
	 * Case represents cases
	 */
	class Case {
		string input;
		vector< string > output;
		string caseDescription;
		float gradeReduction;
		string failMessage;
		string programToRun;
		string programArgs;
		int expectedExitCode; // Default value numeric_limits<int>::min()
		string variation;

		//Added by Tamar
		string inputSize;

	public:
		Case();
		void reset();
		void addInput(string );
		string getInput();
		void addOutput(string );
		const vector< string > & getOutput();
		void setFailMessage(const string &);
		string getFailMessage();
		void setCaseDescription(const string &);
		string getCaseDescription();
		void setGradeReduction(float);
		float getGradeReduction();
		void setExpectedExitCode(int);
		int getExpectedExitCode();
		void setProgramToRun(const string &);
		string getProgramToRun();
		void setProgramArgs(const string &);
		string getProgramArgs();
		void setVariation(const string &);
		string getVariation();

		//Added by Tamar
		void addInputSize(string );
		string getInputSize();

	};


	struct ProcessInfo {
		pid_t pid;
		int inPipe[2];
		int outPipe[2];
		const char* command;
		string output;
		bool executionError = false;
		char executionErrorReason[256] = {0};
		long maxResidentSetSize = 0;
		struct timeval userTime = {0, 0};
		struct timeval systemTime = {0, 0};
		chrono::milliseconds elapsedTime = chrono::milliseconds(0);
		bool finished = false;
		int exitCode = numeric_limits<int>::min();
		struct rusage ru;
		chrono::high_resolution_clock::time_point startTime;
		int status = 0;
	};

	/**
	 * Class TestCase Declaration
	 * TestCase represents cases to tested
	 */
	class TestCase {
		const char *command;
		const char *teacherCommand;
		const char **argv;
		static const char **envv;
		int id;
		bool correctOutput;
		bool outputTooLarge;
		bool programTimeout;
		bool programTimeoutInMs;
		bool executionError;
		bool correctExitCode;
		char executionErrorReason[1000];
		int sizeReaded;
		string input;
		vector< OutputChecker* > output;
		string caseDescription;
		float gradeReduction;
		float gradeReductionApplied;
		string failMessage;
		string programToRun;
		string programArgs;
		string variantion;
		int expectedExitCode; // Default value numeric_limits<int>::min()
		int exitCode; // Default value numeric_limits<int>::min()
		string programOutputBefore, programOutputAfter, programInput;

		//Added by Tamar
		string inputSize;
		chrono::duration<double> elapsedTime;
		double cpuTimeRatio;

		//int elapsedTime;

		void cutOutputTooLarge(string &output);
		void readWrite(int fdread, int fdwrite);
		void addOutput(const string &o, const string &actualCaseDescription);
	public:

		//Added by Tamar
		chrono::duration<double> getElapsedTime() const { return elapsedTime; }
		double getCpuTimeRatio() const { return cpuTimeRatio; }
		long maxResidentSetSize; // Memory usage in kilobytes
	    timeval userTime;    // User CPU time
	    timeval systemTime;  // System CPU time
		

		static void setEnvironment(const char **environment);
		void setDefaultCommand();
		TestCase(const TestCase &o);
		TestCase& operator=(const TestCase &o);
		~TestCase();
		TestCase(int id, const string &input, const vector<string> &output,
				const string &caseDescription, const float gradeReduction,
				string failMessage, string programToRun, string programArgs, int expectedExitCode, const string &inputSize);
		bool isCorrectResult();
		bool isExitCodeTested();
		float getGradeReduction();
		void setGradeReductionApplied(float r);
		float getGradeReductionApplied();
		string getCaseDescription();

		//Added by Tamar
		string getInputSize();

		string getCommentTitle(bool withGradeReduction/*=false*/); // Suui
		string getComment();
		void splitArgs(string);

		//void compareTest(time_t timeout, chrono::milliseconds timeoutInMs);
		void runTest(time_t timeout, chrono::milliseconds timeoutInMs); //Changed by Tamar
		void runTestWithCompare(time_t timeout, chrono::milliseconds timeoutInMs); //Added by Tamar
		string processArrayInput(const string &input);

		bool match(string data);


		bool setupPipes(ProcessInfo& process);
		bool startProcess(ProcessInfo& process, const char** argv, const char** envv);
		void closeUnusedPipeEnds(ProcessInfo& process);
		void writeInputToProcess(ProcessInfo& process, const string& input);
		void checkProcessTermination(ProcessInfo& process);
		void compareAndPrintResults(const ProcessInfo& studentProcess, const ProcessInfo& teacherProcess);
		
	};

	/**
	 * Class Evaluation Declaration
	 */
	class Evaluation {
		int maxtime;
		float grademin, grademax;
		string variation;
		bool noGrade;
		float grade;
		int nerrors, nruns;
		vector<TestCase> testCases;
		char comments[MAXCOMMENTS + 1][MAXCOMMENTSLENGTH + 1];
		char titles[MAXCOMMENTS + 1][MAXCOMMENTSTITLELENGTH + 1];
		char titlesGR[MAXCOMMENTS + 1][MAXCOMMENTSTITLELENGTH + 1];
		volatile int ncomments;
		volatile bool stopping;
		static Evaluation *singlenton;
		Evaluation();

		//Added by Tamar
		char executionErrorReason[1000];


	public:
		static Evaluation* getSinglenton();
		static void deleteSinglenton();
		void addTestCase(Case &);
		void removeLastNL(string &s);
		bool cutToEndTag(string &value, const string &endTag);
		void loadTestCases(string fname);
		bool loadParams();
		void addFatalError(const char *m);
		void runTests();
		void outputEvaluation();
	};

	/////////////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////// END OF DECLARATIONS ///////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////// BEGINNING OF DEFINITIONS ////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////



	volatile bool Stop::TERMRequested = false;
	time_t Timer::startTime;
	chrono::time_point<chrono::high_resolution_clock> Timer::startTimeInMs;
	const char **TestCase::envv=NULL;
	Evaluation* Evaluation::singlenton = NULL;

	/**
	 * Class Tools Definitions
	 */

	bool Tools::existFile(string name) {
		FILE *f = fopen(name.c_str(), "r");
		if (f != NULL) {
			fclose(f);
			return true;
		}
		return false;
	}

	string Tools::readFile(string name) {
		char buf[1000];
		string res;
		FILE *f = fopen(name.c_str(), "r");
		if (f != NULL)
			while (fgets(buf, 1000, f) != NULL)
				res += buf;
		return res;
	}

	vector<string> Tools::splitLines(const string &data) {
		vector<string> lines;
		int len, l = data.size();
		int startLine = 0;
		char pc = 0, c;
		for (int i = 0; i < l; i++) {
			c = data[i];
			if (c == '\n') {
				len = i - startLine;
				if (pc == '\r')
					len--;
				lines.push_back(data.substr(startLine, len));
				startLine = i + 1;
			}
			pc = c;
		}
		if (startLine < l) {
			len = l - startLine;
			if (pc == '\r')
				len--;
			lines.push_back(data.substr(startLine, len));
		}
		return lines;
	}

	int Tools::nextLine(const string &data) {
		int l = data.size();
		for (int i = 0; i < l; i++) {
			if (data[i] == '\n')
				return i + 1;
		}
		return l;
	}

	string Tools::caseFormat(string text) {
		vector<string> lines = Tools::splitLines(text);
		string res;
		int nlines = lines.size();
		for (int i = 0; i < nlines; i++)
			res += ">" + lines[i] + '\n';
		return res;
	}

	bool Tools::parseLine(const string &text, string &name, string &data) {
		size_t poseq;
		if ((poseq = text.find('=')) != string::npos) {
			name = normalizeTag(text.substr(0, poseq + 1));
			data = text.substr(poseq + 1);
			return true;
		}
		name = "";
		data = text;
		return false;
	}

	string Tools::toLower(const string &text) {
		string res = text;
		int len = res.size();
		for (int i = 0; i < len; i++)
			res[i] = tolower(res[i]);
		return res;
	}

	string Tools::normalizeTag(const string &text) {
		string res;
		int len = text.size();
		for (int i = 0; i < len; i++) {
			char c = text[i];
			if (isalpha(c) || c == '=')
				res += tolower(c);
		}
		return res;
	}

	string Tools::trimRight(const string &text) {
		int len = text.size();
		int end = -1;
		for (int i = len - 1; i >= 0; i--) {
			if (!isspace(text[i])) {
				end = i;
				break;
			}
		}
		return text.substr(0, end + 1);
	}

	string Tools::trim(const string &text) {
		int len = text.size();
		int begin = len;
		int end = -1;
		for (int i = 0; i < len; i++) {
			char c = text[i];
			if (!isspace(c)) {
				begin = i;
				break;
			}
		}
		for (int i = len - 1; i >= 0; i--) {
			char c = text[i];
			if (!isspace(c)) {
				end = i;
				break;
			}
		}
		if (begin <= end)
			return text.substr(begin, (end - begin) + 1);
		return "";
	}

	void Tools::fdblock(int fd, bool set) {
		int flags;
		if ((flags = fcntl(fd, F_GETFL, 0)) < 0) {
			return;
		}
		if (set && (flags | O_NONBLOCK) == flags)
			flags ^= O_NONBLOCK;
		else
			flags |= O_NONBLOCK;
		fcntl(fd, F_SETFL, flags);
	}

	bool Tools::convert2(const string& str, double &data){
		if ( str == "." ){
			return false;
		}
		stringstream conv(str);
		conv >> data;
		return conv.eof();
	}

	bool Tools::convert2(const string& str, long int &data){
		stringstream conv(str);
		conv >> data;
		return conv.eof();
	}
	const char* Tools::getenv(const char* name, const char* defaultvalue) {
		const char* value = ::getenv(name);
		if ( value == NULL ) {
			value = defaultvalue;
			printf("Warning: using default value '%s' for '%s'\n", defaultvalue, name);
		}
		return value; // Fixes bug found by Peter Svec
	}

	double Tools::getenv(const char* name, double defaultvalue) {
		const char* svalue = ::getenv(name);
		double value = defaultvalue;
		if ( svalue != NULL ) {
			Tools::convert2(svalue, value);
		} else {
			printf("Warning: using default value '%lf' for '%s'\n", defaultvalue, name);
		}
		return value;
	}


	/**
	 * Class Stop Definitions
	 */

	void Stop::setTERMRequested() {
		TERMRequested = true;
	}

	bool Stop::isTERMRequested() {
		return TERMRequested;
	}

	/**
	 * Class Timer Definitions
	 */

	void Timer::start() {
		startTime = time(NULL);
	}

	void Timer::startInMs() {
		startTimeInMs = chrono::high_resolution_clock::now();
	}


	int Timer::elapsedTime() {
		return time(NULL) - startTime;
	}

	int Timer::elapsedTimeInMs() {
		auto endTimeInMs = chrono::high_resolution_clock::now();
			return chrono::duration_cast<chrono::milliseconds>(endTimeInMs - startTimeInMs).count();
	}


	/**
	 * Class Stop Definitions
	 */

	void I18n::init(){

	}

	const char *I18n::get_string(const char *s){
		return s;
	}

	/**
	 * Class NumbersOutput Definitions
	 */

	// Struct Number
	bool NumbersOutput::Number::set(const string& str){
		isInteger=Tools::convert2(str, integer);
		if(!isInteger){
			return Tools::convert2(str, cientific);
		}
		return true;
	}

	bool NumbersOutput::Number::operator==(const Number &o)const{
		if(isInteger)
			return o.isInteger && integer == o.integer;
		if(o.isInteger)
			return cientific != 0?fabs((cientific - o.integer) / cientific) < 0.0001 : o.integer == 0;
		else
			return cientific != 0?fabs((cientific - o.cientific) / cientific) < 0.0001 : fabs(o.cientific) < 0.0001;
	}

	bool NumbersOutput::Number::operator!=(const Number &o)const{
		return !((*this)==o);
	}

	NumbersOutput::Number::operator string() const{
		char buf[100];
		if(isInteger) {
			sprintf(buf, "%ld", integer);
		} else {
			sprintf(buf, "%10.5lf", cientific);
		}
		return buf;
	}


	bool NumbersOutput::isNum(char c){
		if(isdigit(c)) return true;
		return c=='+' || c=='-' || c=='.' || c=='e' || c=='E';
	}

	bool NumbersOutput::isNumStart(char c){
		if(isdigit(c)) return true;
		return c=='+' || c=='-' || c=='.';
	}

	bool NumbersOutput::calcStartWithAsterisk(){
		int l=text.size();
		for(int i=0; i<l; i++){
			char c=text[i];
			if(isspace(c)) continue;
			if(c=='*'){
				cleanText = text.substr(i+1,text.size()-(i+1));
				return true;
			}else{
				cleanText = text.substr(i,text.size()-i);
				return false;
			}
		}
		return false;
	}

	NumbersOutput::NumbersOutput(const string &text):OutputChecker(text){
		int l=text.size();
		string str;
		Number number;
		for(int i=0; i<l; i++){
			char c=text[i];
			if((isNum(c) && str.size()>0) || (isNumStart(c) && str.size()==0)){
				str+=c;
			}else if(str.size()>0){
				if(isNumStart(str[0]) && number.set(str)) numbers.push_back(number);
				str="";
			}
		}
		if(str.size()>0){
			if(isNumStart(str[0]) && number.set(str)) numbers.push_back(number);
		}
		startWithAsterisk=calcStartWithAsterisk();
	}

	string NumbersOutput::studentOutputExpected(){
		return cleanText;
	}

	bool NumbersOutput::operator==(const NumbersOutput& o)const{
		size_t len = numbers.size();
		int offset = 0;
		if(startWithAsterisk) {
			if( o.numbers.size() < len ) return false;
			offset = o.numbers.size() - len;
		} else {
			if (o.numbers.size() != len) return false;
		}
		for (size_t i = 0; i < len; i++)
			if(numbers[i] != o.numbers[offset+i])
				return false;
		return true;
	}

	bool NumbersOutput::match(const string& output){
		NumbersOutput temp(output);
		return operator==(temp);
	}

	OutputChecker* NumbersOutput::clone(){
		return new NumbersOutput(outputExpected());
	}

	bool NumbersOutput::typeMatch(const string& text){
		int l=text.size();
		string str;
		Number number;
		for(int i=0; i<l; i++){
			char c=text[i];
			// Skip spaces/CR/LF... and *
			if(!isspace(c) && c!='*') {
				str += c;
			}else if(str.size()>0) {
				if (!isNumStart(str[0])||
					!number.set(str)) return false;
				str="";
			}
		}
		if(str.size()>0){
			if(!isNumStart(str[0])||!number.set(str)) return false;
		}
		return true;
	}

	string NumbersOutput::type(){
		return "numbers";
	}

	NumbersOutput::operator string () const{
		string ret="[";
		int l=numbers.size();
		for(int i=0; i<l; i++){
			ret += i > 0 ? ", " : "";
			ret += numbers[i];
		}
		ret += "]";
		return ret;
	}

	/**
	 * Class TextOutput Definitions
	 */

	bool TextOutput::isAlpha(char c){
		if ( isalnum(c) ) return true;
		return c < 0;
	}

	TextOutput::TextOutput(const string &text):OutputChecker(text){
		size_t l = text.size();
		string token;
		for(size_t i = 0; i < l; i++){
			char c = text[i];
			if( isAlpha(c) ){
				token += c;
			}else if(token.size() > 0){
				tokens.push_back(Tools::toLower(token));
				token="";
			}
		}
		if(token.size()>0){
			tokens.push_back(Tools::toLower(token));
		}
	}

	bool TextOutput::operator==(const TextOutput& o) {
		size_t l = tokens.size();
		if (o.tokens.size() < l) return false;
		int offset = o.tokens.size() - l;
		for (size_t i = 0; i < l; i++)
			if (tokens[i] != o.tokens[ offset + i ])
				return false;
		return true;
	}

	bool TextOutput::match(const string& output) {
		TextOutput temp(output);
		return operator== (temp);
	}

	OutputChecker* TextOutput::clone() {
		return new TextOutput(outputExpected());
	}

	bool TextOutput::typeMatch(const string& text) {
		return true;
	}

	string TextOutput::type(){
		return "text";
	}

	/**
	 * Class ExactTextOutput Definitions
	 */

	bool ExactTextOutput::isAlpha(char c){
		if(isalnum(c)) return true;
		return c < 0;
	}

	ExactTextOutput::ExactTextOutput(const string &text):OutputChecker(text){
		string clean = Tools::trim(text);
		if(clean.size() > 2 && clean[0] == '*') {
			startWithAsterix = true;
			cleanText = clean.substr(2, clean.size() - 3);
		}else{
			startWithAsterix =false;
			cleanText=clean.substr(1,clean.size()-2);
		}
	}

	string ExactTextOutput::studentOutputExpected(){
		return cleanText;
	}

	bool ExactTextOutput::operator==(const ExactTextOutput& o){
		return match(o.text);
	}

	bool ExactTextOutput::match(const string& output){
		if (cleanText == output) return true;
		string cleanOutput = output;
		// Removes last output char if is a newline and the last searched char is not a newline.
		if (cleanText.size() > 0 && cleanText[cleanText.size()-1] != '\n' ) {
			if (cleanOutput.size() > 0 && cleanOutput[cleanOutput.size()-1] == '\n' ) {
				cleanOutput = cleanOutput.substr(0, cleanOutput.size()-1);
			}
		}
		if (startWithAsterix && cleanText.size() < cleanOutput.size()) {
			size_t start = cleanOutput.size() - cleanText.size();
			return cleanText == cleanOutput.substr(start, cleanText.size());
		} else {
			return cleanText == cleanOutput;
		}
	}

	OutputChecker* ExactTextOutput::clone(){
		return new ExactTextOutput(outputExpected());
	}

	bool ExactTextOutput::typeMatch(const string& text){
		string clean=Tools::trim(text);
		return (clean.size()>1 && clean[0]=='"' && clean[clean.size()-1]=='"')
				||(clean.size()>3 && clean[0]=='*' && clean[1]=='"' && clean[clean.size()-1]=='"');
	}

	string ExactTextOutput::type(){
		return "exact text";
	}

	/**
	 * Class RegularExpressionOutput Definitions
	 */

	RegularExpressionOutput::RegularExpressionOutput(const string &text, const string &actualCaseDescription):OutputChecker(text) {

		errorCase = actualCaseDescription;
		size_t pos = 1;
		flagI = false;
		flagM = false;
		string clean = Tools::trim(text);
		pos = clean.size() - 1;
		while (clean[pos] != '/' && pos > 0) {
			pos--;
		}
		cleanText = clean.substr(1,pos-1);
		if (pos + 1 != clean.size()) {
			pos = pos + 1;
			// Flags processing
			while (pos < clean.size()) {

				switch (clean[pos]) {
					case 'i':
						flagI=true;
						break;
					case 'm':
						flagM=true;
						break;
					case ' ':
						break;
					default:
						Evaluation* p_ErrorTest = Evaluation::getSinglenton();
						char wrongFlag = clean[pos];
						string flagCatch;
						stringstream ss;
						ss << wrongFlag;
						ss >> flagCatch;
						string errorType = string("Error: invalid flag in regex output ")+ string(errorCase)+ string (", found a ") + string(flagCatch) + string (" used as a flag, only i and m available.");
						const char* flagError = errorType.c_str();
						p_ErrorTest->addFatalError(flagError);
						p_ErrorTest->outputEvaluation();
						abort();
				}
				pos++;
			}
		}
	}

	// Regular Expression compilation (with flags in mind) and comparison with the input and output evaluation
	bool RegularExpressionOutput::match (const string& output) {

		reti=-1;
		const char * in = cleanText.c_str();
		// Use POSIX-C regrex.h
		// Flag compilation
		if (flagI || flagM) {
			if (flagM && flagI) {
				reti = regcomp(&expression, in, REG_EXTENDED | REG_NEWLINE | REG_ICASE);
			} else if (flagM) {
				reti = regcomp(&expression, in, REG_EXTENDED | REG_NEWLINE);
			} else {
				reti = regcomp(&expression, in, REG_EXTENDED | REG_ICASE);
			}

		// No flag compilation
		} else {
			reti = regcomp(&expression, in, REG_EXTENDED);
		}

		if (reti == 0) { // Compilation was successful

			const char * out = output.c_str();
			reti = regexec(&expression, out, 0, NULL, 0);

			if (reti == 0) { // Match
				return true;
			} else if (reti == REG_NOMATCH){ // No match
				return false;

			} else { // Memory Error
				Evaluation* p_ErrorTest = Evaluation::getSinglenton();
				string errorType = string("Error: out of memory error, during matching case ") + string(errorCase);
				const char* flagError = errorType.c_str();
				p_ErrorTest->addFatalError(flagError);
				p_ErrorTest->outputEvaluation();
				abort();
			}

		} else { // Compilation error
			size_t length = regerror(reti, &expression, NULL, 0);
			char* bff = new char[length + 1];
			(void) regerror(reti, &expression, bff, length);
			Evaluation* p_ErrorTest = Evaluation::getSinglenton();
			string errorType = string("Error: regular expression compilation error")+string (" in case: ")+ string(errorCase) +string (".\n")+ string(bff);
			const char* flagError = errorType.c_str();
			p_ErrorTest->addFatalError(flagError);
			p_ErrorTest->outputEvaluation();
			abort();
			return false;
		}
	}

	// Returns the expression without flags nor '/'
	string RegularExpressionOutput::studentOutputExpected() {return cleanText;}

	OutputChecker* RegularExpressionOutput::clone() {
		return new RegularExpressionOutput(outputExpected(), errorCase);
	}

	// Tests if it's a regular expression. A regular expressions should be between /../
	bool RegularExpressionOutput::typeMatch(const string& text) {
		string clean=Tools::trim(text);
		if (clean.size() > 2 && clean[0] == '/') {
			for (size_t i = 1; i < clean.size(); i++) {
				if (clean[i] == '/') {
					return true;
				}
			}
		}
		return false;
	}

	string RegularExpressionOutput::type() {
		return "regular expression";
	}
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
		gradeReduction = numeric_limits<float>::min();
		failMessage = "";
		programToRun = "";
		programArgs = "";
		variation = "";
		expectedExitCode = numeric_limits<int>::min();

		//Added by Tamar
		inputSize = "";

	}

	void Case::addInput(string s) {
		input += s;
	}

	string Case::getInput() {
		return input;
	}

	void Case::addOutput(string o) {
		output.push_back(o);
	}

	const vector< string > & Case::getOutput() {
		return output;
	}

	void Case::setFailMessage(const string &s) {
		failMessage = s;
	}

	string Case::getFailMessage() {
		return failMessage;
	}
	void Case::setCaseDescription(const string &s) {
		caseDescription = s;
	}

	string Case::getCaseDescription() {
		return caseDescription;
	}
	void Case::setGradeReduction(float g) {
		gradeReduction = g;
	}

	float Case::getGradeReduction() {
		return gradeReduction;
	}

	void Case::setExpectedExitCode(int e) {
		expectedExitCode = e;
	}

	int Case::getExpectedExitCode() {
		return expectedExitCode;
	}
	void Case::setProgramToRun(const string &s) {
		programToRun = s;
	}

	string Case::getProgramToRun() {
		return programToRun;
	}

	void Case::setProgramArgs(const string &s) {
		programArgs = s;
	}

	string Case::getProgramArgs() {
		return programArgs;
	}

	void Case::setVariation(const string &s) {
		variation = Tools::toLower(Tools::trim(s));
	}

	string Case::getVariation() {
		return variation;
	}

	//Added by Tamar
	void Case::addInputSize(string s) {
		inputSize += s;
	}
	string Case::getInputSize() {
		return inputSize;
	}

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
		const int MAX = 1024* 1000 ;
		// Buffer size to read
		const int POLLREAD = POLLIN | POLLPRI;
		// Poll to read from program
		struct pollfd devices[2];
		devices[0].fd = fdread;
		devices[1].fd = fdwrite;
		char buf[MAX];
		devices[0].events = POLLREAD;
		devices[1].events = POLLOUT;
		int res = poll(devices, programInput.size()>0?2:1, 0);
		if (res == -1) // Error
			return;
		if (res == 0) // Nothing to do
			return;
		if (devices[0].revents & POLLREAD) { // Read program output
			int readed = read(fdread, buf, MAX);
			if (readed > 0) {
				sizeReaded += readed;
				if (programInput.size() > 1) {
					programOutputBefore += string(buf, readed);
					cutOutputTooLarge(programOutputBefore);
				} else {
					programOutputAfter += string(buf, readed);
					cutOutputTooLarge(programOutputAfter);
				}
			}
		}
		if (programInput.size() > 0 && devices[1].revents & POLLOUT) { // Write to program
			int written = write(fdwrite, programInput.c_str(), Tools::nextLine(
					programInput));
			if (written > 0) {
				programInput.erase(0, written);
			}
			if(programInput.size()==0){
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
		argv = new const char*[2];
		argv[0] = command;
		argv[1] = NULL;
	}

	TestCase::TestCase(const TestCase &o) {
		id=o.id;
		correctOutput=o.correctOutput;
		correctExitCode = o.correctExitCode;
		outputTooLarge=o.outputTooLarge;
		programTimeout=o.programTimeout;
		programTimeoutInMs=o.programTimeoutInMs;
		executionError=o.executionError;
		strcpy(executionErrorReason,o.executionErrorReason);
		sizeReaded=o.sizeReaded;
		input=o.input;
		caseDescription=o.caseDescription;
		gradeReduction=o.gradeReduction;
		expectedExitCode = o.expectedExitCode;
		exitCode = o.exitCode;
		failMessage=o.failMessage;
		programToRun=o.programToRun;
		programArgs=o.programArgs;
		gradeReductionApplied=o.gradeReductionApplied;
		programOutputBefore=o.programOutputBefore;
		programOutputAfter=o.programOutputAfter;
		programInput=o.programInput;

		//Added by Tamar
		inputSize=o.inputSize;
	    maxResidentSetSize = 0;
        userTime = {0, 0};
        systemTime = {0, 0};
		cpuTimeRatio =0;

		for(size_t i = 0; i < o.output.size(); i++){
			output.push_back(o.output[i]->clone());
		}
		setDefaultCommand();
	}

	TestCase& TestCase::operator=(const TestCase &o) {
		id=o.id;
		correctOutput=o.correctOutput;
		correctExitCode = o.correctExitCode;
		outputTooLarge=o.outputTooLarge;
		programTimeout=o.programTimeout;
		programTimeoutInMs=o.programTimeoutInMs;
		executionError=o.executionError;
		strcpy(executionErrorReason,o.executionErrorReason);
		sizeReaded=o.sizeReaded;
		input=o.input;
		caseDescription=o.caseDescription;
		gradeReduction=o.gradeReduction;
		failMessage=o.failMessage;
		programToRun=o.programToRun;
		programArgs=o.programArgs;
		expectedExitCode = o.expectedExitCode;
		exitCode = o.exitCode;
		gradeReductionApplied=o.gradeReductionApplied;
		programOutputBefore=o.programOutputBefore;
		programOutputAfter=o.programOutputAfter;
		programInput=o.programInput;

		//Added by Tamar
		inputSize=o.inputSize;

		for(size_t i=0; i<output.size(); i++)
			delete output[i];
		output.clear();
		for(size_t i=0; i<o.output.size(); i++){
			output.push_back(o.output[i]->clone());
		}
		return *this;
	}

	TestCase::~TestCase() {
		for(size_t i = 0; i < output.size(); i++)
			delete output[i];
	}

	TestCase::TestCase(int id, const string &input, const vector<string> &output,
			const string &caseDescription, const float gradeReduction,
			string failMessage, string programToRun, string programArgs, int expectedExitCode, const string &inputSize) {
		this->id = id;
		this->input = input;
		for(size_t i = 0; i < output.size(); i++){
			addOutput(output[i], caseDescription);
		}
		this->caseDescription = caseDescription;
		this->gradeReduction = gradeReduction;
		this->expectedExitCode = expectedExitCode;
		this->programToRun = programToRun;
		this->programArgs = programArgs;
		this->failMessage = failMessage;

		//Added by Tamar
		this->inputSize = inputSize;

		exitCode = numeric_limits<int>::min();
		outputTooLarge = false;
		programTimeout = false;
		programTimeoutInMs = false;
		executionError = false;
		correctOutput = false;
		correctExitCode = false;
		sizeReaded = 0;
		gradeReductionApplied =0;
		strcpy(executionErrorReason, "");
		setDefaultCommand();
	}

	bool TestCase::isCorrectResult() {
		bool correct = correctOutput &&
					! programTimeout &&
					! programTimeoutInMs &&
					! outputTooLarge &&
					! executionError;
		return correct || (isExitCodeTested() && correctExitCode);
	}

	bool TestCase::isExitCodeTested() {
		return expectedExitCode != numeric_limits<int>::min();
	}

	float TestCase::getGradeReduction() {
		return gradeReduction;
	}

	void TestCase::setGradeReductionApplied(float r) {
		gradeReductionApplied=r;
	}

	float TestCase::getGradeReductionApplied() {
		return gradeReductionApplied;
	}

	string TestCase::getCaseDescription(){
		return caseDescription;
	}

	//Added by Tamar
	string TestCase::getInputSize(){
		return inputSize;
	}

	string TestCase::getCommentTitle(bool withGradeReduction=false) {
		char buf[100];
		string ret;
		sprintf(buf, "Test %d", id);
		ret = buf;
		if (caseDescription.size() > 0) {
			ret += ": " + caseDescription;
		}
		if(withGradeReduction && getGradeReductionApplied()>0){
			sprintf(buf," (%.3f)", -getGradeReductionApplied());
			ret += buf;
		}
		ret += '\n';
		return ret;
	}
	

	string TestCase::getComment() {
		if (isCorrectResult()) {
			return "";
		}
		char buf[100];
		string ret;
		if(output.size()==0){
			ret += "Configuration error in the test case: the output is not defined";
		}
		if (programTimeout) {
			ret += "Program timeout\n";
		}
		if (programTimeoutInMs) {
			ret += "Program timeout in ms\n";
		}
		if (outputTooLarge) {
			sprintf(buf, "Program output too large (%dKb)\n", sizeReaded / 1024);
			ret += buf;
		}

		//Added by Tamar
		if(elapsedTime.count()>0){
			sprintf(buf, "Program run time in ms: %f \n", elapsedTime.count());
			ret += buf;
		}

		if (executionError) {
			ret += executionErrorReason + string("\n");
		}
		if (isExitCodeTested() && ! correctExitCode) {
			char buf[250];
			sprintf(buf, "Incorrect exit code. Expected %d, found %d\n", expectedExitCode, exitCode);
			ret += buf;
		}
		if (! correctOutput ) {
			if (failMessage.size()) {
				ret += failMessage + "\n";
			} else {
				ret += "Incorrect program output\n";
				ret += " --- Input ---\n";

				//Added by Tamar - Change input to file content instead of file 	name
				string actualInput = input;
				string filename, limitStr;
				int limit = 0;
				// Split the input string into filename and limit
				istringstream iss(actualInput);
				iss >> filename >> limitStr;

				// Check if the limit part is a number and convert it
				try {
					limit = stoi(limitStr);
				} catch (const invalid_argument&) {
					limit = 0; // Default to 0 if the limit is not a valid number
				}
				if(Tools::existFile(Tools::trim(actualInput))){
					string fileContent = Tools::readFile(Tools::trim(actualInput));

					if (!fileContent.empty()) {
						actualInput = fileContent;
					}
				}
				if (limit > 0 && Tools::existFile(Tools::trim(filename))) {
					// Read the content of the file
					ret+=filename;
					ret+="\n";

					string fileContent = Tools::readFile(Tools::trim(filename));
					if (!fileContent.empty()) {
						actualInput = fileContent;

						if (limit < actualInput.length()){
							actualInput = actualInput.substr(0, limit);
						}
					}
				}
				if(actualInput.length() > 1500){
					actualInput = actualInput.substr(0,1000) + " ... " + actualInput.substr(actualInput.length() - 500);
				}
				ret += Tools::caseFormat(actualInput);

				//Added by Tamar
				if (inputSize != ""){
					ret += " --- Input size---\n";
					ret += Tools::caseFormat(inputSize);
				}

				ret += "\n --- Program output ---\n";
				ret += Tools::caseFormat(programOutputBefore + programOutputAfter);
				if(output.size()>0){
					ret += "\n --- Expected output ("+output[0]->type()+")---\n";
					ret += Tools::caseFormat(output[0]->studentOutputExpected());
				}
			}
		}
		return ret;
	}

	void TestCase::splitArgs(string programArgs) {
		int l = programArgs.size();
		int nargs = 1;
		char *buf = new char[programArgs.size() + 1];
		strcpy(buf, programArgs.c_str());
		argv = (const char **) new char*[programArgs.size() + 1];
		argv[0] = command;
		bool inArg = false;
		char separator = ' ';
		for(int i=0; i < l; i++) { // TODO improve
			if ( ! inArg ) {
				if ( buf[i] == ' ' ) {
					buf[i] = '\0';
					continue;
				} else if ( buf[i] == '\'' ) {
					argv[nargs++] = buf + i + 1;
					separator = '\'';
				} else if ( buf[i] == '"' ) {
					argv[nargs++] = buf + i + 1;
					separator = '"';
				} else if ( buf[i] != '\0') {
					argv[nargs++] = buf + i;
					separator = ' ';
				}
				inArg = true;
			} else {
				if ( buf[i] == separator  ) {
					buf[i] = '\0';
					separator = ' ';
					inArg = false;
				}
			}
		}
		argv[nargs] = NULL;
	}

	//Added by Tamar
	string inputToFile(const string& largeString) {
		string filename = "output_" + to_string(time(nullptr)) + ".txt";
		ofstream file;
		try {
			file.exceptions(ofstream::failbit | ofstream::badbit);
			file.open(filename);
			file << largeString;
			file.close();
		} catch (const ofstream::failure& e) {
			cerr << "Exception writing to file: " << e.what() << '\n';
			return "";  // Return an empty string on failure
		}
		return filename;
	}



	string TestCase::processArrayInput(const string& input) {
		string result = input;

		if (result.find("{[") != string::npos && result.find("]}") != string::npos) {
			// Example placeholder format: {[1:1000:]} or ri{[1:1000:]}
			regex pattern(R"((r?)(\d+)?(i|f|c)?\{\[(\d+):(\d+):((\d+(\.\d+)?)?)\]\})");
			smatch match;

			if (regex_search(result, match, pattern)) {
				string type = match[3].str();        // Get the type: "int", "float", or "char"
				bool isRandom = !match[1].str().empty();  // Check if 'r' is present for randomness
				int count = 0;
				if(!match[2].str().empty()){
					count = stoi(match[2].str());
				}
				int startNum = stoi(match[4].str());
				int endNum = stoi(match[5].str());
				double jump = match[6].str().empty() ? 1.0 : stod(match[6].str());  // Default jump is 1 if not specified

				// Random number generator initialization
				random_device rd;
				mt19937 gen(rd());
				ostringstream oss;

				if (count < 0) {
					cerr << "Error: Count must be a positive integer for random sequences.\n";
					return "";
				}
				
				if (type == "f") {
					// For floats, use a random real number distribution
					uniform_real_distribution<> disFloat(startNum, endNum);
					if(count > 0 && isRandom){
						endNum = count;
						startNum = 0;
					}
					for (double i = startNum; i <= endNum; i += jump) {
						if (isRandom) {
							oss << disFloat(gen) << " ";  // Random float
						} else {
							oss << i << " ";  // Sequential float with jump
						}
					}
				} else if (type == "c") {
					// For char, treat the numbers as ASCII codes and cast to char
					uniform_int_distribution<> disChar(startNum, endNum);
					if(count > 0 && isRandom){
						endNum = count;
						startNum = 0;
					}
					for (int i = startNum; i <= endNum; i += static_cast<int>(jump)) {
						if (isRandom) {
							oss << static_cast<char>(disChar(gen)) << " ";  // Random ASCII character
						} else {
							oss << static_cast<char>(i) << " ";  // Sequential char based on ASCII values with jump
						}
					}
				} else {  // Default to int if no specific type or "int" is specified
					uniform_int_distribution<> disInt(startNum, endNum);
					if(count > 0 && isRandom){
						endNum = count - 1;
						startNum = 0;
					}
					for (int i = startNum; i <= endNum; i += static_cast<int>(jump)) {
						if (isRandom) {
							oss << disInt(gen) << " ";  // Random int
						} else {
							oss << i << " ";  // Sequential int with jump
						}
					}
				}

				// Replace the placeholder with the generated numbers/characters
				result = regex_replace(result, pattern, oss.str());
				const size_t MAX_SIZE = 100 * 1024 * 1024; // 10 MB limit
				if (result.size() > MAX_SIZE) {
					cerr << "Error: Input size exceeds the maximum allowed limit.\n";
					return "";  // Handle the error appropriately
				}
				//Added by Tamar
				result = inputToFile(result);

				if (programToRun.empty()){
					command = "./inputFile.sh";
				}
			}
		}
		return result;
	}

	#include <fcntl.h>

	void readFromPipe(int fd, string& output) {
		char buffer[1024];
		ssize_t bytesRead;
		while (true) {
			bytesRead = read(fd, buffer, sizeof(buffer));
			if (bytesRead > 0) {
				output.append(buffer, bytesRead);
			} else if (bytesRead == -1 && (errno == EAGAIN || errno == EWOULDBLOCK)) {
				// No more data to read now
				break;
			} else if (bytesRead == 0) {
				// End of file
				break;
			} else {
				// An error occurred
				perror("read");
				break;
			}
		}
	}




	// Make setupPipes a member function of TestCase
	bool TestCase::setupPipes(ProcessInfo& process) {
		if (pipe(process.inPipe) == -1) {
			executionError = true;
			sprintf(executionErrorReason, "Internal error: pipe error (%s)", strerror(errno));
			return false;
		}

		if (pipe(process.outPipe) == -1) {
			// Close the first pipe before returning to avoid a resource leak
			close(process.inPipe[0]);
			close(process.inPipe[1]);
			
			executionError = true;
			sprintf(executionErrorReason, "Internal error: pipe error (%s)", strerror(errno));
			return false;
		}

		return true;
	}

	// Make startProcess a member function of TestCase
	bool TestCase::startProcess(ProcessInfo& process, const char** argv, const char** envv) {
		if ((process.pid = fork()) == 0) {
			// Child process
			// Redirect stdin and stdout/stderr
			close(process.inPipe[1]);  // Close write end of input pipe
			dup2(process.inPipe[0], STDIN_FILENO);
			close(process.inPipe[0]);

			close(process.outPipe[0]); // Close read end of output pipe
			dup2(process.outPipe[1], STDOUT_FILENO);
			dup2(process.outPipe[1], STDERR_FILENO);
			close(process.outPipe[1]);

			setpgrp();

			// Execute program
			execve(process.command, (char *const *)argv, (char *const *)envv);
			perror("Internal error, execve fails");
			abort(); // End of child process
		}

		if (process.pid == -1) {
			executionError = true;
			sprintf(executionErrorReason, "Internal error: fork error (%s)", strerror(errno));
			return false;
		}

		return true;
	}

	// Modify closeUnusedPipeEnds to be a member function (optional)
	void TestCase::closeUnusedPipeEnds(ProcessInfo& process) {
		close(process.inPipe[0]);    // Close read end of input pipe
		close(process.outPipe[1]);   // Close write end of output pipe
	}

	// Similarly, make writeInputToProcess a member function (optional)
	void TestCase::writeInputToProcess(ProcessInfo& process, const string& input) {
		if (!input.empty()) {
			write(process.inPipe[1], input.c_str(), input.size());
		}
		close(process.inPipe[1]); // Close write end after writing input
	}

	// Modify checkProcessTermination to be a member function (optional)
	void TestCase::checkProcessTermination(ProcessInfo& process) {
		pid_t pidr = wait4(process.pid, &process.status, WNOHANG | WUNTRACED, &process.ru);
		if (pidr == process.pid) {
			process.finished = true;
			auto endTime = chrono::high_resolution_clock::now();
			process.elapsedTime = chrono::duration_cast<chrono::milliseconds>(endTime - process.startTime);

			if (WIFSIGNALED(process.status)) {
				int signal = WTERMSIG(process.status);
				process.executionError = true;
				sprintf(process.executionErrorReason, "Program terminated due to \"%s\" (%d)\n", strsignal(signal), signal);
				cerr << process.executionErrorReason;  // Print the error
			}
			if (WIFEXITED(process.status)) {
				process.exitCode = WEXITSTATUS(process.status);
			} else {
				process.executionError = true;
				strcpy(process.executionErrorReason, "Program terminated but unknown reason.");
			}
			process.maxResidentSetSize = process.ru.ru_maxrss; // Memory usage in KB
			process.userTime = process.ru.ru_utime;
			process.systemTime = process.ru.ru_stime;
		} else if (pidr == -1) {
			process.executionError = true;
			strcpy(process.executionErrorReason, "waitpid error");
			process.finished = true;
		}
	}


	void TestCase::compareAndPrintResults(const ProcessInfo& studentProcess, const ProcessInfo& teacherProcess) {

		// Print the comparison results
		cout << "Student vs. Teacher Comparison:\n";
		cout << left << setw(15) << "Variation"
				<< setw(20) << "Run Time (ms)"
				<< setw(20) << "CPU Time (ms)"
				<< setw(20) << "Memory (KB)"
				<< setw(20) << "Output"
				<< "\n";

		double teacher_cpuTimeMs = (teacherProcess.userTime.tv_sec * 1000.0) + (teacherProcess.userTime.tv_usec / 1000.0);
		double student_cpuTimeMs = (studentProcess.userTime.tv_sec * 1000.0) + (studentProcess.userTime.tv_usec / 1000.0);
		programOutputAfter = studentProcess.output;

		cout << left << setw(15) << "Teacher"
				<< setw(25) << fixed << setprecision(2) << teacherProcess.elapsedTime.count()
				<< setw(25) << teacher_cpuTimeMs
				<< setw(25) << teacherProcess.maxResidentSetSize
				<< setw(25) << teacherProcess.output.substr(0, 1000)
				<< "\n";

		cout << left << setw(15) << "Student"
				<< setw(25) << fixed << setprecision(2) << studentProcess.elapsedTime.count()
				<< setw(25) << student_cpuTimeMs
				<< setw(25) << studentProcess.maxResidentSetSize
				<< setw(25) << (programOutputBefore + programOutputAfter).substr(0, 100)
				<< "\n";

		// Calculate and display ratios
		cpuTimeRatio = student_cpuTimeMs != 0 ? (teacher_cpuTimeMs / student_cpuTimeMs) : 0.0;
		double memoryRatio = studentProcess.maxResidentSetSize != 0 ? ((double)teacherProcess.maxResidentSetSize / studentProcess.maxResidentSetSize) : 0.0;

		// Set formatting for decimal places
		cout << fixed << setprecision(2);

		cout << "Runtime Ratio: " << cpuTimeRatio << "\n";
		cout << "Memory Usage Ratio: " << memoryRatio << "\n\n";
	}



	void TestCase::runTest(time_t timeout, chrono::milliseconds timeoutInMs) { //Changed by Tamar
		time_t start = time(NULL);
		//Added by Tamar
		input = processArrayInput(input);
		//auto startInMs = chrono::high_resolution_clock::now();
		elapsedTime = chrono::milliseconds(0);
		int pp1[2]; // Send data
		int pp2[2]; // Receive data
		if (pipe(pp1) == -1 || pipe(pp2) == -1) {
			executionError = true;
			sprintf(executionErrorReason, "Internal error: pipe error (%s)", strerror(errno));
			return;
		}
		if (programToRun > "" && programToRun.size() < 512) {
			command = programToRun.c_str();
		}
		if (!Tools::existFile(command)) {
			executionError = true;
			sprintf(executionErrorReason, "Execution file not found '%s'", command);
			return;
		}
		pid_t pid;
		if (programArgs.size() > 0) {
			splitArgs(programArgs);
		}
		if ((pid = fork()) == 0) {
			// Execute
			close(pp1[1]);
			dup2(pp1[0], STDIN_FILENO);
			close(pp2[0]);
			dup2(pp2[1], STDOUT_FILENO);
			dup2(STDOUT_FILENO, STDERR_FILENO);
			setpgrp();
			execve(command, (char *const *)argv, (char *const *)envv);
			perror("Internal error, execve fails");
			abort(); //end of child
		}
		if (pid == -1) {
			executionError = true;
			sprintf(executionErrorReason, "Internal error: fork error (%s)", strerror(errno));
			return;
		}
		close(pp1[0]);
		close(pp2[1]);
		int fdwrite = pp1[1];
		int fdread = pp2[0];
		Tools::fdblock(fdwrite, false);
		Tools::fdblock(fdread, false);
		programInput = input;
		if (programInput.size() == 0) { // No input
			close(fdwrite);
		}
		programOutputBefore = "";
		programOutputAfter = "";
		pid_t pidr;
		int status;
		exitCode = numeric_limits<int>::min();
		//Added by Tamar
		auto startInMs = chrono::high_resolution_clock::now();
		struct rusage ru;
		while ((pidr = wait4(pid, &status, WNOHANG | WUNTRACED, &ru)) == 0) {
			readWrite(fdread, fdwrite);
			usleep(5000);

			// TERMSIG or timeout or program output too large?
			if (Stop::isTERMRequested() || (time(NULL) - start) >= timeout || outputTooLarge) { //Changed by Tamar
				
				//Added by Tamar
				//if ((now - startInMs) >= timeoutInMs && timeoutInMs != chrono::milliseconds(0)) {
				//    programTimeoutInMs = true;
				//}

				if ((time(NULL) - start) >= timeout) {
					programTimeout = true;
				}
				kill(pid, SIGTERM); // Send SIGTERM normal termination
				int otherstatus;
				usleep(5000);
				if (waitpid(pid, &otherstatus, WNOHANG | WUNTRACED) == pid) {
					break;
				}
				if (kill(pid, SIGQUIT) == 0) { // Kill
					break;
				}
			}
		}
		
		//Added by Tamar
		auto endInMs = chrono::high_resolution_clock::now();
		elapsedTime = chrono::duration_cast<chrono::milliseconds>(endInMs - startInMs);
		userTime = ru.ru_utime;
		systemTime = ru.ru_stime;

		if (pidr == pid) {
			if (WIFSIGNALED(status)) {
				int signal = WTERMSIG(status);
				executionError = true;
				sprintf(executionErrorReason, "Program terminated due to \"%s\" (%d)\n", strsignal(signal), signal);
			}
			if (WIFEXITED(status)) {
				exitCode = WEXITSTATUS(status);
			} else {
				executionError = true;
				strcpy(executionErrorReason, "Program terminated but unknown reason.");
			}

			//Added by Tamar
			struct rusage ru;
			if (getrusage(RUSAGE_CHILDREN, &ru) == 0) {
				maxResidentSetSize = ru.ru_maxrss; // In kilobytes
			}


		} else if (pidr != 0) {
			executionError = true;
			strcpy(executionErrorReason, "waitpid error");
		}
		readWrite(fdread, fdwrite);
		correctExitCode = isExitCodeTested() && expectedExitCode == exitCode;
		correctOutput = match(programOutputAfter) || match(programOutputBefore + programOutputAfter);

	}





	// Main runTest function
	void TestCase::runTestWithCompare(time_t timeout, chrono::milliseconds timeoutInMs) {
		time_t start = time(NULL);

		input = processArrayInput(input);
		elapsedTime = chrono::milliseconds(0);
		maxResidentSetSize = 0;
		userTime = {0, 0};
		systemTime = {0, 0};

		teacherCommand = "./vpl_test_teacher";

		if (!programToRun.empty() && programToRun.size() < 512) {
			command = programToRun.c_str();
			teacherCommand = "./inputFile2.sh"; // Update as necessary
		}

		if (!Tools::existFile(command)) {
			executionError = true;
			sprintf(executionErrorReason, "Execution file not found '%s'", command);
			return;
		}

		if (!Tools::existFile(teacherCommand)) {
			executionError = true;
			sprintf(executionErrorReason, "Execution file not found '%s'", teacherCommand);
			return;
		}

		if (programArgs.size() > 0) {
			splitArgs(programArgs);
		}

		// Initialize ProcessInfo structs for student and teacher
		ProcessInfo studentProcess, teacherProcess;
		studentProcess.command = command;
		teacherProcess.command = teacherCommand;

		// Set up pipes
		if (!setupPipes(studentProcess) || !setupPipes(teacherProcess)) {
			return;
		}

		// Start processes
		if (!startProcess(studentProcess, argv, envv)) {
			return;
		}
		if (!startProcess(teacherProcess, argv, envv)) {
			return;
		}

		// Close unused pipe ends
		closeUnusedPipeEnds(studentProcess);
		closeUnusedPipeEnds(teacherProcess);

		// Set non-blocking mode for output pipes
		Tools::fdblock(studentProcess.outPipe[0], false);
		Tools::fdblock(teacherProcess.outPipe[0], false);

		// Write input to both processes
		programInput = input;
		if (!programInput.empty()) {
			if (programInput.back() != '\n') {
				programInput += '\n';
			}
			writeInputToProcess(studentProcess, programInput);
			writeInputToProcess(teacherProcess, programInput);
		}

		// Start times for each process
		auto startTime = chrono::high_resolution_clock::now();
		studentProcess.startTime = startTime;
		teacherProcess.startTime = startTime;

		// Main loop to monitor both processes
		while (!studentProcess.finished || !teacherProcess.finished) {
			// Use select() to monitor both output pipes
			fd_set readfds;
			FD_ZERO(&readfds);
			int maxfd = 0;

			if (!studentProcess.finished) {
				FD_SET(studentProcess.outPipe[0], &readfds);
				if (studentProcess.outPipe[0] > maxfd) maxfd = studentProcess.outPipe[0];
			}
			if (!teacherProcess.finished) {
				FD_SET(teacherProcess.outPipe[0], &readfds);
				if (teacherProcess.outPipe[0] > maxfd) maxfd = teacherProcess.outPipe[0];
			}

			struct timeval tv;
			tv.tv_sec = 0;
			tv.tv_usec = 5000; // 5ms timeout

			int ret = select(maxfd + 1, &readfds, NULL, NULL, &tv);
			if (ret > 0) {
				// Read from student output
				if (!studentProcess.finished && FD_ISSET(studentProcess.outPipe[0], &readfds)) {
					readFromPipe(studentProcess.outPipe[0], studentProcess.output);
				}
				// Read from teacher output
				if (!teacherProcess.finished && FD_ISSET(teacherProcess.outPipe[0], &readfds)) {
					readFromPipe(teacherProcess.outPipe[0], teacherProcess.output);
				}
			}

			// Check if student process has terminated
			if (!studentProcess.finished) {
				checkProcessTermination(studentProcess);
				if (studentProcess.finished) {
					exitCode = studentProcess.exitCode;
					userTime = studentProcess.userTime;
					systemTime = studentProcess.systemTime;
					maxResidentSetSize = studentProcess.maxResidentSetSize;
					elapsedTime = studentProcess.elapsedTime;
				}
			}

			// Check if teacher process has terminated
			if (!teacherProcess.finished) {
				checkProcessTermination(teacherProcess);
			}

			// Check for termination conditions (timeout, etc.)
			if (Stop::isTERMRequested() || (time(NULL) - start) >= timeout || outputTooLarge) {
				if ((time(NULL) - start) >= timeout) {
					programTimeout = true;
				}
				if (!studentProcess.finished) {
					kill(studentProcess.pid, SIGTERM);
					usleep(3000);
					waitpid(studentProcess.pid, NULL, WNOHANG | WUNTRACED);
					kill(studentProcess.pid, SIGQUIT);
					studentProcess.finished = true;
				}
				if (!teacherProcess.finished) {
					kill(teacherProcess.pid, SIGTERM);
					usleep(3000);
					waitpid(teacherProcess.pid, NULL, WNOHANG | WUNTRACED);
					kill(teacherProcess.pid, SIGQUIT);
					teacherProcess.finished = true;
				}
				break;
			}
		}

		// Read any remaining data from pipes
		if (!studentProcess.finished) {
			readFromPipe(studentProcess.outPipe[0], studentProcess.output);
			close(studentProcess.outPipe[0]);
		}
		if (!teacherProcess.finished) {
			readFromPipe(teacherProcess.outPipe[0], teacherProcess.output);
			close(teacherProcess.outPipe[0]);
		}

		correctExitCode = isExitCodeTested() && expectedExitCode == exitCode;
		correctOutput = match(studentProcess.output) || match(programOutputBefore + studentProcess.output);

		compareAndPrintResults(studentProcess, teacherProcess);

	}



	bool TestCase::match(string data) {
		for (size_t i = 0; i < output.size(); i++)
			if (output[i]->match(data))
				return true;
		return false;
	}

	/**
	 * Class Evaluation Definitions
	 */

	Evaluation::Evaluation() {
		grade = 0;
		ncomments = 0;
		nerrors = 0;
		nruns = 0;
		noGrade = true;

		//Added by Tamar
		strcpy(executionErrorReason, "");
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
		testCases.push_back(TestCase(testCases.size() + 1, caso.getInput(), caso.getOutput(),
				caso.getCaseDescription(), caso.getGradeReduction(), caso.getFailMessage(),
				caso.getProgramToRun(), caso.getProgramArgs(), caso.getExpectedExitCode(), caso.getInputSize() ));
	}

	void Evaluation::removeLastNL(string &s) {
		if (s.size() > 0 && s[s.size() - 1] == '\n') {
			s.resize(s.size() - 1);
		}
	}

	bool Evaluation::cutToEndTag(string &value, const string &endTag) {
		size_t pos;
		if (endTag.size() && (pos = value.find(endTag)) != string::npos) {
			value.resize(pos);
			return true;
		}
		return false;
	}

	void Evaluation::loadTestCases(string fname) {
		//Adde by Tamar


		if(!Tools::existFile(fname)) return;
		const char *CASE_TAG = "case=";
		const char *INPUT_TAG = "input=";
		const char *INPUT_END_TAG = "inputend=";
		const char *OUTPUT_TAG = "output=";
		const char *OUTPUT_END_TAG = "outputend=";
		const char *GRADEREDUCTION_TAG = "gradereduction=";
		const char *FAILMESSAGE_TAG = "failmessage=";
		const char *PROGRAMTORUN_TAG = "programtorun=";
		const char *PROGRAMARGS_TAG = "programarguments=";
		const char *EXPECTEDEXITCODE_TAG = "expectedexitcode=";
		const char *VARIATION_TAG = "variation=";

		//Added by Tamar
		const char *INPUTSIZE_TAG = "inputsize=";
		
		enum {
			regular, ininput, inoutput
		} state;
		bool inCase = false;
		vector<string> lines = Tools::splitLines(Tools::readFile(fname));
		remove(fname.c_str());
		string inputEnd = "";
		string outputEnd = "";
		Case caso;
		string output = "";
		string tag, value;
		/* must be changed from String
		* to pair type (regexp o no) and string. */
		state = regular;
		int nlines = lines.size();
		for (int i = 0; i < nlines; i++) {
			string &line = lines[i];
			Tools::parseLine(line, tag, value);
			if (state == ininput) {
				if (inputEnd.size()) { // Check for end of input.
					size_t pos = line.find(inputEnd);
					if (pos == string::npos) {
						caso.addInput(line + "\n");
					} else {
						cutToEndTag(line, inputEnd);
						caso.addInput(line);
						state = regular;
						continue; // Next line.
					}
				} else if (tag.size() && (tag == OUTPUT_TAG || tag
						== GRADEREDUCTION_TAG || tag == CASE_TAG)) {// New valid tag.
					state = regular;
					// Go on to process the current tag.
				} else {
					caso.addInput(line + "\n");
					continue; // Next line.
				}
			} else if (state == inoutput) {
				if (outputEnd.size()) { // Check for end of output.
					size_t pos = line.find(outputEnd);
					if (pos == string::npos) {
						output += line + "\n";
					} else {
						cutToEndTag(line, outputEnd);
						output += line;
						caso.addOutput(output);
						output = "";
						state = regular;
						continue; // Next line.
					}
				} else if (tag.size() && (tag == INPUT_TAG || tag == OUTPUT_TAG
						|| tag == GRADEREDUCTION_TAG || tag == CASE_TAG)) {// New valid tag.
					removeLastNL(output);
					caso.addOutput(output);
					output = "";
					state = regular;
				} else {
					output += line + "\n";
					continue; // Next line.
				}
			}
			if (state == regular && tag.size()) {
				if (tag == INPUT_TAG) {
					inCase = true;
					if (cutToEndTag(value, inputEnd)) {
						caso.addInput(value);
					} else {
						state = ininput;
						caso.addInput(value + '\n');
					}
				} else if (tag == OUTPUT_TAG) {
					inCase = true;
					if (cutToEndTag(value, outputEnd))
						caso.addOutput(value);
					else {
						state = inoutput;
						output = value + '\n';
					}
				} else if (tag == GRADEREDUCTION_TAG) {
					inCase = true;
					value = Tools::trim(value);
					// A percent value?
					if( value.size() > 1 && value[ value.size() - 1 ] == '%' ){
						float percent = atof(value.c_str());
						caso.setGradeReduction((grademax-grademin)*percent/100);
					}else{
						caso.setGradeReduction( atof(value.c_str()) );
					}
				} else if (tag == EXPECTEDEXITCODE_TAG) {
					caso.setExpectedExitCode( atoi(value.c_str()) );
				} else if (tag == PROGRAMTORUN_TAG) {
					caso.setProgramToRun(Tools::trim(value));
				} else if (tag == PROGRAMARGS_TAG) {
					caso.setProgramArgs(Tools::trim(value));
				} else if (tag == FAILMESSAGE_TAG) {
					caso.setFailMessage(Tools::trim(value));
				//Added by Tamar
				} else if (tag == INPUTSIZE_TAG) {
					caso.addInputSize(value);
				} else if (tag == VARIATION_TAG) {
					caso.setVariation(value);
				} else if (tag == INPUT_END_TAG) {
					inputEnd = Tools::trim(value);
				} else if (tag == OUTPUT_END_TAG) {
					outputEnd = Tools::trim(value);
				} else if (tag == CASE_TAG) {
					if (inCase) {
						addTestCase(caso);
						caso.reset();
					}
					inCase = true;
					caso.setCaseDescription( Tools::trim(value) );
				} else {
					if ( line.size() > 0 ) {
						char buf[250];
						sprintf(buf,"Syntax error: unexpected line %d ", i+1);
						addFatalError(buf);
					}
				}
			}
		}
		// TODO review
		if (state == inoutput) {
			removeLastNL(output);
			caso.addOutput(output);
		}
		if (inCase) { // Last case => save current.
			addTestCase(caso);
		}
	}

	bool Evaluation::loadParams() {
		grademin= Tools::getenv("VPL_GRADEMIN", 0.0);
		grademax = Tools::getenv("VPL_GRADEMAX", 10);
		maxtime = (int) Tools::getenv("VPL_MAXTIME", 20);
		variation = Tools::toLower(Tools::trim(Tools::getenv("VPL_VARIATION","")));
		noGrade = grademin >= grademax;
		return true;
	}

	void Evaluation::addFatalError(const char *m) {
		float reduction = grademax - grademin;
		if (ncomments >= MAXCOMMENTS)
			ncomments = MAXCOMMENTS - 1;

		snprintf(titles[ncomments], MAXCOMMENTSTITLELENGTH, "%s", m);
		snprintf(titlesGR[ncomments], MAXCOMMENTSTITLELENGTH, "%s (%.2f)", m, reduction);
		strcpy(comments[ncomments], "");
		ncomments ++;
		grade = grademin;
	}



	
	#include <fstream>
	#include <iostream>

	struct Config {
		double epsilon;
		int maxtime;
		double maxRatioToReset;
		double maxDeletionFraction;
	};

	struct TestResult {
		string testId;
		double cpuTimeRatio;

		TestResult(const string& id, double ratio)
			: testId(id), cpuTimeRatio(ratio) {}
	};


	// Function to load configuration from a JSON file
	Config loadConfig(const string& filename) {
		Config config;
		ifstream configFile(filename);

		
		try {
			nlohmann::json jsonConfig;
			configFile >> jsonConfig;

			// Validate and assign configuration parameters
			if (jsonConfig.contains("epsilon") && jsonConfig["epsilon"].is_number()) {
				config.epsilon = jsonConfig["epsilon"].get<double>();
			} else {
				std::cerr << "Warning: 'epsilon' not found or not a number in config. Using default value: " << config.epsilon << std::endl;
			}

			if (jsonConfig.contains("maxRatioToReset") && jsonConfig["maxRatioToReset"].is_number()) {
				config.maxRatioToReset = jsonConfig["maxRatioToReset"].get<double>();
			} else {
				std::cerr << "Warning: 'maxRatioToReset' not found or not a number in config. Using default value: " << config.maxRatioToReset << std::endl;
			}

			if (jsonConfig.contains("maxDeletionFraction") && jsonConfig["maxDeletionFraction"].is_number()) {
				config.maxDeletionFraction = jsonConfig["maxDeletionFraction"].get<double>();
			} else {
				std::cerr << "Warning: 'maxDeletionFraction' not found or not a number in config. Using default value: " << config.maxDeletionFraction << std::endl;
			}

		} catch (nlohmann::json::parse_error& e) {
			std::cerr << "Parse Error: " << e.what() << ". Using default configuration." << std::endl;
		} catch (nlohmann::json::type_error& e) {
			std::cerr << "Type Error: " << e.what() << ". Using default configuration." << std::endl;
		} catch (std::exception& e) {
			std::cerr << "Unexpected Error: " << e.what() << ". Using default configuration." << std::endl;
		}


		return config;
	}


	void printCpuRatios(const vector<TestResult>& testResults) {
		cout << "CPU Time Ratios:" << endl;
		for (const auto& result : testResults) {
			cout << "Test Name: " << result.testId << ", CPU Time Ratio: " << result.cpuTimeRatio << endl;
		}
	}

	// Function to calculate the mean of CPU time ratios
	double calculateMean(const vector<TestResult>& testResults) {
		double sum = 0.0;
		int count = testResults.size();

		for (const auto& test : testResults) {
			sum += test.cpuTimeRatio;
		}

		return count > 0 ? sum / count : 0.0;
	}


	// Function to calculate the standard deviation of CPU time ratios
	double calculateStdev(const vector<TestResult>& testResults, double mean) {
		double sumSqDiff = 0.0;
		int count = testResults.size();

		for (const auto& test : testResults) {
			sumSqDiff += pow(test.cpuTimeRatio - mean, 2);
		}

		return count > 0 ? sqrt(sumSqDiff / count) : 0.0;
	}


	void filterOutliers(vector<TestResult>& testResults, const Config& config) {
		double mean = calculateMean(testResults);
		double stdev = calculateStdev(testResults, mean);

		// Calculate the maximum number of deletions allowed
		int totalTests = testResults.size();
		int maxDeletions = static_cast<int>(totalTests * config.maxDeletionFraction);

		// Counter to track the number of deletions
		int deletionCount = 0;

		// Remove entries that are outside of mean ± stdev range, up to maxDeletions
		auto it = remove_if(testResults.begin(), testResults.end(),
			[mean, stdev, &deletionCount, maxDeletions](const TestResult& test) {
				if (deletionCount >= maxDeletions) {
					return false;  // Stop deleting if limit is reached
				}

				bool isOutlier = abs(test.cpuTimeRatio - mean) > stdev;

				if (isOutlier) {
					cout << "Deleting test: " << test.testId << endl;
					deletionCount++;  // Increment deletion counter
				}

				return isOutlier;
			});

		testResults.erase(it, testResults.end());
	}



	void Evaluation::runTests() {
		Config config;
		bool isCompetition = false;
		if (testCases.size() == 0) {
			return;
		}
		if (maxtime < 0) {
			addFatalError("Global timeout");
			return;
		}
		vector<TestResult> testResults;
		nerrors = 0;
		nruns = 0;
		grade = grademax;
		float defaultGradeReduction = (grademax - grademin) / testCases.size();
		int timeout = maxtime / testCases.size();
		for (size_t i = 0; i < testCases.size(); i++) {
			isCompetition = false;
			printf("Testing %lu/%lu : %s\n", (unsigned long)i + 1, (unsigned long)testCases.size(), testCases[i].getCaseDescription().c_str());

			if (timeout <= 1 || Timer::elapsedTime() >= maxtime) {
				grade = grademin;
				addFatalError("Global timeout");
				return;
			}
			if (maxtime - Timer::elapsedTime() < timeout) { // Try to run last case
				timeout = maxtime - Timer::elapsedTime();
			}
			
			if(!testCases[i].getInputSize().empty()){
				isCompetition = true;
				testCases[i].runTestWithCompare(timeout, chrono::milliseconds(0));  //Changed by Tamar
			}
			else{
				testCases[i].runTest(timeout, chrono::milliseconds(0));
			}
			if(isCompetition){
				if (!Tools::existFile("config.json")) {
					sprintf(executionErrorReason, "Error: config.json file not found.");
				}
				else 
				{
					std::string configContent = Tools::readFile("config.json");
					if (configContent.empty()){
						sprintf(executionErrorReason, "Error: config.json is empty or could not be read.");
					}
					else {
						config = loadConfig("config.json");
					}
				}
			}
			nruns++;

			float gr = testCases[i].getGradeReduction();
			if (gr == numeric_limits<float>::min()){
				testCases[i].setGradeReductionApplied(defaultGradeReduction);
			}
			else
				testCases[i].setGradeReductionApplied(gr);

			if (!testCases[i].isCorrectResult()) {
				if (Stop::isTERMRequested())
					break;
				
				grade -= testCases[i].getGradeReductionApplied();
				if (grade < grademin) {
					grade = grademin;
				}
				nerrors++;
				if (ncomments < MAXCOMMENTS) {
					strncpy(titles[ncomments], testCases[i].getCommentTitle().c_str(), MAXCOMMENTSTITLELENGTH);
					strncpy(titlesGR[ncomments], testCases[i].getCommentTitle(true).c_str(), MAXCOMMENTSTITLELENGTH);
					strncpy(comments[ncomments], testCases[i].getComment().c_str(), MAXCOMMENTSLENGTH);
					ncomments++;
				}
			}
			else{
				if(isCompetition){
					if(testCases[i].getCpuTimeRatio() < config.maxRatioToReset){
						grade -= testCases[i].getGradeReductionApplied();
						if (grade < grademin) {
							grade = grademin;
						}
					}
					testResults.emplace_back(testCases[i].getCaseDescription(), testCases[i].getCpuTimeRatio());
				}
			}
		}

		if(testResults.size() > 0){
			//printCpuRatios(testResults);
			double epsilon = config.epsilon;
			double mean = calculateMean(testResults);
			cout << "Mean Before: " << mean << endl;
			cout << "Standard Deviation: " << calculateStdev(testResults, mean) << endl;

			// Filter outliers and recalculate mean
			filterOutliers(testResults, config);
			mean = calculateMean(testResults);
			cout << "Mean After: " << mean << endl;

			// Round mean to two decimal places for consistent grade calculation
			mean = round(mean * 100.0) / 100.0;

			// Calculate grade reduction based on 0.01 increments below 1
			if (mean < 1 && mean > 0.00) {
				double gradeReduce = ((1 - mean) / 0.01) * epsilon;
				cout << "Grade Reduce: " << gradeReduce << endl;
				grade -= gradeReduce;
				if (grade < grademin) {
					grade = grademin;
				}
			}
		}
		
	}


	#include <iomanip>
	void Evaluation::outputEvaluation() {
		
		const char* stest[] = {" test", "tests"};
		if (strlen(executionErrorReason) > 0) {
			printf("\nExecution error: %s\n", executionErrorReason);
		}
		if (testCases.size() == 0) {
			printf("<|--\n");
			printf("-No test case found\n");
			printf("--|>\n");
		}
		if (ncomments > 1) {
			printf("\n<|--\n");        
			printf("-Failed tests\n");
			for (int i = 0; i < ncomments; i++) {
				printf("%s", titles[i]);
			}
			printf("--|>\n");
		}
		if (ncomments > 0) {
			printf("\n<|--\n");
			for (int i = 0; i < ncomments; i++) {
				printf("-%s", titlesGR[i]);
				printf("%s\n", comments[i]);
			}
			printf("--|>\n");
		}
		int passed = nruns - nerrors;
		if (nruns > 0) {
			printf("\n<|--\n");
			printf("-Summary of tests\n");
			printf(">+------------------------------+\n");
			printf(">| %2d %s run/%2d %s passed |\n",
					nruns, nruns==1?stest[0]:stest[1],
					passed, passed==1?stest[0]:stest[1]); // Taken from Dominique Thiebaut
			printf(">+------------------------------+\n");
			printf("\n--|>\n\n");

			// Added by Tamar
			//printf("-Tests run time--\n");
			// for (size_t i = 0; i < testCases.size(); ++i) {
			//     // Convert elapsedTime to milliseconds before printing
			//     chrono::duration<double, milli> elapsed_ms = testCases[i].getElapsedTime();
			//     printf("Test %d run time: %f ms\n", (int)i + 1, elapsed_ms.count());
			// }
			cout << left << setw(25) << "Test Case Name"
          << setw(15) << "Input Size"
          << setw(20) << "Run Time (ms)"
		  << setw(20) << "CPU Time (ms)"
          << setw(20) << "Memory (KB)"
          << endl;

			cout << string(100, '-') << endl;

			for (size_t i = 0; i < testCases.size(); ++i) {
				string testName = testCases[i].getCaseDescription();
				string inputSize = testCases[i].getInputSize();
				chrono::duration<double, milli> elapsed_ms = testCases[i].getElapsedTime();
				long memoryKB = testCases[i].maxResidentSetSize;
				double userTime_ms = testCases[i].userTime.tv_sec * 1000.0 + testCases[i].userTime.tv_usec / 1000.0;
				double systemTime_ms = testCases[i].systemTime.tv_sec * 1000.0 + testCases[i].systemTime.tv_usec / 1000.0;
				double totalCpuTime_ms = userTime_ms + systemTime_ms;


				cout << left << setw(25) << testName
						<< setw(15) << inputSize
						<< setw(20) << fixed << setprecision(2) << elapsed_ms.count()
						<< setw(20) << totalCpuTime_ms
						<< setw(20) << memoryKB
						<< endl;
			}

		}
		if (!noGrade) {
			char buf[100];
			sprintf(buf, "%5.2f", grade);
			int len = strlen(buf);
			if (len > 3 && strcmp(buf + (len - 3), ".00") == 0)
				buf[len - 3] = 0;
			printf("\nGrade :=>>%s\n", buf);
		}
		fflush(stdout);
	}

	void nullSignalCatcher(int n) {
		//printf("Signal %d\n",n);
	}

	void signalCatcher(int n) {
		//printf("Signal %d\n",n);
		if (Stop::isTERMRequested()) {
			Evaluation* obj = Evaluation::getSinglenton();
			obj->outputEvaluation();
			abort();
		}
		Evaluation *obj = Evaluation::getSinglenton();
		if (n == SIGTERM) {
			obj->addFatalError("Global test timeout (TERM signal received)");
		} else {
			obj->addFatalError("Internal test error");
			obj->outputEvaluation();
			Stop::setTERMRequested();
			abort();
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
		Timer::start();

		//Added by Tamar
		Timer::startInMs();

		TestCase::setEnvironment(env);
		setSignalsCatcher();
		Evaluation* obj = Evaluation::getSinglenton();
		obj->loadParams();
		obj->loadTestCases("evaluate.cases");
		obj->runTests();
		obj->outputEvaluation();
		return EXIT_SUCCESS;
	}

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
#include <sys/time.h>
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
#include <termios.h>

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
	static string removeFirstSpace(const string &text);
	static string trimRight(const string &text);
	static string trim(const string &text);
	static void replaceAll(string &text, const string &oldValue, const string &newValue);
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
	time_t startTimeSec;
	suseconds_t startTimeUsec;
public:
	Timer();
	double elapsedTime();
};

/**
 * Interface OutputChecker
 */
class OutputChecker{
protected:
	string text;

public:
	OutputChecker(const string &t):text(t) {}
	virtual ~OutputChecker() {};
	virtual string type() {return "";}
	virtual operator string() {return "";}
	virtual string outputExpected() {return text;}
	virtual string studentOutputExpected() {return text;}
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
	NumbersOutput(const string &text);
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
 * Constant strings classes
 * 
 */
class MessageMarks {
public:
	static const string input;
	static const string check_type;
	static const string expected_output;
	static const string program_output;
	static const string expected_exit_code;
	static const string exit_code;
	static const string time_limit;
};

class DefaultMessage {
public:
	static const string fail_output;
	static const string timeout;
	static const string fail_exit_code;
};


/**
 * Class Case Declaration
 * Case represents cases
 */
class Case {
	static string stringNoSet;
	string input;
	vector< string > output;
	string caseDescription;
	float gradeReduction;
	string failMessage;
	string timeoutMessage;
	string failExitCodeMessage;
	string programToRun;
	string programArgs;
	int expectedExitCode; // Default value -1
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
	void setTimeoutMessage(const string &);
	const string& getTimeoutMessage() const;
	void setFailExitCodeMessage(const string &);
	const string& getFailExitCodeMessage() const;
	void setCaseDescription(const string &);
	const string& getCaseDescription() const;
	void setGradeReduction(float);
	float getGradeReduction() const;
	void setExpectedExitCode(int);
	int getExpectedExitCode() const;
	void setProgramToRun(const string &);
	const string& getProgramToRun() const;
	void setProgramArgs(const string &);
	const string& getProgramArgs() const;
	void setVariation(const string &);
	const string& getVariation() const;
	void setTimeLimit(double);
	double getTimeLimit() const;
};

/**
 * Class TestCase Declaration
 * TestCase represents cases to tested
 */
class TestCase {
	const char *command;
	const char **argv;
	static const char **envv;
	int id;
	bool correctOutput;
	bool outputTooLarge;
	bool programTimeout;
	bool executionError;
	bool correctExitCode;
	char executionErrorReason[1000];
	int sizeReaded;
	Case caso;
	vector< OutputChecker* > output;
	float gradeReductionApplied;
	int exitCode; // Default value std::numeric_limits<int>::min()
	string programOutputBefore, programOutputAfter, programInput;

	void cutOutputTooLarge(string &output);
	void readWrite(int fdread, int fdwrite);
	void addOutput(const string &o, const string &actualCaseDescription);
	string formatCustomComment(const string &comment);
public:
	static void setEnvironment(const char **environment);
	void setDefaultCommand();
	TestCase(const TestCase &o);
	TestCase& operator=(const TestCase &o);
	~TestCase();
	TestCase(int id, const Case &caso);
	bool isCorrectResult();
	bool isExitCodeTested();
	float getGradeReduction();
	void setGradeReductionApplied(float r);
	float getGradeReductionApplied();
	string getCaseDescription();
	string getCommentTitle(bool withGradeReduction/*=false*/); // Suui
	string getComment();
	void splitArgs(string);
	void runTest(double timeout);
	bool match(string data);
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
	int nerrors, nruns;
	vector<TestCase> testCases;
	char comments[MAXCOMMENTS + 1][MAXCOMMENTSLENGTH + 1];
	char titles[MAXCOMMENTS + 1][MAXCOMMENTSTITLELENGTH + 1];
	char titlesGR[MAXCOMMENTS + 1][MAXCOMMENTSTITLELENGTH + 1];
	volatile int ncomments;
	volatile bool stopping;
	static Evaluation *singlenton;
	Evaluation();

public:
	static Evaluation* getSinglenton();
	static void deleteSinglenton();
	void addTestCase(Case &);
	void removeLastNL(string &s);
	bool cutToEndTag(string &value, const string &endTag);
	bool isValidTag(const string& tag);
	void loadTestCases(string fname);
	bool loadParams();
	void addFatalError(const char *m);
	void runTests();
	void outputEvaluation();
};

/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// END OF DECLARATIONS ///////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////// BEGINNING OF DEFINITIONS ////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////

volatile bool Stop::TERMRequested = false;
time_t Timer::startTime;
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
	if (f != NULL) {
		while (fgets(buf, 1000, f) != NULL)
			res += buf;
		fclose(f);
	}
	Tools::removeCRs(res);
	return res;
}

void Tools::removeCRs(string &text) {
	size_t len = text.size();
	bool noNL = true;
	for(size_t i = 0; i < len; i++) {
		if (text[i] == '\n') {
			noNL = false;
			break;
		};
	}
	if (noNL) { //Replace CR by NL
		for(size_t i = 0; i < len; i++) {
			if (text[i] == '\r') {
				text[i] = '\n';
			}
		}
	} else { //Remove CRs if any
		size_t lenClean = 0;
		for(size_t i = 0; i < len; i++) {
			if (text[i] != '\r') {
				text[lenClean] = text[i];
				lenClean++;
			}
		}
		text.resize(lenClean);
	}
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

string Tools::removeFirstSpace(const string &text) {
	if (text.size() > 0 && text[0] == ' ') {
		return text.substr(1);
	}
	return text;
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

void Tools::sanitizeTag(string &text, const string& tag) {
	size_t pos = 0;
	while((pos = text.find(tag, pos)) != string::npos) {
		text[pos + 2] = '?';
	}
}

void Tools::sanitizeTags(string &text) {
	sanitizeTag(text, "<|--");
	sanitizeTag(text, "--|>");
	sanitizeTag(text, "Comment :=>>");
	sanitizeTag(text, "Grade :=>>");
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

void Tools::replaceAll(string &text, const string &oldValue, const string &newValue) {
    size_t startPos = 0;
	size_t oldValueLength = oldValue.length();
	size_t newValueLength = newValue.length();
    while((startPos = text.find(oldValue, startPos)) != std::string::npos) {
        text.replace(startPos, oldValueLength, newValue);
        startPos += newValueLength;
    }
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
		}else if(str.size() > 0){
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
	if(startWithAsterisk)
		offset = o.numbers.size()-l;
	for(size_t i = 0; i < l; i++)
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
		if (tokens[i] != o.tokens[offset + i ])
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
string showChars(const string& str) {
	string out= "[";
	for(int i = 0; i < str.size(); i++){
		if (str[i] < ' ') {
			char buf[100];
			sprintf(buf, "%d", (int)str[i]);
			out += buf;
		} else {
			out += str.substr(i, 1);
		}
		if (i < str.size() -1) out += ",";
	}
	out += "]";
	return out;
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
		if (cleanText != cleanOutput) {
			cout << showChars(cleanText) << endl;
			cout << showChars(cleanOutput) << endl;
		}
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

RegularExpressionOutput::RegularExpressionOutput(const string &text, const string &actualCaseDescription): OutputChecker(text) {

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
					exit(0);
			}
			pos++;
		}
	}
}

// Regular Expression compilation (with flags in mind) and comparison with the input and output evaluation
bool RegularExpressionOutput::match (const string& output) {
	reti = -1;
	int cflag = REG_EXTENDED;
	const char* in = cleanText.c_str();
	// Use POSIX-C regrex.h
	cflag |= flagI? REG_ICASE : 0;
	cflag |= flagM? REG_NEWLINE : 0;
	reti = regcomp(&expression, in, cflag);
	if (reti == 0) { // Compilation was successful
		const char * out = output.c_str();
		reti = regexec(&expression, out, 0, NULL, 0);
		regfree(&expression);
		if (reti == 0) { // Match
			return true;
		} else if (reti == REG_NOMATCH){ // No match
			return false;
		} else { // Memory Error
			Evaluation* p_ErrorTest = Evaluation::getSinglenton();
			string errorType = string("Error: out of memory error, during matching case ") + errorCase;
			const char* flagError = errorType.c_str();
			p_ErrorTest->addFatalError(flagError);
			p_ErrorTest->outputEvaluation();
			exit(0);
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
		delete []bff;
		exit(0);
	}
	return false;
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
 * Class MessageMarks Definitions
 * Marks place holder for output message
 */

const string MessageMarks::input = "<<<input>>>";
const string MessageMarks::check_type = "<<<check_type>>>";
const string MessageMarks::expected_output = "<<<expected_output>>>";
const string MessageMarks::program_output = "<<<program_output>>>";
const string MessageMarks::expected_exit_code = "<<<expected_exit_code>>>";
const string MessageMarks::exit_code = "<<<exit_code>>>";
const string MessageMarks::time_limit = "<<<time_limit>>>";

const string DefaultMessage::fail_output = "Incorrect program output\n"
			" --- Input ---\n" + MessageMarks::input + "\n"
			" --- Program output ---\n" + MessageMarks::program_output + "\n"
			" --- Expected output (" + MessageMarks::check_type + ")---\n" + MessageMarks::expected_output ;

const string DefaultMessage::timeout = "Program timeout after " + MessageMarks::time_limit + " sec\n"
									" --- Input ---\n" + MessageMarks::input + "\n"
									" --- Program output ---\n" + MessageMarks::program_output + "\n";

const string DefaultMessage::fail_exit_code = "Incorrect exit code. Expected " + MessageMarks::expected_exit_code +
											+ ", found " + MessageMarks::exit_code + "\n"
											" --- Input ---\n" + MessageMarks::input + "\n"
											" --- Program output ---\n" + MessageMarks::program_output + "\n";




/**
 * Class Case Definitions
 * Case represents cases
 */
string Case::stringNoSet = "-=-ristra no usada=-=";
Case::Case() {
	reset();
}

void Case::reset() {
	input = "";
	output.clear();
	caseDescription = "";
	gradeReduction = std::numeric_limits<float>::min();
	failMessage = "";
	timeoutMessage = "";
	failExitCodeMessage = "";
	programToRun = "";
	programArgs = "";
	variation = "";
	expectedExitCode = -1;
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
	if (failMessage.size()) {
		return failMessage;
	}
	return DefaultMessage::fail_output;
}

void Case::setTimeoutMessage(const string &s) {
	timeoutMessage = s;
}

const string& Case::getTimeoutMessage() const {
	if (timeoutMessage.size()) {
		return timeoutMessage;
	}
	return DefaultMessage::timeout;
}

void Case::setFailExitCodeMessage(const string &s) {
	failExitCodeMessage = s;
}

const string& Case::getFailExitCodeMessage() const {
	if (failExitCodeMessage.size()) {
		return failExitCodeMessage;
	}
	return DefaultMessage::fail_exit_code;
}

void Case::setCaseDescription(const string &s) {
	caseDescription = s;
}

const string& Case::getCaseDescription() const {
	return caseDescription;
}
void Case::setGradeReduction(float g) {
	gradeReduction = g;
}

float Case::getGradeReduction() const {
	return gradeReduction;
}

void Case::setExpectedExitCode(int e) {
	expectedExitCode = e;
}

int Case::getExpectedExitCode() const {
	return expectedExitCode;
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
	const int MAX = 1024* 10 ;
	// Buffer size to read
	const int POLLREAD = POLLIN | POLLPRI;
	// Poll to read from program
	struct pollfd devices[2];
	devices[0].fd = fdread;
	devices[1].fd = fdwrite;
	char buf[MAX];
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
			if (programInput.size() > 0) {
				programOutputBefore += string(buf, readed);
				cutOutputTooLarge(programOutputBefore);
			} else {
				programOutputAfter += string(buf, readed);
				cutOutputTooLarge(programOutputAfter);
			}
		}
	}
	if (devices[1].revents & POLLOUT) { // Write to program
	    if (programInput.size() > 0) {
    		int written = write(fdwrite, programInput.c_str(), Tools::nextLine(programInput));
    		if (written > 0) {
    			programInput.erase(0, written);
    		}
	    } else {
	        // End of input then send EOF
	        write(fdwrite, "\x04", 1);
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
	id = o.id;
	correctOutput = o.correctOutput;
	correctExitCode = o.correctExitCode;
	outputTooLarge = o.outputTooLarge;
	programTimeout = o.programTimeout;
	executionError = o.executionError;
	strcpy(executionErrorReason, o.executionErrorReason);
	sizeReaded = o.sizeReaded;
	caso = o.caso;
	gradeReductionApplied = o.gradeReductionApplied;
	programOutputBefore = o.programOutputBefore;
	programOutputAfter = o.programOutputAfter;
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
	strcpy(executionErrorReason,o.executionErrorReason);
	sizeReaded = o.sizeReaded;
	caso = o.caso;
	gradeReductionApplied = o.gradeReductionApplied;
	programOutputBefore = o.programOutputBefore;
	programOutputAfter = o.programOutputAfter;
	programInput = o.programInput;
	for(size_t i = 0; i < output.size(); i++)
		delete output[i];
	output.clear();
	for(size_t i = 0; i < o.output.size(); i++){
		output.push_back(o.output[i]->clone());
	}
	return *this;
}

TestCase::~TestCase() {
	for(size_t i = 0; i < output.size(); i++)
		delete output[i];
}

TestCase::TestCase(int id, const Case &caso) {
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
	strcpy(executionErrorReason, "");
	setDefaultCommand();
}

bool TestCase::isCorrectResult() {
	bool correct = correctOutput &&
			      ! programTimeout &&
				  ! outputTooLarge &&
				  ! executionError;
	return correct || (isExitCodeTested() && correctExitCode);
}

bool TestCase::isExitCodeTested() {
	return caso.getExpectedExitCode() >= 0;
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

string TestCase::getCommentTitle(bool withGradeReduction=false) {
	char buf[100];
	string ret;
	sprintf(buf, "Test %d", id);
	ret = buf;
	if (caso.getCaseDescription().size() > 0) {
		ret += ": " + caso.getCaseDescription();
	}
	if(withGradeReduction && getGradeReductionApplied() > 0){
		sprintf(buf," (%.3f)", -getGradeReductionApplied());
		ret += buf;
	}
	ret += '\n';
	return ret;
}

string TestCase::formatCustomComment(const string &comment) {
	char buffer[100];
	string formatedComment = comment;
	Tools::replaceAll(formatedComment, MessageMarks::input, Tools::caseFormat(caso.getInput()));
	if (output.size() > 0) {
		Tools::replaceAll(formatedComment, MessageMarks::check_type, output[0]->type());
		Tools::replaceAll(formatedComment, MessageMarks::expected_output, Tools::caseFormat(output[0]->studentOutputExpected()));
	}
	Tools::replaceAll(formatedComment, MessageMarks::program_output, Tools::caseFormat(programOutputBefore + programOutputAfter));
	if (isExitCodeTested()) {
		sprintf(buffer, "%d", caso.getExpectedExitCode());
		Tools::replaceAll(formatedComment, MessageMarks::expected_exit_code, buffer);
		sprintf(buffer, "%d", exitCode);
		Tools::replaceAll(formatedComment, MessageMarks::exit_code, buffer);
	}
	if (programTimeout) {
		sprintf(buffer, "%.2f", caso.getTimeLimit());
		Tools::replaceAll(formatedComment, MessageMarks::time_limit, buffer);
	}
	return formatedComment;
}

string TestCase::getComment() {
	if (isCorrectResult()) {
		return "";
	}
	char buffer[100];
	string ret;
	if(output.size()==0){
		ret += "Configuration error in the test case: the output is not defined";
	}
	if (programTimeout) {
		ret += "Program timeout\n";
	}
	if (outputTooLarge) {
		sprintf(buffer, "Program output too large (%dKb)\n", sizeReaded / 1024);
		ret += buffer;
	}
	if (executionError) {
		ret += executionErrorReason + string("\n");
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

void TestCase::runTest(time_t timeout) {// Timeout in seconds
	time_t start = time(NULL);
	int pp1[2]; // Send data
	int pp2[2]; // Receive data
	if (pipe(pp1) == -1 || pipe(pp2) == -1) {
		executionError = true;
		sprintf(executionErrorReason, "Internal error: pipe error (%s)",
				strerror(errno));
		return;
	}
	if ( programToRun > "" && programToRun.size() < 512) {
		command = programToRun.c_str();
	}
	if ( ! Tools::existFile(command) ){
		executionError = true;
		sprintf(executionErrorReason, "Execution file not found '%s'", command);
		return;
	}
	pid_t pid;
	if ( caso.getProgramArgs().size() > 0) {
		splitArgs(caso.getProgramArgs());
	}
	struct termios term;
    if (tcgetattr(STDIN_FILENO, &term) < 0) {
		// c_iflag - Input modes
		term.c_iflag = ICRNL | IXON;
		// c_oflag - Output modes
		term.c_oflag = OPOST | ONLCR;
		// c_cflag - Control modes
		term.c_cflag = CS8 | CREAD;
		// c_lflag - Local modes
		term.c_lflag = ISIG | IEXTEN | ECHO | ECHOE | ECHOK | ECHOCTL | ECHOKE;
		// c_cc - Special control characters		
        term.c_cc[VINTR] = 0x03;    // ^C
        term.c_cc[VQUIT] = 0x1c;    // ^backslash
        term.c_cc[VERASE] = 0x7f;   // ^?
        term.c_cc[VKILL] = 0x15;    // ^U
        term.c_cc[VEOF] = 0x04;     // ^D
        term.c_cc[VSTART] = 0x11;   // ^Q
        term.c_cc[VSTOP] = 0x13;    // ^S
        term.c_cc[VSUSP] = 0x1a;    // ^Z
        term.c_cc[VREPRINT] = 0x12; // ^R
        term.c_cc[VWERASE] = 0x17;  // ^W
        term.c_cc[VLNEXT] = 0x16;   // ^V
        term.c_cc[VDISCARD] = 0x0f; // ^O
        term.c_cc[VMIN] = 1;
        term.c_cc[VTIME] = 0;
		cfsetospeed(&term, B115200);
	}
	term.c_lflag |= ICANON;
	// Ensure new line handling
	term.c_iflag |= ICRNL;
	term.c_lflag &= ~ ECHO;
	signal(SIGTERM, SIG_IGN);
	signal(SIGKILL, SIG_IGN);
	int fdmaster = -1;
	if ((pid = forkpty(&fdmaster, NULL, &term, NULL)) == 0) {
		setpgrp();
		if (execve(command, (char * const *) argv, (char * const *) envv) == -1) {
			perror("Internal error, execve fails");
			abort(); //end of child
		}
	}
	if (pid == -1 || fdmaster == -1) {
		executionError = true;
		sprintf(executionErrorReason, "Internal error: forkpty error (%s)",
				strerror(errno));
		return;
	}
	close(pp1[0]);
	close(pp2[1]);
	int fdwrite = pp1[1];
	int fdread = pp2[0];
	Tools::fdblock(fdwrite, false);
	Tools::fdblock(fdread, false);
	programInput = input;
	if(programInput.size()==0){ // No input
		close(fdwrite);
	}
	programOutputBefore = "";
	programOutputAfter = "";
	pid_t pidr;
	int status;
	Timer timer;
	exitCode = std::numeric_limits<int>::min();
	while ((pidr = waitpid(pid, &status, WNOHANG | WUNTRACED)) == 0) {
		readWrite(fdmaster, fdmaster);
		usleep(5000);
		// TERMSIG or timeout or program output too large?
		if (Stop::isTERMRequested() || timer.elapsedTime() >= timeout
				|| outputTooLarge) {
			if (timer.elapsedTime() >= timeout) {
				programTimeout = true;
			}
			kill(pid, SIGTERM); // Send SIGTERM normal termination
			int otherstatus;
			usleep(5000);
			if (waitpid(pid, &otherstatus, WNOHANG | WUNTRACED) == pid) {
			    status = otherstatus;
				break;
			}
			if (kill(pid, SIGQUIT) == 0) { // Kill
				break;
			}
			usleep(5000);
			if (waitpid(pid, &otherstatus, WNOHANG | WUNTRACED) == pid) {
			    status = otherstatus;
			}
		}
	}
	if (pidr == pid) {
		if (WIFEXITED(status)) {
    			exitCode = WEXITSTATUS(status);
		} else if (WIFSIGNALED(status)) {
			int signal = WTERMSIG(status);
			executionError = true;
			sprintf(executionErrorReason,
					"Program terminated due to \"%s\" (%d)", strsignal(
							signal), signal);
        } else if (WIFSTOPPED(status)) {
            executionError = true;
            sprintf(executionErrorReason,
                "Child process was stopped by signal: %d", WSTOPSIG(status));
        } else if (WIFCONTINUED(status)) {
            executionError = true;
            sprintf(executionErrorReason, "Child process was continued.");
		} else {
			executionError = true;
			sprintf(executionErrorReason,
				"Program terminated but unknown reason. (%d)", status);
		}
	} else if (pidr != 0) {
		executionError = true;
		strcpy(executionErrorReason, "waitpid error");
	}
	readWrite(fdread, fdwrite);
	correctExitCode = isExitCodeTested() && expectedExitCode == exitCode;
	correctOutput = match(programOutputAfter)
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

/**
 * Class Evaluation Definitions
 */

Evaluation::Evaluation() {
	grade = 0;
	ncomments = 0;
	nerrors = 0;
	nruns = 0;
	noGrade = true;
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
	testCases.push_back(TestCase(testCases.size() + 1, caso));
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

const char *CASE_TAG = "case=";
const char *INPUT_TAG = "input=";
const char *INPUT_END_TAG = "inputend=";
const char *OUTPUT_TAG = "output=";
const char *OUTPUT_END_TAG = "outputend=";
const char *GRADEREDUCTION_TAG = "gradereduction=";
const char *FAILMESSAGE_TAG = "failmessage=";
const char *FAILOUTPUTMESSAGE_TAG = "failoutputmessage=";
const char *TIMEOUTMESSAGE_TAG = "timeoutmessage=";
const char *FAILEXITCODEMESSAGE_TAG = "failexitcodemessage=";
const char *PROGRAMTORUN_TAG = "programtorun=";
const char *PROGRAMARGS_TAG = "programarguments=";
const char *EXPECTEDEXITCODE_TAG = "expectedexitcode=";
const char *VARIATION_TAG = "variation=";
const char *TIMELIMIT_TAG = "timelimit=";
const char *allTags[] = {
	CASE_TAG,
	INPUT_TAG,
	INPUT_END_TAG,
	OUTPUT_TAG,
	OUTPUT_END_TAG,
	GRADEREDUCTION_TAG,
	FAILMESSAGE_TAG,
	FAILOUTPUTMESSAGE_TAG,
	TIMEOUTMESSAGE_TAG,
	FAILEXITCODEMESSAGE_TAG,
	PROGRAMTORUN_TAG,
	PROGRAMARGS_TAG,
	EXPECTEDEXITCODE_TAG,
	VARIATION_TAG,
	TIMELIMIT_TAG,
	NULL
};

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
		regular, inInput, inOutput, inFailMessage, inFailExitCodeMessage, inTimeoutMessage
	} state;
	bool inCase = false;
	vector<string> lines = Tools::splitLines(Tools::readFile(fname));
    remove(fname.c_str()); // Remove config file to avoid cheating
	string inputEnd = "";
	string outputEnd = "";
	Case defaultCaseValues;
	Case currentCase;
	string multiLineParameter = "";
	string tag, value;
	/* must be changed from String
	 * to pair type (regexp o no) and string. */
	state = regular;
	int nlines = lines.size();
	for (int i = 0; i < nlines; i++) {
		string &line = lines[i];
		Tools::parseLine(line, tag, value);
		if (state == inInput) {
			if (inputEnd.size()) { // Check for end of input.
				size_t pos = line.find(inputEnd);
				if (pos == string::npos) {
					multiLineParameter += line + '\n';
				} else {
					cutToEndTag(line, inputEnd);
					multiLineParameter += line + '\n';
					currentCase.setInput(multiLineParameter);
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
			if (outputEnd.size()) { // Check for end of output.
				size_t pos = line.find(outputEnd);
				if (pos == string::npos) {
					multiLineParameter += line + "\n";
				} else {
					cutToEndTag(line, outputEnd);
					multiLineParameter += line;
					currentCase.addOutput(multiLineParameter);
					state = regular;
					continue; // Next line.
				}
			} else if (isValidTag(tag)) {// New valid tag.
				removeLastNL(multiLineParameter);
				currentCase.addOutput(multiLineParameter);
				state = regular;
			} else {
				multiLineParameter += line + "\n";
				continue; // Next line.
			}
		} else if (state == inFailMessage ||
		           state == inFailExitCodeMessage ||
				   state == inTimeoutMessage) {
			if (isValidTag(tag)) { // New valid tag.
				switch (state) {
				case inFailMessage:
					currentCase.setFailMessage(multiLineParameter);
					break;
				case inFailExitCodeMessage:
					currentCase.setFailExitCodeMessage(multiLineParameter);
					break;
				case inTimeoutMessage:
					currentCase.setTimeoutMessage(multiLineParameter);
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
					if (cutToEndTag(value, inputEnd)) {
						currentCase.setInput(value);
					} else {
						state = inInput;
						multiLineParameter = Tools::removeFirstSpace(value) + '\n';
					}
				} else if (tag == OUTPUT_TAG) {
					if (cutToEndTag(value, outputEnd))
						currentCase.addOutput(value);
					else {
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
				} else if (tag == FAILMESSAGE_TAG || tag == FAILOUTPUTMESSAGE_TAG) {
					state = inFailMessage;
					multiLineParameter = Tools::removeFirstSpace(value);
				} else if (tag == TIMEOUTMESSAGE_TAG) {
					state = inTimeoutMessage;
					multiLineParameter = Tools::removeFirstSpace(value);
				} else if (tag == FAILEXITCODEMESSAGE_TAG) {
					state = inFailExitCodeMessage;
					multiLineParameter = Tools::removeFirstSpace(value);
				} else if (tag == VARIATION_TAG) {
					currentCase.setVariation(value);
				} else if (tag == TIMELIMIT_TAG) {
					currentCase.setTimeLimit(atof(value.c_str()));
				} else if (tag == INPUT_END_TAG) {
					inputEnd = Tools::trim(value);
				} else if (tag == OUTPUT_END_TAG) {
					outputEnd = Tools::trim(value);
				} else if (tag == CASE_TAG) {
					if (inCase) {
						addTestCase(currentCase);
						currentCase = defaultCaseValues;
					} else {
						inCase = true;
						defaultCaseValues = currentCase;
					}
					currentCase.setCaseDescription(Tools::trim(value));
				} else {
					char buf[250];
					sprintf(buf, "Syntax error in cases file (line:%d) unknow parameter", i+1);
					addFatalError(buf);
				}
			} else {
				if ( line.size() > 0 ) {
					string content = Tools::trim(line);
					if (content.size() > 0 && content[0] != '#') {
						char buf[250];
						sprintf(buf, "Syntax error in cases file (line:%d) text out of parameter or comment", i+1);
						addFatalError(buf);
					}
				}
			}
		}
	}
	// TODO review
	switch (state) {
		case inOutput:
			removeLastNL(multiLineParameter);
			currentCase.addOutput(multiLineParameter);
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
	}
	if (inCase) { // Last case => save current.
		addTestCase(currentCase);
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

void Evaluation::runTests() {
	if (testCases.size() == 0) {
		return;
	}
	if (maxtime < 0) {
		addFatalError("Global timeout");
		return;
	}
	nerrors = 0;
	nruns = 0;
	grade = grademax;
	float defaultGradeReduction = (grademax - grademin) / testCases.size();
	double defaultTestTimeLimit = maxtime / testCases.size();
	Timer globalTimer;
	for (size_t i = 0; i < testCases.size(); i++) {
		printf("Testing %lu/%lu : %s\n", (unsigned long) i+1, (unsigned long)testCases.size(), testCases[i].getCaseDescription().c_str());
		if (globalTimer.elapsedTime() >= maxtime) {
			grade = grademin;
			addFatalError("Global timeout");
			return;
		}
		testCases[i].runTest(defaultTestTimeLimit);
		nruns++;
		if (!testCases[i].isCorrectResult()) {
			if (Stop::isTERMRequested())
				break;
			float gr = testCases[i].getGradeReduction();
			if (gr == std::numeric_limits<float>::min())
				testCases[i].setGradeReductionApplied(defaultGradeReduction);
			else
				testCases[i].setGradeReductionApplied(gr);
			grade -= testCases[i].getGradeReductionApplied();
			if (grade < grademin) {
				grade = grademin;
			}
			nerrors++;
			if(ncomments < MAXCOMMENTS){
				strncpy(titles[ncomments], testCases[i].getCommentTitle().c_str(),
						MAXCOMMENTSTITLELENGTH);
				strncpy(titlesGR[ncomments], testCases[i].getCommentTitle(true).c_str(),
						MAXCOMMENTSTITLELENGTH);
				strncpy(comments[ncomments], testCases[i].getComment().c_str(),
						MAXCOMMENTSLENGTH);
				ncomments++;
			}
		}
	}
}

void Evaluation::outputEvaluation() {
	const char* stest[] = {" test", "tests"};
	if (testCases.size() == 0) {
		printf("<|--\n");
		printf("-Error no test case found\n");
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
	if ( ncomments > 0 ) {
		printf("\n<|--\n");
		for (int i = 0; i < ncomments; i++) {
			printf("-%s", titlesGR[i]);
			printf("%s\n", comments[i]);
		}
		printf("--|>\n");
	}
	int passed = nruns - nerrors;
	if ( nruns > 0 ) {
		printf("\n<|--\n");
		printf("-Summary of tests\n");
		printf(">+------------------------------+\n");
		printf(">| %2d %s run/%2d %s passed |\n",
				nruns, nruns==1?stest[0]:stest[1],
				passed, passed==1?stest[0]:stest[1]); // Taken from Dominique Thiebaut
		printf(">+------------------------------+\n");
		printf("\n--|>\n");
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
	TestCase::setEnvironment(env);
	setSignalsCatcher();
	Evaluation* obj = Evaluation::getSinglenton();
	obj->loadParams();
	obj->loadTestCases(caseFileName);
	obj->runTests();
	obj->outputEvaluation();
	return EXIT_SUCCESS;
}

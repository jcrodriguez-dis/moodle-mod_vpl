/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#include "output_checker.hpp"
#include "evaluation.hpp"

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

bool NumbersOutput::isNumStart(char c) {
	return isdigit(c) || c == '+' || c == '-' || c == '.';
}

bool NumbersOutput::isNum(char c) {
	return isNumStart(c) || c == 'e' || c == 'E';
}

bool NumbersOutput::calcStartWithAsterisk(){
	int l = text.size();
	for (int i = 0; i < l; i++){
		char c = text[i];
		if (isspace(c)) continue;
		if (c == '*'){
			cleanText = text.substr(i + 1, text.size() - (i + 1));
			return true;
		} else {
			cleanText = text.substr(i, text.size() - i);
			return false;
		}
	}
	return false;
}

NumbersOutput::NumbersOutput(const string &text):OutputChecker(text){
	int l = text.size();
	string str;
	Number number;
	bool contentDigit = false;
	for (int i = 0; i < l; i++){
		char c = text[i];
		if ((isNum(c) && str.size() > 0) || (isNumStart(c) && str.size() == 0)){
			str += c;
		    contentDigit = contentDigit || isdigit(c);
		} else if(str.size() > 0){
			if (isNumStart(str[0]) && contentDigit && number.set(str)) {
				numbers.push_back(number);
			}
			str = "";
			contentDigit = false;
		}
	}
	if(str.size() > 0){
		if(isNumStart(str[0]) && number.set(str)) numbers.push_back(number);
	}
	startWithAsterisk = calcStartWithAsterisk();
}

string NumbersOutput::studentOutputExpected(){
	return cleanText;
}

bool NumbersOutput::operator==(const NumbersOutput& o) const {
	#ifdef DEBUG
		cout << "Comparing " << (string)*this << " with " << (string)o << endl;
	#endif
	size_t len = numbers.size();
	int offset = 0;
	if(startWithAsterisk) {
		if( o.numbers.size() < len ) return false;
		offset = o.numbers.size() - len;
	} else {
		if (o.numbers.size() != len) return false;
	}
	for (size_t i = 0; i < len; i++)
		if(numbers[i] != o.numbers[offset + i])
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
	int l = text.size();
	string str;
	Number number;
	for(int i = 0; i < l; i++){
		char c = text[i];
		// Skip spaces/CR/LF... and *
		if(!isspace(c) && c !='*') {
			str += c;
		}else if(str.size() > 0) {
			if (!isNumStart(str[0])||
				!number.set(str)) return false;
			str="";
		}
	}
	if(str.size() > 0){
		if(!isNumStart(str[0]) || !number.set(str)) return false;
	}
	return true;
}

string NumbersOutput::type(){
	return "numbers";
}

NumbersOutput::operator string () const{
	string ret = "[";
	int l = numbers.size();
	for(int i = 0; i < l; i++){
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
	#ifdef DEBUG
		cout << "Comparing " << (string)*this << " with " << (string)o << endl;
	#endif
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

TextOutput::operator string () const{
	string ret = "[";
	int l = tokens.size();
	for(int i = 0; i < l; i++){
		ret += i > 0 ? ", " : "";
		ret += "\"" + tokens[i] + "\"";
	}
	ret += "]";
	return ret;
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
		startWithAsterisk = true;
		cleanText = clean.substr(2, clean.size() - 3);
	}else{
		startWithAsterisk = false;
		cleanText = clean.substr(1,clean.size()-2);
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
			out += Tools::int2str((int)str[i]);
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
	if (startWithAsterisk && cleanText.size() < cleanOutput.size()) {
		size_t start = cleanOutput.size() - cleanText.size();
		return cleanText == cleanOutput.substr(start, cleanText.size());
	} else {
		#ifdef DEBUG
			if (cleanText != cleanOutput) {
				cout << "Comparing " << showChars(cleanText) << " with " << showChars(cleanOutput) << endl;
			}
		#endif
		return cleanText == cleanOutput;
	}
}

OutputChecker* ExactTextOutput::clone(){
	return new ExactTextOutput(outputExpected());
}

bool ExactTextOutput::typeMatch(const string& text){
	string clean = Tools::trim(text);
	return (clean.size() > 1 && clean[0] =='"' && clean[clean.size()-1] == '"')
			||(clean.size() > 3 && clean[0] == '*' && clean[1] == '"' && clean[clean.size()-1] == '"');
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
	// Cleans the text: trims and unescapes converting \n, \t, \r, \", \', \\... to real characters
	string clean = Tools::unescapeString(Tools::trim(text));
	// Extracts the regex between the first pair of '/' and the flags after the second '/'
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
					flagI = true;
					break;
				case 'm':
					flagM = true;
					break;
				case ' ':
					break;
				default:
					Evaluation* eval = Evaluation::getSinglenton();
					char wrongFlag = clean[pos];
					string flagCatch;
					stringstream ss;
					ss << wrongFlag;
					ss >> flagCatch;
					string errorType = string("Error: invalid flag in regex output ") + string(errorCase) + string (", found a ") + string(flagCatch) + string (" used as a flag, only i and m available.");
					const char* flagError = errorType.c_str();
					eval->addFatalError(flagError);
					eval->outputEvaluation();
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
			Evaluation* eval = Evaluation::getSinglenton();
			string errorType = string("Error: out of memory error, during matching case ") + errorCase;
			const char* flagError = errorType.c_str();
			eval->addFatalError(flagError);
			eval->outputEvaluation();
			exit(0);
		}

	} else { // Compilation error
		size_t length = regerror(reti, &expression, NULL, 0);
        char* bff = new char[length + 1];
        (void) regerror(reti, &expression, bff, length);
		Evaluation* eval = Evaluation::getSinglenton();
		string errorType = "Error: regular expression compilation error";
		errorType += " in case: "+ string(errorCase) + string (": ") + string(bff);		const char* flagError = errorType.c_str();
		eval->addFatalError(flagError);
		eval->outputEvaluation();
		delete []bff;
		exit(0);
	}
	return false;
}

// Returns the expression without flags nor '/'
string RegularExpressionOutput::studentOutputExpected() {
	return cleanText;
}

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

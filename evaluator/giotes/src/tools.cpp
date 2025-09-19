/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#include "tools.hpp"

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

bool Tools::removeInputLine(string &data, const string &inputLine) {
	size_t pos = data.find(inputLine);
	if (pos != string::npos) {
		size_t end = inputLine.size();
		data.erase(pos, end - pos + 1);
		return true;
	}
	return false;
}

string Tools::caseFormatInline(const string &text) {
	static const char* ascii_control_icons[] = {
		"␀",  // 0  - Null
		"␁",  // 1  - Start of Heading
		"␂",  // 2  - Start of Text
		"␃",  // 3  - End of Text
		"␄",  // 4  - End of Transmission
		"␅",  // 5  - Enquiry
		"␆",  // 6  - Acknowledge
		"␇",  // 7  - Bell
		"␈",  // 8  - Backspace
		"⇥",  // 9  - Horizontal Tab
		"↵",  // 10 - Line Feed
		"␋",  // 11 - Vertical Tab
		"␌",  // 12 - Form Feed
		"␍",  // 13 - Carriage Return
		"␎",  // 14 - Shift Out
		"␏",  // 15 - Shift In
		"␐",  // 16 - Data Link Escape
		"␑",  // 17 - Device Control 1
		"␒",  // 18 - Device Control 2
		"␓",  // 19 - Device Control 3
		"␔",  // 20 - Device Control 4
		"␕",  // 21 - Negative Acknowledge
		"␖",  // 22 - Synchronous Idle
		"␗",  // 23 - End of Transmission Block
		"␘",  // 24 - Cancel
		"␙",  // 25 - End of Medium
		"␚",  // 26 - Substitute
		"␛",  // 27 - Escape
		"␜",  // 28 - File Separator
		"␝",  // 29 - Group Separator
		"␞",  // 30 - Record Separator
		"␟",  // 31 - Unit Separator
		"␣"    // 32 - Space (represented as Open Box)
	};
	string res;
	res.reserve(text.size());
	for (int i = 0; i < text.size(); i++) {
		int code = (int)text[i];
		if (code >= 0 && code <= 32) {
			#ifdef DEBUG
			cout << "ASCII control code: " << code << " a " << ascii_control_icons[code] <<endl;
			#endif
			res += ascii_control_icons[code];
		} else {
			res += text[i];
		}
	}
	return res;
}

string Tools::caseFormat(const string &text) {
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
	bool inSpace = false;
	for (int i = 0; i < len; i++) {
		char c = text[i];
		if (isspace(c)) {
			inSpace = true;
			continue;
		}
		if (inSpace) {
			if (c != '=' && res.size() > 0) {
				res += ' ';
			}
			inSpace = false;
		}
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

string Tools::unescapeString(const string &text) {
	string res;
	int len = text.size();
	res.reserve(len);
	for (int i = 0; i < len; i++) {
		char c = text[i];
		if (c == '\\' && i + 1 < len) {
			i++;
			switch (text[i]) {
				case 'n':
					res += '\n';
					break;
				case 't':
					res += '\t';
					break;
				case 'r':
					res += '\r';
					break;
				case '\\':
					res += '\\';
					break;
				default:
					res += c;
					res += text[i];
			}
		} else {
			res += c;
		}
	}
	return res;
}
/**
 * @brief Check and modify text value to avoid patterns <<<chars>>>.
 * 
 * @param value Value to check
 */
void Tools::sanitizePlaceholders(string &value) {
	static regex_t placeHolder;
	static bool firstTime = true;
	if (firstTime) {
		regcomp(&placeHolder, "<<<[a-z_]+>>>", REG_EXTENDED);
		firstTime = false;
	}
	regmatch_t matches[1];
	char *cursor = (char *)value.c_str();
	while (regexec(&placeHolder, cursor, 1, matches, 0) == 0) {
		cursor[matches[0].rm_so] = '?';
    }
}
/**
 * @brief Replace all occurrences of oldValue with newValue in text
 * 
 * @param text Text to change
 * @param oldValue Text to search
 * @param newValue Text to replace
 */
void Tools::replaceAll(string &text, const string &oldValue, const string &newValue) {
    size_t startPos = 0;
	size_t oldLength = oldValue.length();
	size_t newLength = newValue.length();
	string sanitizeReplaceValue = newValue;
	Tools::sanitizePlaceholders(sanitizeReplaceValue);
    while((startPos = text.find(oldValue, startPos)) != std::string::npos) {
        text.replace(startPos, oldLength, sanitizeReplaceValue);
        startPos += newLength;
    }
}

/**
 * @brief Replace all occurrences of oldValue with newValue in text.
 * 
 * This function is used to replace all occurrences of oldValue with newValue formated for case output.
 * If oldValue is not at a line start a new line is added.
 * 
 * @param text Text to change
 * @param oldValue Text to search
 * @param newValue Text to replace after be formated
 */
void Tools::replaceAllML(string &text, const string &oldValue, const string &newValue) {
	static const string nothig = "";
	static const string nl = "\n";
	const string *pre = &nothig;
	const string *post = &nothig;
	string newValueFormated = Tools::caseFormat(newValue);
	Tools::sanitizePlaceholders(newValueFormated);
	size_t startPos = 0;
	size_t oldLength = oldValue.length();
	size_t newLength = newValueFormated.length();
	removeLastNL(newValueFormated);
    while((startPos = text.find(oldValue, startPos)) != std::string::npos) {
		if (startPos == 0 || text[startPos - 1] == '\n') {
			pre = &nothig;
		} else {
			pre = &nl;
		}
		if (startPos + oldLength < text.size() && text[startPos + oldLength] == '\n') {
			post = &nothig;
		} else {
			post = &nl;
		}
        text.replace(startPos, oldLength, *pre + newValueFormated + *post);
        startPos += newLength;
    }
}

/**
 * @brief Replace all occurrences of oldValue with newValue in text.
 * 
 * This function is used to replace all occurrences of oldValue with newValue in text.
 * If oldValue is not at a line start a new line is added.
 * 
 * @param text Text to change
 * @param oldValue Text to search
 * @param newValue Text to replace
 */
void Tools::replaceAllIL(string &text, const string &oldValue, const string &newValue) {
	string newValueFormated = Tools::caseFormatInline(newValue);
	Tools::sanitizePlaceholders(newValueFormated);
    size_t startPos = 0;
	size_t oldLength = oldValue.length();
	size_t newLength = newValue.length();
    while((startPos = text.find(oldValue, startPos)) != std::string::npos) {
        text.replace(startPos, oldLength, newValueFormated);
        startPos += newLength;
    }
}

void Tools::removeLastNL(string &s){
	size_t len = s.size();
	if (len > 0 && s[len - 1] == '\n') {
		s.resize(len - 1);
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

string Tools::int2str(const long int data, int width) {
	stringstream conv;
	if (width > 0) {
		conv.width(width);
	}
	conv << data;
	return conv.str();
}

string Tools::double2str(const double data, int precision, int width) {
	stringstream conv;
	conv.precision(precision);
	if (width > 0) {
		conv.width(width);
	}
	conv << data;
	return conv.str();
}

string Tools::getenv(const string &name, const string &defaultvalue, bool warn) {
	const char* value = ::getenv(name.c_str());
	if ( value == NULL ) {
		if(warn) {
	    	printf("Warning: using default value '%s' for '%s'\n", defaultvalue.c_str(), name.c_str());
		}
		return defaultvalue;
	}
	return value;
}

double Tools::getenv(const string &name, double defaultvalue) {
	const char* svalue = ::getenv(name.c_str());
	double value = defaultvalue;
	if ( svalue != NULL ) {
		Tools::convert2(svalue, value);
	} else {
		printf("Warning: using default value '%lf' for '%s'\n", defaultvalue, name.c_str());
	}
	return value;
}

void Tools::println(const string &message) {
	printf("%s\n", message.c_str());
}

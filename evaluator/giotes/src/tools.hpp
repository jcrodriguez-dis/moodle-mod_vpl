/**
 * Part of GIOTES for Virtual Programming Lab for Moodle
 * @Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
 * @License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

#pragma once

#include <cstdlib>
#include <cstdio>
#include <string>
#include <vector>
#include <fstream>
#include <sstream>
#include <algorithm>
#include <cctype>
#include <fcntl.h>
#include <unistd.h>

using namespace std;

/**
 * @brief Tools utility class
 * 
 */
class Tools {
public:
    static bool existFile(string name);
    static string readFile(string name);
    static vector<string> splitLines(const string &data);
    static int nextLine(const string &data);
    static bool removeInputLine(string &data, const string &inputLine);
    static string caseFormat(const string &text);
    static string caseFormatInline(const string &text);
    static string toLower(const string &text);
    static string normalizeTag(const string &text);
    static void sanitizeTag(string &text, const string& tag);
    static void sanitizeTags(string &text);
    static void sanitizePlaceholders(string &text);
    static void removeCRs(string &text);
    static void removeLastNL(string &s);
    static bool parseLine(const string &text, string &name, string &data);
    static string removeFirstSpace(const string &text);
    static string trimRight(const string &text);
    static string trim(const string &text);
    static string unescapeString(const string &text);
    static void replaceAll(string &text, const string &oldValue, const string &newValue);
    static void replaceAllML(string &text, const string &oldValue, const string &newValue);
    static void replaceAllIL(string &text, const string &oldValue, const string &newValue);
    static void fdblock(int fd, bool set);
    static bool convert2(const string& str, double &data);
    static bool convert2(const string& str, long int &data);
    static string int2str(const long int data, int width = 0);
    static string double2str(const double data, int precision = 2, int width = 0); 
    static string getenv(const string& name, const string& defaultvalue, bool warn = true);
    static double getenv(const string& name, double defaultvalue);
    static void println(const string &message);
};

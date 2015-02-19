<?php
// This file is part of VPL for Moodle - http://vpl.dis.ulpgc.es/
//
// VPL for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// VPL for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with VPL for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Syntaxhighlighter for M language (Octave)
 *
 * @package mod_vpl
 * @copyright 2012 Juan Carlos Rodríguez-del-Pino
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>
 */

require_once dirname(__FILE__).'/sh_base.class.php';

class vpl_sh_matlab extends vpl_sh_base{
    protected function show_pending(&$rest){
        if(array_key_exists($rest  , $this->reserved)){
            $this->initTag(self::c_reserved);
            parent::show_pending($rest);
            echo self::endTag;
        }elseif(array_key_exists($rest  , $this->functions)){
            $this->initTag(self::c_function);
            parent::show_pending($rest);
            echo self::endTag;
        }else{
            parent::show_pending($rest);
        }
    }
    const regular=0;
    const in_string=1;
    const in_macro=3;
    const in_comment=4;
    const in_linecomment=5;
    var $string_delimiter;
    var $functions;
    function __construct(){
        $this->reserved= array(//Source MATLAB Quick Reference Author: Jialong He
                //﻿Managing Commands and Functions
                "addpath" => true, "doc" => true, "docopt" => true, "genpath" => true, "help" => true, "helpbrowser" => true,
                "helpdesk" => true, "helpwin" => true, "lasterr" => true, "lastwarn" => true, "license" => true,
                "lookfor" => true, "partialpath" => true, "path" => true, "pathtool" => true, "profile" => true,
                "profreport" => true, "rehash" => true, "rmpath" => true, "support" => true, "type" => true, "ver" => true,
                "version" => true, "web" => true, "what" => true, "whatsnew" => true, "which" => true,
                //Managing Variables and the Workspace
                "clear" => true, "disp" => true, "length" => true, "load" => true, "memory" => true, "mlock" => true,
                "munlock" => true, "openvar" => true, "Open" => true, "pack" => true, "save" => true, "saveas" => true,
                "size" => true, "who" => true, "whos" => true, "workspace" => true,
                //﻿Starting and Quitting MATLAB
                "finish" => true, "exit" => true, "matlab" => true, "matlabrc" => true, "quit" => true, "startup" => true,
                //  as a Programming Language
                "builtin" => true, "eval" => true, "evalc" => true, "evalin" => true, "feval" => true,
                "function" => true, "global" => true, "nargchk" => true, "persistent" => true, "script" => true,
                //Control Flow
                "break" => true, "case" => true, "catch" => true, "continue" => true, "else" => true, "elseif" => true,
                "end" => true, "error" => true, "for" => true, "if" => true, "otherwise" => true, "return" => true,
                "switch" => true, "try" => true, "warning" => true, "while" => true,
                //Interactive Input
                "input" => true, "keyboard" => true, "menu" => true, "pause" => true,
                //﻿Object-Oriented Programming
                "class" => true, "double" => true, "inferiorto" => true, "inline" => true,
                "int8" => true, "int16" => true, "int32" => true, "isa" => true, "loadobj" => true,
                "saveobj" => true, "single" => true, "superiorto" => true, "uint8" => true,
                "uint16" => true, "uint32" => true,
                //Operator
                "kron" => true, "xor" => true, "and" => true
        );
        $this->functions= array(
                "kron" => true, "xor" => true, "all" => true, "any" => true, "exist" => true, "find" => true,
                "is*" => true, "isa" => true, "logical" => true, "mislocked" => true, "builtin" => true,
                "eval" => true, "evalc" => true, "evalin" => true, "feval" => true, "function" => true,
                "global" => true, "nargchk" => true, "persistent" => true, "script" => true, "break" => true,
                "case" => true, "catch" => true, "else" => true, "elsei" => true, "end" => true, "error" => true, "for" => true,
                "if" => true, "otherwise" => true, "return" => true, "switch" => true, "try" => true, "warning" => true,
                "while" => true, "input" => true, "keyboard" => true, "menu" => true, "pause" => true, "class" => true,
                "double" => true, "inferiorto" => true, "inline" => true, "int8" => true, "int16" => true,
                "int32" => true, "isa" => true, "loadobj" => true, "saveobj" => true, "single" => true,
                "superiorto" => true, "uint8" => true, "int16" => true, "uint32" => true, "dbclear" => true,
                "dbcont" => true, "dbdown" => true, "dbmex" => true, "dbquit" => true, "dbstack" => true,
                "dbstatus" => true, "dbstep" => true, "dbstop" => true, "dbtype" => true, "dbup" => true,
                "blkdiag" => true, "eye" => true, "linspace" => true, "logspace" => true, "ones" => true,
                "rand" => true, "randn" => true, "zeros" => true, "ans" => true, "computer" => true, "eps" => true,
                "flops" => true, "Inf" => true, "inputname" => true, "NaN" => true, "nargin" => true,
                "nargout" => true, "pi" => true, "realmax" => true, "realmin" => true, "varargin" => true,
                "varargout" => true, "calendar" => true, "clock" => true, "cputime" => true, "date" => true,
                "datenum" => true, "datestr" => true, "datevec" => true, "eomday" => true, "etime" => true, "now" => true,
                "tic" => true, "toc" => true, "weekday" => true, "cat" => true, "diag" => true, "fliplr" => true,
                "flipud" => true, "repmat" => true, "reshape" => true, "rot90" => true, "tril" => true, "triu" => true,
                "compan" => true, "gallery" => true, "hadamard" => true, "hankel" => true, "hilb" => true,
                "invhilb" => true, "magic" => true, "pascal" => true, "toeplitz" => true, "wilkinson" => true,
                "abs" => true, "acos" => true, "acosh" => true, "acot" => true, "acoth" => true, "acsc" => true, "acsch" => true,
                "angle" => true, "asec" => true, "asech" => true, "asin" => true, "asinh" => true, "atan" => true,
                "atanh" => true, "atan2" => true, "ceil" => true, "complex" => true, "conj" => true, "cos" => true,
                "cosh" => true, "cot" => true, "coth" => true, "csc" => true, "csch" => true, "exp" => true, "fix" => true,
                "floor" => true, "gcd" => true, "imag" => true, "lcm" => true, "log" => true, "log2" => true, "log10" => true,
                "mod" => true, "nchoosek" => true, "real" => true, "rem" => true, "round" => true, "sec" => true, "sech" => true,
                "sign" => true, "sin" => true, "sinh" => true, "sqrt" => true, "tan" => true, "tanh" => true, "airy" => true, "besselh" => true,
                "besseli" => true, "besselk" => true, "besselj" => true, "Bessely" => true, "beta" => true, "betainc" => true,
                "betaln" => true, "ellipj" => true, "ellipke" => true, "erf" => true, "erfc" => true, "erfcx" => true, "erfiny" => true,
                "expint" => true, "factorial" => true, "gamma" => true, "gammainc" => true, "gammaln" => true,
                "legendre" => true, "pow2" => true, "rat" => true, "rats" => true, "cart2pol" => true, "cart2sph" => true,
                "pol2cart" => true, "sph2cart" => true, "abs" => true, "eval" => true, "real" => true, "strings" => true,
                "deblank" => true, "findstr" => true, "lower" => true, "strcat" => true, "strcmp" => true, "strcmpi" => true,
                "strjust" => true, "strmatch" => true, "strncmp" => true, "strrep" => true, "strtok" => true, "strvcat" => true,
                "symvar" => true, "texlabel" => true, "upper" => true, "char" => true, "int2str" => true, "mat2str" => true,
                "num2str" => true, "sprintf" => true, "sscanf" => true, "str2double" => true, "str2num" => true, "bin2dec" => true,
                "dec2bin" => true, "dec2hex" => true, "hex2dec" => true, "hex2num" => true, "fclose" => true, "fopen" => true,
                "fread" => true, "fwrite" => true, "fgetl" => true, "fgets" => true, "fprintf" => true, "fscanf" => true,
                "feof" => true, "ferror" => true, "frewind" => true, "fseek" => true, "ftell" => true, "sprintf" => true,
                "sscanf" => true, "dlmread" => true, "dlmwrite" => true, "hdf" => true, "imfinfo" => true, "imread" => true, "imwrite" => true,
                "textread" => true, "wk1read" => true, "wk1write" => true, "bitand" => true, "bitcmp" => true, "bitor" => true, "bitmax" => true,
                "bitset" => true, "bitshift" => true, "bitget" => true, "bitxor" => true, "fieldnames" => true, "getfield" => true, "rmfield" => true,
                "setfield" => true, "struct Create" => true, "struct2cell" => true, "class" => true, "isa" => true, "cell" => true,
                "cellfun" => true, "cellstr" => true, "cell2struct" => true, "celldisp" => true, "cellplot" => true, "num2cell" => true,
                "cat" => true, "flipdim" => true, "ind2sub" => true, "ipermute" => true, "ndgrid" => true, "ndims" => true, "permute" => true,
                "reshape" => true, "shiftdim" => true, "squeeze" => true, "sub2ind" => true, "cond" => true, "condeig" => true, "det" => true,
                "norm" => true, "null" => true, "orth" => true, "rank" => true, "rcond" => true, "rref" => true, "rrefmovie" => true, "subspace" => true,
                "trace" => true, "chol" => true, "inv" => true, "lscov" => true, "lu" => true, "nnls" => true, "pinv" => true, "qr" => true, "balance" => true,
                "cdf2rdf" => true, "eig" => true, "gsvd" => true, "hess" => true, "poly" => true, "qz" => true, "rsf2csf" => true, "schur" => true,
                "svd" => true, "expm" => true, "funm" => true, "logm" => true, "sqrtm" => true, "qrdelete" => true, "qrinsert" => true, "bar" => true,
                "barh" => true, "hist" => true, "hold" => true, "loglog" => true, "pie" => true, "plot" => true, "polar" => true, "semilogx" => true,
                "semilogy" => true, "subplot" => true, "bar3" => true, "bar3h" => true, "comet3" => true, "cylinder" => true, "fill3" => true,
                "plot3" => true, "quiver3" => true, "slice" => true, "sphere" => true, "stem3" => true, "waterfall" => true, "clabel" => true,
                "datetick" => true, "grid" => true, "gtext" => true, "legend" => true, "plotyy" => true, "title" => true, "xlabel" => true,
                "ylabel" => true, "zlabel" => true, "contour" => true, "contourc" => true, "contourf" => true, "hidden" => true, "meshc" => true,
                "mesh" => true, "peaks" => true, "surf" => true, "surface" => true, "surfc" => true, "surfl" => true, "trimesh" => true, "trisurf" => true,
                "coneplot" => true, "contourslice" => true, "isocaps" => true, "isonormals" => true, "isosurface" => true,
                "reducepatch" => true, "reducevolume" => true, "shrinkfaces" => true, "smooth3" => true, "stream2" => true,
                "stream3" => true, "streamline" => true, "surf2patch" => true, "subvolume" => true, "griddata" => true, "meshgrid" => true,
                "area" => true, "box" => true, "comet" => true, "compass" => true, "errorbar" => true, "ezcontour" => true, "ezcontourf" => true,
                "ezmesh" => true, "ezmeshc" => true, "ezplot" => true, "ezplot3" => true, "ezpolar" => true, "ezsurf" => true, "ezsurfc" => true,
                "feather" => true, "fill" => true, "fplot" => true, "pareto" => true, "pie3" => true, "plotmatrix" => true, "pcolor" => true, "rose" => true,
                "quiver" => true, "ribbon" => true, "stairs" => true, "scatter" => true, "scatter3" => true, "stem" => true, "convhull" => true,
                "delaunay" => true, "dsearch" => true, "inpolygon" => true, "polyarea" => true, "tsearch" => true, "voronoi" => true,
                "camdolly" => true, "camlookat" => true, "camorbit" => true, "campan" => true, "campos" => true, "camproj" => true, "camroll" => true,
                "camtarget" => true, "camup" => true, "camva" => true, "camzoom" => true, "daspect" => true, "pbaspect" => true, "view" => true,
                "viewmtx" => true, "xlim" => true, "ylim" => true, "zlim" => true, "camlight" => true, "diffuse" => true, "lighting" => true,
                "lightingangle" => true, "material" => true, "specular" => true, "brighten" => true, "bwcontr" => true, "caxis" => true,
                "colorbar" => true, "colorcube" => true, "colordef" => true, "colormap" => true, "graymon" => true, "hsv2rgb" => true,
                "rgb2hsv" => true, "rgbplot" => true, "shading" => true, "spinmap" => true, "surfnorm" => true, "whitebg" => true, "autumn" => true,
                "bone" => true, "contrast" => true, "cool" => true, "copper" => true, "flag" => true, "gray" => true, "hot" => true, "hsv" => true, "jet" => true,
                "lines" => true, "prism" => true, "spring" => true, "summer" => true, "winter" => true, "orient" => true, "print" => true, "printopt" => true,
                "saveas" => true, "copyobj" => true, "findobj" => true, "gcbo" => true, "gco" => true, "get" => true, "rotate" => true, "ishandle" => true, "set" => true,
                "axes" => true, "figure" => true, "image" => true, "light" => true, "line" => true, "patch" => true, "rectangle" => true, "surface" => true,
                "text Create" => true, "uicontext Create" => true, "capture" => true, "clc" => true, "clf" => true, "clg" => true, "close" => true,
                "gcf" => true, "newplot" => true, "refresh" => true, "saveas" => true, "axis" => true, "cla" => true, "gca" => true, "propedit" => true,
                "reset" => true, "rotate3d" => true, "selectmoveresize" => true, "shg" => true, "ginput" => true, "zoom" => true, "dragrect" => true,
                "drawnow" => true, "rbbox" => true, "dialog" => true, "errordlg" => true, "helpdlg" => true, "inputdlg" => true, "listdlg" => true,
                "msgbox" => true, "pagedlg" => true, "printdlg" => true, "questdlg" => true, "uigetfile" => true, "uiputfile" => true,
                "uisetcolor" => true, "uisetfont" => true, "warndlg" => true, "menu" => true, "menuedit" => true, "uicontextmenu" => true,
                "uicontrol" => true, "uimenu" => true, "dragrect" => true, "findfigs" => true, "gcbo" => true, "rbbox" => true,
                "selectmoveresize" => true, "textwrap" => true, "uiresume" => true, "uiwait Used" => true, "waitbar" => true,
                "waitforbuttonpress" => true, "convhull" => true, "cumprod" => true, "cumsum" => true, "cumtrapz" => true, "delaunay" => true,
                "dsearch" => true, "factor" => true, "inpolygon" => true, "max" => true, "mean" => true, "median" => true, "min" => true, "perms" => true,
                "polyarea" => true, "primes" => true, "prod" => true, "sort" => true, "sortrows" => true, "std" => true, "sum" => true, "trapz" => true,
                "tsearch" => true, "var" => true, "voronoi" => true, "del2" => true, "diff" => true, "gradient" => true, "corrcoef" => true, "cov" => true,
                "conv" => true, "conv2" => true, "deconv" => true, "filter" => true, "filter2" => true, "abs" => true, "angle" => true, "cplxpair" => true,
                "fft" => true, "fft2" => true, "fftshift" => true, "ifft" => true, "ifft2" => true, "ifftn" => true, "ifftshift" => true,
                "nextpow2" => true, "unwrap" => true, "cross" => true, "intersect" => true, "ismember" => true, "setdiff" => true,
                "setxor" => true, "union" => true, "unique" => true, "conv" => true, "deconv" => true, "poly" => true, "polyder" => true,
                "polyeig" => true, "polyfit" => true, "polyval" => true, "polyvalm" => true, "residue" => true, "roots" => true,
                "griddata" => true, "interp1" => true, "interp2" => true, "interp3" => true, "interpft" => true, "interpn" => true,
                "meshgrid" => true, "ndgrid" => true, "spline" => true, "dblquad" => true, "fmin" => true, "fmin" => true, "fzero" => true,
                "ode45," => true, "ode113," => true, "ode15s," => true, "ode23s" => true, "ode23t," => true, "ode23tb" => true, "odefile" => true,
                "odeget" => true, "odeset" => true, "quad," => true, "vectorize" => true, "spdiags" => true, "speye" => true, "sprand" => true,
                "sprandn" => true, "sprandsym" => true, "find" => true, "full" => true, "sparse" => true, "spconvert" => true, "nnz" => true,
                "nonzeros" => true, "nzmax" => true, "spalloc" => true, "spfun" => true, "spones" => true, "colmmd" => true, "colperm" => true,
                "dmperm" => true, "randperm" => true, "symmmd" => true, "symrcm" => true, "condest" => true, "normest" => true, "bicg" => true,
                "bicgstab" => true, "cgs" => true, "cholinc" => true, "cholupdate" => true, "gmres" => true, "luinc" => true, "pcg" => true, "qmr" => true,
                "qr" => true, "qrdelete" => true, "qrinsert" => true, "qrupdate" => true, "eigs" => true, "svds" => true, "spparms" => true,
                "lin2mu" => true, "mu2lin" => true, "sound" => true, "soundsc" => true, "auread" => true, "auwrite" => true, "wavread" => true,
                "wavwrite" => true, "addpath" => true, "do" => true, "docopt" => true, "help" => true, "helpdesk" => true, "helpwin" => true,
                "lasterr" => true, "lastwarn" => true, "lookfor" => true, "partialpath" => true, "path" => true, "pathtool" => true,
                "profile" => true, "profreport" => true, "rmpath" => true, "type" => true, "ver" => true, "version" => true, "web" => true,
                "what" => true, "whatsnew" => true, "which" => true, "clear" => true, "disp" => true, "length" => true, "load" => true, "mlock" => true,
                "munlock" => true, "openvar" => true, "pack" => true, "save" => true, "saveas" => true, "size" => true, "who" => true, "whos" => true,
                "workspace" => true, "clc" => true, "echo" => true, "format" => true, "home" => true, "more" => true, "cd" => true, "copyfile" => true,
                "delete" => true, "diary" => true, "dir" => true, "edit" => true, "fileparts" => true, "fullfile" => true, "inmem" => true, "ls" => true,
                "matlabroot" => true, "mkdir" => true, "open" => true, "pwd" => true, "tempdir" => true, "tempname" => true, "matlabrc" => true,
                "quit" => true, "startup" => true
        );
        parent::__construct();
    }
    function show_line_number(){
        echo "\n";
        parent::show_line_number();
    }


    function print_file($filename, $filedata, $showln=true){
        $this->begin($filename,$showln);
        $state = self::regular;
        $pending='';
        $last_no_space = self::LF;
        $l = strlen($filedata);
        if($l){
            $this->show_line_number();
        }
        $current=self::LF;
        for($i=0;$i<$l;$i++){
            $previous=$current;
            $current=$filedata[$i];
            if($i < ($l-1)) {
                $next = $filedata[$i+1];
            }else{
                $next ='';
            }
            if($current == self::CR){
                if($next == self::LF) {
                    continue;
                }else{
                    $current = self::LF;
                }
            }
            if(!ctype_space($previous) || $previous==self::LF) {//Keep last char
                $last_no_space=$previous;
            }
            switch($state){
                case self::in_linecomment:
                    // Check end of comment
                    if($current==self::LF){
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $this->show_line_number();
                        $state=self::regular;
                    }else{
                        $pending .= $current;
                    }
                    break;
                case self::in_comment:
                    // Check end of comment
                    if($current==self::LF){
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $this->show_line_number();
                        $this->initTag(self::c_comment);
                    }elseif($current=='%' && $next == '}'){
                        $pending .='%}';
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $i++;
                        $state=self::regular;
                    }else{
                        $pending .= $current;
                    }
                    break;
                case self::in_string:
                    // Check end of string
                    if($current==$this->string_delimiter && $next==$this->string_delimiter){
                        $pending .= $current.$current;
                        $i++;
                    }elseif($this->string_delimiter == '"' && $current =='\\'){
                        $pending .= $current.$next;
                        $i++;
                    }elseif($this->string_delimiter == $current){
                        $last_no_space = $current;
                        $pending .= $current;
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $state = self::regular;
                    }elseif($current==self::LF){
                        $this->show_text($pending);
                        $pending='';
                        $this->endTag();
                        $this->show_line_number();
                        $this->initTag(self::c_string);
                    }else{
                        $pending .= $current;
                    }
                    break;
                case self::regular:
                    if($current == '%') {
                        if($next == '{'){
                            $state = self::in_comment;
                        }else{
                            $state = self::in_linecomment;
                        }
                        $this->show_pending($pending);
                        $this->initTag(self::c_comment);
                        $this->show_text('%');
                        continue 2;
                    }elseif($current == '"')    {
                        $state = self::in_string;
                        $this->string_delimiter = '"';
                        $this->show_pending($pending);
                        $this->initTag(self::c_string);
                        $this->show_text('"');
                        break;
                    }elseif($current == "'" &&
                            ($last_no_space == self::LF || strpos("[,;'(=",$last_no_space) !== false) )    {
                        $state = self::in_string;
                        $this->string_delimiter = "'";
                        $this->show_pending($pending);
                        $this->initTag(self::c_string);
                        $this->show_text("'");
                        break;
                    }
                    if(($current >= 'a' && $current <= 'z') ||
                    ($current >= 'A' && $current <= 'Z') ||
                    ($current >= '0' && $current <= '9') ||
                    $current=='_' || ord($current) > 127){
                        $pending .= $current;
                    } else {
                        //TODO check level without { }
                        $this->show_pending($pending);
                        if($current == self::LF){
                            $this->show_line_number();
                        }else{
                            $aux =$current;
                            $this->show_pending($aux);
                        }
                    }
            }
        }

        $this->show_pending($pending);
        if($state != self::regular){
            $this->endTag();
        }
        $this->end();
    }
}

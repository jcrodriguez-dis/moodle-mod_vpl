#!/bin/bash
# $Id: matlab_run.sh,v 1.10 2013-07-09 13:28:31 juanca Exp $
# Default Matlab/Octave language run script for VPL
# Copyright (C) 2011 Juan Carlos Rodríguez-del-Pino. All rights reserved.
# License GNU/GPL, see LICENSE.txt or http://www.gnu.org/licenses/gpl-2.0.html
# Author Juan Carlos Rodriguez-del-Pino

#//TODO add image_viewer ("eog %s");
#load common script and check programs
. common_script.sh

#create vpl_replot function
#Send last figure or figure number h to the applet 
if [ -f vpl_evaluate.sh ] ; then
	#if evaluating do not replot
	cat > vpl_replot.m <<END_MFILE
	function vpl_replot(h)
		% VPL_REPLOT in batch mode is disable
		n=vpl_replot_used(1);
		if( nargin == 0)
			h = gcf;
		end
	end
END_MFILE
else
	cat > vpl_replot.m <<END_MFILE
	function vpl_replot(h)
	% VPL_REPLOT show last figure in VPL console
	% VPL_REPLOT(h) show figure h in VPL console
		n=vpl_replot_used(1);
		if( nargin == 0)
			h = gcf;
		end
		print(h,'-dpng','vpl_img.png');
		%Send figure in base64 format to console
		printf('%cIMG',17);
		system("base64 vpl_img.png");
		printf('%c',17);
	end
END_MFILE
fi
#create vpl_replot function
#Set and chek if replot has been used 
cat > vpl_replot_used.m <<END_MFILE
function answer = vpl_replot_used(h)
%For internal VPL use
	persistent vpl_used;
	if isempty(vpl_used)
  		vpl_used = 0;
	end
	if(nargin != 0)
		vpl_used = h;
	end
	answer = vpl_used;
end
END_MFILE

#Replace soundsc function
cat > soundsc.m <<END_MFILE
function output = soundsc(y,Fs,bits,range)
	if (nargin == 1)
		Fs=8192;
	end
	if (nargin <= 2)
		bits=8;
	end
	if (nargin <= 3)
		range=[min(y(:)) max(y(:))];
	end
	center=(range(1)+range(2))/2;
	scale=(range(2)-range(1))/2;
	y = (y-center)/scale;
	if (nargout == 0)
		sound(y, Fs, bits);
	else
		output = y;
	end
end
END_MFILE

#Replace sound function
cat > sound.m <<END_MFILE
function sound(y,Fs,bits)
	if (nargin == 1)
		Fs=8192;
	end
	if (nargin <= 2)
		bits=8;
	end
	wavwrite(y', Fs, bits,'vpl_sound.wav');
	printf('%cSND',17);
	system("base64 vpl_sound.wav");
	printf('%c',17);	 
end
END_MFILE

#Replace playaudio function
cat > playaudio.m <<END_MFILE
function playaudio(x,ext)
	if (nargin == 2)
		x = loadaudio(x,ext);
	end
	wavwrite(x, 'vpl_sound.wav');
	printf('%cSND',17);
	system("base64 vpl_sound.wav");
	printf('%c',17);
end
END_MFILE

#Replace image funtions
cat > image.m <<END_MFILE
function image(IMG)
	imwrite(IMG, 'vpl_image.png');
	printf('%cIMG',17);
	system("base64 vpl_image.png");
	printf('%c',17);	 
end
END_MFILE

cat > imagesc.m <<END_MFILE
function imagesc(IMG)
	image(IMG);	 
end
END_MFILE

#End of vpl function
cat > vpl_quit.m <<END_MFILE
function vpl_quit
% VPL_QUIT quit for VPL
% If vpl_replot has not been used shows figures in the VPL console
	if (vpl_replot_used == 0 )
		%Get figures
		figurenumbers=findobj('type','figure');
		for i=1:length(figurenumbers)
			%print figure
			vpl_replot(figurenumbers(i));
		end
	end
	quit;
end
END_MFILE

#Add vpl_quit to the main m file
cat >> $VPL_SUBFILE0 <<END_MFILE

vpl_quit;
END_MFILE
PROPATH=$(command -v matlab 2>/dev/null)
if [ "$PROPATH" == "" ] ; then
	PROPATH=$(command -v octave 2>/dev/null)
	if [ "$PROPATH" == "" ] ; then
		echo "The jail-server need to install "Octave" or "Matlab" to run this type of program"
		exit 0;
	else
		cat common_script.sh > vpl_execution
		#workaround for bug in gnuplot (GNUTERM mus be dumb but )
		echo "export GNUTERM=pstex" >> vpl_execution
		#Workaround for bug in libgomp requiere to set the stack limit to 8M
		echo 'ulimit -s 8192' >> vpl_execution
		echo "octave --no-window-system -q $VPL_SUBFILE0 2>stderr.txt" >> vpl_execution
	fi
else
	PROGNAME=$(basename $VPL_SUBFILE0 .m)
	cat common_script.sh > vpl_execution
	echo "matlab -nosplash -r \"$PROGNAME\" 2>stderr.txt" >> vpl_execution
fi
echo "grep \"error:\" stderr.txt >/dev/null" >> vpl_execution
echo "if [ $? == 0 ] ; then" >> vpl_execution
echo "cat stderr.txt" >> vpl_execution
echo "fi" >> vpl_execution
echo "exit 0" >> vpl_execution
chmod +x vpl_execution

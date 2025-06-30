#!/bin/bash
# This file is part of VPL for Moodle
# Launcher for evaluator in GUI
# Copyright (C) 2025 Juan Carlos Rodríguez-del-Pino
# License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
# Author Juan Carlos Rodríguez-del-Pino <jcrodriguez@dis.ulpgc.es>


function vpl_vncaccel() {
    # Use old VNC acelerate directory
    local VNCACCELDIR=/etc/vncaccel
    [ -d "$VNCACCELDIR" ] && cp -a $VNCACCELDIR/.??* $HOME
}

function vpl_set_vnc_password() {
    # Create and set VNC password file
    VNCPASSWDFILE=$HOME/.vnc/passwd
    local VNCPASSWDSET="$VPL_VNCPASSWD\n$VPL_VNCPASSWD\nn\n"
    printf "$VNCPASSWDSET" | vncpasswd -f >$VNCPASSWDFILE
    [ ! -s $VNCPASSWDFILE ] && 	printf "$VNCPASSWDSET" | vncpasswd
    chmod 0600 $VNCPASSWDFILE
    echo "$SECONDS: Created VNC password file"
}

function vpl_set_XGEOMETRY_default() {
    # Set default XGEOMETRY
    [ "$VPL_XGEOMETRY" == "" ] && VPL_XGEOMETRY="800x600"
    echo "$SECONDS: VPL_XGEOMETRY => $VPL_XGEOMETRY"
}

function vpl_select_VNCPORT() {
    # Select unused VNCPORT
    local VNCPORTRANGE=10000
    local VNCPORTSEARCHLIMIT=100
    function VPL_PORTCHECK {
        lsof -i :\$VNCPORT &>/dev/null
        echo \$?
    }
    if [ -n "$(command -v nc)" ] ; then
        function VPL_PORTCHECK {
           nc -z 127.0.0.1 \$VNCPORT &>/dev/null
           echo \$? 
        }
    fi
    while true; do
        export VNCPORT=$((5900 + $RANDOM % $VNCPORTRANGE))
        [ "$(VPL_PORTCHECK)" != "0" ] && break
        ((limit++)) && ((limit==VNCPORTSEARCHLIMIT)) && break
    done
    export NDIS=$(($VNCPORT - 5900))
    echo "$SECONDS: VNCPORT => $VNCPORT"
}

function vpl_generate_cookie {
    for i in {1..8} ; do
        printf '%04X' $RANDOM
    done
}

function vpl_set_xauth {
    local COOKIE=$(mcookie)
    [ "$?" != "0" ] && COOKIE=$(vpl_generate_cookie)
    export XAUTHORITY=$HOME/.Xauthority
    touch $XAUTHORITY
    xauth add :$NDIS . $COOKIE
    [ $? != 0 ] && printf "add :$NDIS . $COOKIE\n" | xauth
    [ $? != 0 ] && printf "add :$NDIS . $COOKIE\nexit\n" | xauth
    echo "$SECONDS: Set xauth"
}

# Wait until a program ($1 e.g. execution_int) of the current user ends. 
function wait_end {
	local PSRESFILE
	PSRESFILE=.vpl_temp_search_program
	#wait start until 5s
	for I in 1 .. 5
	do
		sleep 1s
		ps -f -u $USER > $PSRESFILE
		grep $1 $PSRESFILE &> /dev/null
		if [ "$?" == "0" ] ; then
			break
		fi
	done
	while :
	do
		sleep 1s
		ps -f -u $USER > $PSRESFILE
		grep $1 $PSRESFILE &> /dev/null
		if [ "$?" != "0" ] ; then
			rm $PSRESFILE
			return
		fi
	done
}

function vpl_create_xresources_file {
    export XRESOURCES=$HOME/.Xresources
    cat >$XRESOURCES <<"END_OF_FILE"
Xft.dpi: 100
Xft.antialias: false
Xft.hinting: true
Xft.hintstyle: hintslight
Xft.rgba: rgb
session.screen0.workspaces: 1
END_OF_FILE
    echo "$SECONDS: Created resources file"
}

function vpl_create_xstartup_file {
    export XSTARTUPFILE=$HOME/.vnc/xstartup
    cat >$XSTARTUPFILE <<"END_OF_SCRIPT"
#!/bin/bash
export DISPLAY=:$NDIS
export TERM=xterm
unset SESSION_MANAGER
unset SESSION_MANAGER
unset DBUS_SESSION_BUS_ADDRESS

FONTPATHS=( '/usr/share/X11/fonts' '/usr/share/fonts/X11/' '/usr/lib/X11/fonts'
            '/usr/X11/lib/X11/fonts' '/usr/X11R6/lib/X11/fonts' '/usr/X11/lib/X11/fonts' )
FTYPES=( 'misc' '75dpi' '100dpi' 'Speedo' 'Type1' )
for FONTPATH in "${FONTPATHS[@]}" ; do
    if [ -d $FONTPATH ] ; then
        for FTYPE in "${FTYPES[@]}" ; do
            FONT=${FONTPATH}/${FTYPE}
            if [ -f "${FONT}/fonts.dir" ] ; then
                [ -z "${FONTS}" ] && FONTS="${FONT}"
                [ -n "${FONTS}" ] && FONTS="${FONTS},${FONT}"
            fi
        done
    fi
done

# Waits until X server is running
echo "$SECONDS: Waiting X start up"
if [ -x "$(command -v xmodmap)" ] ; then
    while true ; do
        echo "$SECONDS: Checking X with xmodmap"
        timeout 1 xmodmap &> /dev/null
        [ $? = 0 ] && break
        sleep 1
        ((nwait++))
        [ $nwait -gt 20 ] && break
    done
else
    echo "$SECONDS: Waiting 5 seconds"
    sleep 5
fi
echo "$SECONDS: X running"

# Configure X setting
[ -x "$(command -v xset)" ] && xset fp= $FONTS &> /dev/null
[ -x "$(command -v xrdb)" ] && xrdb -merge $HOME/.Xresources &> /dev/null
[ -x "$(command -v xsetroot)" ] && xsetroot -solid MidnightBlue &> /dev/null

echo "$SECONDS: X options set"

# Activate clipboard
[ -x "$(command -v vncconfig)" ] && vncconfig -iconic 2>/dev/null &
echo "$SECONDS: vncconfig running if available"
# Start window manager
if [ -x "$(command -v icewm)" ] ; then
    mkdir -p .icewm
    echo "Theme=SilverXP/default.theme" > .icewm/theme
    icewm &
elif [ -x "$(command -v fluxbox)" ] ; then
    fluxbox &
elif [ -x "$(command -v openbox)" ] ; then
    openbox &
elif [ -x "$(command -v metacity)" ] ; then
    metacity &
else
    [ -x "$(command -v xmessage)" ] && xmessage "Window Manager not found"
fi
echo "$SECONDS: window manager starting"

# Runs task
OUTPUTFILE=$HOME/.std_output
{
    # Run task
    chmod +x $HOME/vpl_evaluation_in_gui
    $HOME/vpl_evaluation_in_gui
} &> $OUTPUTFILE

# Shows task output stdout & stderr if any content
if [ -s $OUTPUTFILE ] ; then
    if [ -x "$(command -v xterm)" ] ; then
        xterm -T "std output" -bg white -fg red -e /bin/bash -c "more $OUTPUTFILE; sleep 3"
    elif [ -x "$(command -v x-terminal-emulator)" ] ; then
        x-terminal-emulator -e /bin/bash -c "more $OUTPUTFILE; sleep 3"
    else
        sleep 5s
    fi
else
    sleep 5s
fi

# Kill X server
ls $HOME/.vnc/*.pid &> /dev/null
[ $? != 0 ] && exit
PIDFILE=$(ls $HOME/.vnc/*.pid)
if [ -x "$(command -v tightvncserver)" ] ; then
    FILENAME=${PIDFILE##*/}
    TIGHTDIS=${FILENAME%.*}
    [ -n "$TIGHTDIS" ] && tightvncserver -kill $TIGHTDIS
fi

if [ -f $PIDFILE ] ; then 
    kill -SIGTERM $(cat $PIDFILE)
    [ $? = 0 ] && sleep 2
    [ -s $PIDFILE ] && kill -SIGKILL $(cat $PIDFILE)
fi
exit
END_OF_SCRIPT
    chmod 0755 $XSTARTUPFILE
    echo "$SECONDS: Created xstatup file"
}
mkdir -p $HOME/.vnc
{
    . vpl_environment.sh
    vpl_set_lang
    vpl_vncaccel
    vpl_set_vnc_password
    vpl_set_XGEOMETRY_default
    vpl_select_VNCPORT
    vpl_set_xauth
    vpl_create_xresources_file
    vpl_create_xstartup_file
} &>$HOME/.vnc/starting.log
echo "$SECONDS: Starting VNC server"
# Start VNC server

PIDFILE=$HOME/.vnc/vncserver.pid
if [ -x "$(command -v tightvncserver)" ] ; then
	echo "$SECONDS: Using tightvncserver"
	tightvncserver \
		-rfbport $VNCPORT \
		-geometry $VPL_XGEOMETRY \
		-localhost \
		-nevershared \
		-name vpl \
		:$NDIS &> $HOME/.vnc/vncserver.log &
elif [ -x "$(command -v Xvnc)" ] ; then
	Xvnc -version 2>&1 | grep -qi TigerVNC
	if [ $? = 0 ] ; then
		echo "$SECONDS: Using TigerVNC with Xvnc"
		$XSTARTUPFILE &
		{
			echo "rfbport=$VNCPORT"
			echo "geometry $VPL_XGEOMETRY"
			echo "localhost"
			echo "nevershared"
		} > $HOME/.vnc/config
		Xvnc \
			-rfbport=$VNCPORT \
			-nevershared \
			-localhost \
			-SecurityTypes=VncAuth \
			-PasswordFile=$VNCPASSWDFILE \
			-geometry $VPL_XGEOMETRY \
			-desktop vpl$NDIS \
			:$NDIS > $HOME/.vnc/vncserver.log &
		echo -n "$! $$" > $PIDFILE
	else
		echo "$SECONDS: Using Tightvnc with Xvnc"
		$XSTARTUPFILE &
		echo "Xvnc"
		{
			echo "rfbport=$VNCPORT"
			echo "geometry $VPL_XGEOMETRY"
			echo "localhost"
			echo "nevershared"
		} > $HOME/.vnc/config
		Xvnc \
			-rfbport=$VNCPORT \
			-nevershared \
			-localhost \
			-SecurityTypes=VncAuth \
			-geometry $VPL_XGEOMETRY \
			-name vpl$NDIS \
			:$NDIS > $HOME/.vnc/vncserver.log &
		echo -n "$! $$" > $PIDFILE
	fi
fi
echo "$SECONDS: VNC server started. Waiting for evaluation end"
wait_end vpl_evaluation_in_gui
echo "$SECONDS: Showing evaluation output"
cat $HOME/.std_output

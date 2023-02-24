FROM bitnami/moodle:4.1.1

COPY . /tmp/vpl

RUN head -n -5 /opt/bitnami/scripts/moodle/entrypoint.sh > tmp.txt && mv tmp.txt /opt/bitnami/scripts/moodle/entrypoint.sh
RUN echo -e '    info "Installing VPL"\n    cp -r /tmp/vpl /bitnami/moodle/mod\nls /bitnami/moodle/mod/vpl\n    info "** Moodle setup finished! **"\nfi\n\necho ""\nexec "$@"' >> /opt/bitnami/scripts/moodle/entrypoint.sh
RUN chmod +x /opt/bitnami/scripts/moodle/entrypoint.sh && chmod +x /opt/bitnami/scripts/moodle/run.sh

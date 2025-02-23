FROM moodlehq/moodle-php-apache:8.0 as main-recipe

LABEL org.label-schema.schema-version="0.0.1"
LABEL org.label-schema.name="example"
LABEL org.label-schema.vendor="Citricity Ltd"

ARG FILE_USER="www-data"
ARG FILE_GROUP="$FILE_USER"
ARG MAX_UPLOAD_FILESIZE=128M
ARG MAX_EXECUTION_TIME=30
ENV DEBUG=
ENV HOST="moodle-image-opt.test"
ENV WWWROOT="http://moodle-image-opt.test"
ENV PORT=80
ENV MOODLE_PATH='/var/www/html/moodle'
ENV MOODLE_TAG=v4.1.0
ENV MOODLE_DATA=/var/www/moodledata

RUN apt update && apt install --no-install-recommends -y vim iputils-ping postgresql-client

RUN \
    mkdir -p $MOODLE_DATA  && \
    chown -R $FILE_USER:$FILE_GROUP $MOODLE_DATA

# Create main virtualhost.
RUN \
    { \
        echo "<VirtualHost *:$PORT>"; \
        echo "  ServerName $HOST"; \
        echo "	DocumentRoot $MOODLE_PATH"; \
        echo "  ErrorLog \${APACHE_LOG_DIR}/error.log"; \
        echo "  CustomLog \${APACHE_LOG_DIR}/access.log combined"; \
        echo "	# Prevent access to vendor directory."; \
        echo "	<Directory $MOODLE_PATH/vendor>"; \
        echo "		Require all denied"; \
        echo "	</Directory>"; \
        echo "</VirtualHost>"; \
    } > /etc/apache2/sites-enabled/000-default.conf
RUN apachectl -t

# Create VirtualHost for behat.

# Set up PHP.ini file.
RUN \
	cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
	sed -i "s/zlib.output_compression = .*/zlib.output_compression = On/" /usr/local/etc/php/php.ini && \
	sed -i "s/max_execution_time = .*/max_execution_time = $MAX_EXECUTION_TIME/" /usr/local/etc/php/php.ini && \
	sed -i "s/post_max_size = .*/post_max_size = $MAX_UPLOAD_FILESIZE/" /usr/local/etc/php/php.ini && \
	sed -i "s/upload_max_filesize = .*/upload_max_filesize = $MAX_UPLOAD_FILESIZE/" /usr/local/etc/php/php.ini  && \
    sed -i "s/error_log = .*/log_errors = on/" /usr/local/etc/php/php.ini && \
    sed -i "s/error_log = .*/error_log = syslog/" /usr/local/etc/php/php.ini

# Install Moodle.
RUN rm -rf $MOODLE_PATH/*
RUN git clone https://github.com/moodle/moodle.git --branch $MOODLE_TAG --depth 1 $MOODLE_PATH
COPY assets/config.php $MOODLE_PATH/config.php

# Install behat config helper.
RUN git clone https://github.com/andrewnicols/moodle-browser-config.git --branch main --depth 1 $MOODLE_PATH/moodle-browser-config
COPY assets/moodle-browser-config/config.php $MOODLE_PATH/moodle-browser-config/config.php

# Install xdebug.
COPY assets/scripts/install-xdebug.sh /tmp/
RUN chmod u+x /tmp/install-xdebug.sh && /tmp/install-xdebug.sh
RUN rm /tmp/install-xdebug.sh

VOLUME $MOODLE_DATA

# Create behat data dir.

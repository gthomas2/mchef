FROM moodlehq/moodle-php-apache:{{ phpVersion }} as main-recipe

LABEL org.label-schema.schema-version="{{ version ?? '0.0.1' }}"
LABEL org.label-schema.name="{{ name ?? 'moodle-chef' }}"
LABEL org.label-schema.vendor="{{ vendor ?? 'Citricity Ltd' }}"

ARG FILE_USER="www-data"
ARG FILE_GROUP="$FILE_USER"
ARG MAX_UPLOAD_FILESIZE={{ maxUploadSize ?? '128M' }}
ARG MAX_EXECUTION_TIME={{ maxExecTime ?? 30 }}
ENV DEBUG={{ debug ?? false }}
ENV HOST="{{ host }}"
ENV WWWROOT="{{ wwwRoot }}"
ENV PORT={{ port }}
ENV MOODLE_PATH='/var/www/html/moodle'
ENV MOODLE_TAG={{ moodleTag ?? 'v4.0.1' }}
ENV MOODLE_DATA={{ moodleData ?? '/var/www/moodledata' }}
{% if (includeBehat) %}
ENV BEHAT_DATA="$MOODLE_DATA/behatdata"
ENV BEHAT_HOST="{{ behatHost }}"
ENV BEHAT_WWWROOT="{{ behatWwwRoot }}"
{% endif %}

{%if (developer) %}
RUN apt update && apt install --no-install-recommends -y vim iputils-ping postgresql-client
{% endif %}

RUN \
    mkdir -p $MOODLE_DATA  && \
    chown -R $FILE_USER:$FILE_GROUP $MOODLE_DATA

# Create main virtualhost.
RUN \
    { \
        echo "<VirtualHost *:$PORT>"; \
{% if (host) %}
        echo "  ServerName $HOST"; \
{% endif %}
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
{% if (includeBehat) %}
RUN \
	{ \
		echo "<VirtualHost *:$PORT>"; \
		echo "  ServerName $BEHAT_HOST"; \
		echo "	DocumentRoot $MOODLE_PATH"; \
        echo "	# Prevent access to vendor directory."; \
		echo "	<Directory $MOODLE_PATH/vendor>"; \
		echo "		Require all denied"; \
		echo "	</Directory>"; \
		echo "	ErrorLog \${APACHE_LOG_DIR}/behat_error.log"; \
		echo "	CustomLog \${APACHE_LOG_DIR}/behat_access.log combined"; \
		echo "</VirtualHost>"; \
	} > /etc/apache2/sites-enabled/cfz-behat.conf
{% endif %}

# Set up PHP.ini file.
RUN \
	cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini && \
	sed -i "s/zlib.output_compression = .*/zlib.output_compression = On/" /usr/local/etc/php/php.ini && \
	sed -i "s/max_execution_time = .*/max_execution_time = $MAX_EXECUTION_TIME/" /usr/local/etc/php/php.ini && \
	sed -i "s/post_max_size = .*/post_max_size = $MAX_UPLOAD_FILESIZE/" /usr/local/etc/php/php.ini && \
	sed -i "s/upload_max_filesize = .*/upload_max_filesize = $MAX_UPLOAD_FILESIZE/" /usr/local/etc/php/php.ini

# Install Moodle.
RUN rm -rf $MOODLE_PATH/*
RUN git clone https://github.com/moodle/moodle.git --branch $MOODLE_TAG --depth 1 $MOODLE_PATH
COPY assets/config.php $MOODLE_PATH/config.php

{% if (includeBehat) %}
# Install behat config helper.
RUN git clone https://github.com/andrewnicols/moodle-browser-config.git --branch main --depth 1 $MOODLE_PATH/moodle-browser-config
COPY assets/moodle-browser-config/config.php $MOODLE_PATH/moodle-browser-config/config.php
{% endif %}

VOLUME $MOODLE_DATA
{% for volume in volumes %}
VOLUME $MOODLE_PATH{{ volume.path }}
{% endfor %}

# Create behat data dir.
{% if (includeBehat) %}
RUN \
	mkdir -p $BEHAT_DATA  && \
	chown -R $FILE_USER:$FILE_GROUP $BEHAT_DATA
{% endif %}
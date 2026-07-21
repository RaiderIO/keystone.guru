ARG BASE_IMAGE=keystone.guru-app
FROM ${BASE_IMAGE}

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get -y install cron

# Copy hello-cron file to the cron.d directory
COPY etc/cron.d /etc/cron.d

# Give execution rights on the cron job
RUN chmod -R 0644 /etc/cron.d/

# Create the log file to be able to run tail. The scheduler job runs as ksg (see
# etc/cron.d/scheduler) and appends to this file, so it must be owned by that user.
RUN touch /var/log/cron.log && chown ksg:ksg /var/log/cron.log

# Run the command on container startup
CMD ["sh", "-c", "cron && tail -f /var/log/cron.log"]

FROM keystone.guru-app

ENV DEBIAN_FRONTEND=noninteractive

# procps provides pgrep, which the ECS container health check runs (`pgrep cron`). The lean
# runtime image (#3320) no longer carries procps transitively, so install it explicitly.
RUN apt-get update && apt-get -y install cron procps

# Copy hello-cron file to the cron.d directory
COPY etc/cron.d /etc/cron.d

# Give execution rights on the cron job
RUN chmod -R 0644 /etc/cron.d/

# Create the log file to be able to run tail
RUN touch /var/log/cron.log

# Run the command on container startup
CMD ["sh", "-c", "cron && tail -f /var/log/cron.log"]

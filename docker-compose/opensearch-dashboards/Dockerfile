FROM opensearchproject/opensearch-dashboards:latest

# Get rid of the default config
RUN rm /usr/share/opensearch-dashboards/config/opensearch_dashboards.yml

# Put a new one in its place (I know volumes exist but they never work for me)
COPY opensearch_dashboards.yml /usr/share/opensearch-dashboards/config/

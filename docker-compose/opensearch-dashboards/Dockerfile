FROM opensearchproject/opensearch-dashboards:2.13.0

USER root

# Get rid of the default config
RUN rm /usr/share/opensearch-dashboards/config/opensearch_dashboards.yml

# Put a new one in its place (I know volumes exist but they never work for me)
COPY opensearch_dashboards.yml /usr/share/opensearch-dashboards/config/

RUN chown opensearch-dashboards:opensearch-dashboards /usr/share/opensearch-dashboards/config/opensearch_dashboards.yml

USER opensearch-dashboards:opensearch-dashboards

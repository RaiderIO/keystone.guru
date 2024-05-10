from locust import HttpUser, task

class AboutPageUser(HttpUser):
    @task
    def hello_world(self):
        self.client.get("/about")

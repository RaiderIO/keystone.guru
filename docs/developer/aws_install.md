# WSL2 Shell
## Install AWS CLI v2 on Ubuntu 22.04
```bash
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
```
## Install unzip if not already installed
```bash
sudo apt install unzip
```

## Unzip the downloaded file
```bash
unzip awscliv2.zip
```

## Install the AWS CLI
```bash
sudo ./aws/install
```

```bash
/usr/local/bin/aws --version
```

Output:
`aws-cli/2.27.8 Python/3.13.2 Linux/5.15.167.4-microsoft-standard-WSL2 exe/x86_64.ubuntu.22`

# Configuring AWS CLI
https://raiderio.awsapps.com/start/#/?tab=accounts

This shows "Access Keys". Hit it, a prompt shows and in option 2 is what you should paste in `~/.aws/credentials`. These credentials are short lived (hours), so be ready to change them often
```
[868970774940_AdministratorAccess]
aws_access_key_id=ASIA4UUVzzzzzzzzzz
aws_secret_access_key=lrY2QHMOqTifitwWdNzzzzzzzzzzz
aws_session_token=IQoJb3JpZ2luX2VjEJ7//////////wEazzzzzzzzzzzzzz
```

Then, I also had this in `~/.aws/config`.
```
[default]
region = us-east-1
output = json

[profile 868970774940_AdministratorAccess]
region = us-east-1
output = json
```

# AWS CLI Login
```bash
export AWS_PROFILE=868970774940_AdministratorAccess
aws sts get-caller-identity
aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin 868970774940.dkr.ecr.us-east-1.amazonaws.com
```

# Building images
## Build the keystone.guru-app image
```bash
docker build -t keystone.guru-app:latest docker-compose/app/
```

## Build the keystone.guru-echo-server image
```bash
docker build -t keystone.guru-echo-server:latest docker-compose/laravel-echo-server/
```

# Tagging and pushing images
## ksg-echo-server repository
```bash
docker tag keystone.guru-echo-server:latest 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-echo-server:latest
docker push 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-echo-server:latest
```

## ksg-php-fpm repository
```bash
docker tag keystone.guru-app:latest 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-php-fpm:latest
docker push 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-php-fpm:latest
```

## ksg-swoole repository
For now uses the same image as `ksg-php-fpm`
```bash
docker tag keystone.guru-app:latest 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-swoole:latest
docker push 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-swoole:latest
```

# Redeploying the application on AWS
```bash
cdk deploy keystoneguru-services
```

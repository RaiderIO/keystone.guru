# 🚀 Deploying Keystone.guru to AWS

## 🔧 Install AWS CLI v2 on WSL2 (Ubuntu 22.04)

```bash
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
sudo apt install unzip
unzip awscliv2.zip
sudo ./aws/install
```

Verify installation:

```bash
/usr/local/bin/aws --version
```

Expected output:

```
aws-cli/2.x.x Python/3.x.x Linux/5.x WSL2
```

---

## 🔐 Configuring AWS CLI with Temporary Credentials

Visit:
[🔗 AWS SSO Portal](https://raiderio.awsapps.com/start/#/?tab=accounts)

1. Click **Access Keys**
2. Copy the **Option 2** output into `~/.aws/credentials`:

```ini
[868970774940_AdministratorAccess]
aws_access_key_id=ASIA4UUVzzzzzzzzzz
aws_secret_access_key=lrY2QHMOqTifitwWdNzzzzzzzzzzz
aws_session_token=IQoJb3JpZ2luX2VjEJ7//////////wEazzzzzzzzzzzzzz
```

Then in `~/.aws/config`:

```ini
[default]
region = us-east-1
output = json

[profile 868970774940_AdministratorAccess]
region = us-east-1
output = json
```

> ℹ️ These are **session-based credentials**. They expire periodically and must be refreshed from the AWS portal.

---

## 🔑 Authenticate with AWS CLI and ECR

```bash
export AWS_PROFILE=868970774940_AdministratorAccess

# Confirm identity
aws sts get-caller-identity

# Login to ECR
aws ecr get-login-password --region us-east-1 \
  | docker login --username AWS --password-stdin 868970774940.dkr.ecr.us-east-1.amazonaws.com
```

---

## 🛠️ Building Docker Images

```bash
cd ~/Git/private/keystone.guru
```

### Build `keystone.guru-app` image

```bash
docker build -t keystone.guru-app:latest docker-compose/app/
```

### Build `keystone.guru-echo-server` image

```bash
docker build -t keystone.guru-echo-server:latest docker-compose/laravel-echo-server/
```

---

## 📦 Tag & Push Docker Images to AWS ECR

### `ksg-echo-server`

```bash
docker tag keystone.guru-echo-server:latest 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-echo-server:latest
docker push 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-echo-server:latest
```

### `ksg-php-fpm`

```bash
docker tag keystone.guru-app:latest 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-php-fpm:latest
docker push 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-php-fpm:latest
```

### `ksg-swoole` (same image as `ksg-php-fpm` for now)

```bash
docker tag keystone.guru-app:latest 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-swoole:latest
docker push 868970774940.dkr.ecr.us-east-1.amazonaws.com/ksg-swoole:latest
```

---

## 🚀 Redeploy to AWS

```bash
cd ~/Git/private/keystoneguru-infra/cdk
cdk deploy keystoneguru-services
```

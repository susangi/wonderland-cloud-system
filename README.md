# Wonderland Cloud Platform

Cloud native theme park platform built for **SE6020 – Architecting Enterprise Cloud Solutions** (SLIIT MSc IT).

Visitors browse rides, book tickets for a date and time slot, pay with a mock payment service and get a digital ticket with a QR code. Administrators manage rides and ticket prices, record gate check-ins and ride usage and monitor park activity through an admin view and Metabase dashboards.

## Architecture in short

| Part | Technology |
|---|---|
| Microservices | 5 Laravel 12 apps (PHP 8.2): attraction, pricing, booking, payment, event |
| Database | MySQL 8.4 (Amazon RDS in the cloud), one schema per service |
| Frontend | React (Vite) visitor and admin portals, hosted on AWS Amplify |
| Auth | Amazon Cognito (`admins` group for administrators) |
| Entry point | Amazon API Gateway HTTP API with a Cognito JWT authorizer |
| Runtime | Docker images on Amazon EKS, ingress-nginx, HPA |
| Analytics | Metabase reading the event data |
| IaC and CI/CD | AWS CloudFormation, GitHub Actions with OIDC |

## Repository layout

```
services/            five Laravel microservices
frontend/            React visitor and admin portals
scripts/             local .bat scripts and the SQL file
docker-compose.yml   local MySQL
```

## Run locally (Windows)

Prerequisites: PHP 8.2, Composer 2, Docker Desktop, Node.js LTS. Only MySQL runs in Docker
(port **3307**, because XAMPP MySQL uses 3306). Details: `docs/05-local-development.md`.

1. One time setup (starts MySQL, installs packages, creates `.env` files, runs migrations and seeders):

   ```
   scripts\dev-setup.bat
   ```

2. Start everything (one window per service, the booking scheduler and the frontend):

   ```
   scripts\dev-start.bat
   ```

3. Optional sample data for the admin dashboard (marked `source = simulated`):

   ```
   scripts\dev-sample-data.bat
   ```

| Component | URL |
|---|---|
| attraction-service | http://127.0.0.1:8001 |
| pricing-service | http://127.0.0.1:8002 |
| booking-service | http://127.0.0.1:8003 |
| payment-service | http://127.0.0.1:8004 |
| event-service | http://127.0.0.1:8005 |
| frontend | http://localhost:5173 |

Each service has `/up` (probes) and `/api/health`. Run the tests of a service with `php artisan test` inside its folder.

Check the schemas: `docker exec -it wonderland-mysql mysql -uroot -p -e "SHOW DATABASES;"`.
To reset the local database completely: `docker compose down -v`, then `scripts\dev-setup.bat` again.

## Secrets

`.env` files are never committed. Each service has a `.env.example` with safe placeholder values.

## Cloud deployment (manual runbook)

The cloud setup was run manually from PowerShell. There are no cloud deploy scripts yet.
Use placeholders below and replace them with values from the deployed stacks or Secrets
Manager. Do not save secret values in this repository.

The repository does not keep the exact PowerShell history. The commands below show the
confirmed manual steps using the template parameters and resource names.
**TO CONFIRM:** exact command options that are not fixed by the files.

### 1. Prerequisites

Install AWS CLI, kubectl, Helm and PowerShell. Configure AWS CLI access for `ap-south-1`.
Before starting, check that the AWS Budget alert is active. The project used the
`wonderland-monthly` USD 30 budget with alerts at 85%, 100% and forecast.

```powershell
$Region = "ap-south-1"
$Project = "wonderland"
```

### 2. Deploy stacks 01 to 07

Deploy one stack at a time in this order. The stack names below follow the names in
`docs/03-deployment.md`. The CloudFormation templates do not set stack names.
**TO CONFIRM:** these are the exact stack names entered during the manual deployment.

```powershell
aws cloudformation deploy --stack-name wonderland-network --template-file .\infrastructure\01-network.yaml --region $Region --parameter-overrides "ProjectName=$Project"
aws cloudformation deploy --stack-name wonderland-security --template-file .\infrastructure\02-security.yaml --region $Region --parameter-overrides "ProjectName=$Project"
aws cloudformation deploy --stack-name wonderland-ecr --template-file .\infrastructure\03-ecr.yaml --region $Region --parameter-overrides "ProjectName=$Project"
aws cloudformation deploy --stack-name wonderland-database --template-file .\infrastructure\04-database.yaml --region $Region --parameter-overrides "ProjectName=$Project" "DbInstanceClass=db.t4g.micro" "MultiAz=false"
aws cloudformation deploy --stack-name wonderland-eks --template-file .\infrastructure\05-eks.yaml --region $Region --capabilities CAPABILITY_NAMED_IAM --parameter-overrides "ProjectName=$Project" "KubernetesVersion=1.36" "NodeInstanceType=c7i-flex.large"
aws cloudformation deploy --stack-name wonderland-cognito --template-file .\infrastructure\06-cognito.yaml --region $Region --parameter-overrides "ProjectName=$Project"
aws cloudformation deploy --stack-name wonderland-cicd --template-file .\infrastructure\07-cicd-iam.yaml --region $Region --capabilities CAPABILITY_NAMED_IAM --parameter-overrides "GitHubOwner=<GITHUB_OWNER>" "GitHubRepo=<GITHUB_REPO>" "ClusterName=wonderland-eks"
```

Replace the GitHub placeholders with the repository owner and repository name. The EKS
cluster name and node type match the deployed values.

### 3. Connect kubectl to EKS

```powershell
aws eks update-kubeconfig --name wonderland-eks --region $Region
```

### 4. Install ingress-nginx

The values file configures two controller replicas and an internal Network Load Balancer.
The chart version was not pinned in the repository.
**TO CONFIRM:** the exact Helm chart version used.

```powershell
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo update
helm upgrade --install ingress-nginx ingress-nginx/ingress-nginx --namespace ingress-nginx --create-namespace --values .\k8s\ingress-nginx-values.yaml
kubectl get service ingress-nginx-controller --namespace ingress-nginx --watch
```

Wait until the service shows the internal load balancer hostname. Use that hostname in
step 8.

### 5. Create the namespace and Kubernetes Secrets

The values come from the generated Secrets Manager secrets and the RDS master secret.
The table lists the secret names and keys referenced by the manifests. Do not put the
values in YAML files or commit them.

| Kubernetes Secret | Keys expected by the manifests |
|---|---|
| `db-master-secret` | `MASTER_USERNAME`, `MASTER_PASSWORD` |
| `wonderland-app-secrets` | `APP_KEY`, `INTERNAL_TOKEN` |
| `attraction-db-secret` | `DB_USERNAME`, `DB_PASSWORD` |
| `pricing-db-secret` | `DB_USERNAME`, `DB_PASSWORD` |
| `booking-db-secret` | `DB_USERNAME`, `DB_PASSWORD` |
| `payment-db-secret` | `DB_USERNAME`, `DB_PASSWORD` |
| `event-db-secret` | `DB_USERNAME`, `DB_PASSWORD` |
| `metabase-db-secret` | `MB_DB_USER`, `MB_DB_PASS` |
| `metabase-ro-secret` | `DB_USERNAME`, `DB_PASSWORD` |

Create the namespace and each secret with the same client dry run and apply method used
during deployment. Replace each quoted placeholder with the matching value from Secrets
Manager before running the command.

```powershell
kubectl create namespace wonderland --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic db-master-secret -n wonderland --from-literal=MASTER_USERNAME='<MASTER_USERNAME>' --from-literal=MASTER_PASSWORD='<MASTER_PASSWORD>' --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic wonderland-app-secrets -n wonderland --from-literal=APP_KEY='<APP_KEY>' --from-literal=INTERNAL_TOKEN='<INTERNAL_TOKEN>' --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic attraction-db-secret -n wonderland --from-literal=DB_USERNAME='<ATTRACTION_DB_USERNAME>' --from-literal=DB_PASSWORD='<ATTRACTION_DB_PASSWORD>' --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic pricing-db-secret -n wonderland --from-literal=DB_USERNAME='<PRICING_DB_USERNAME>' --from-literal=DB_PASSWORD='<PRICING_DB_PASSWORD>' --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic booking-db-secret -n wonderland --from-literal=DB_USERNAME='<BOOKING_DB_USERNAME>' --from-literal=DB_PASSWORD='<BOOKING_DB_PASSWORD>' --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic payment-db-secret -n wonderland --from-literal=DB_USERNAME='<PAYMENT_DB_USERNAME>' --from-literal=DB_PASSWORD='<PAYMENT_DB_PASSWORD>' --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic event-db-secret -n wonderland --from-literal=DB_USERNAME='<EVENT_DB_USERNAME>' --from-literal=DB_PASSWORD='<EVENT_DB_PASSWORD>' --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic metabase-db-secret -n wonderland --from-literal=MB_DB_USER='<METABASE_DB_USERNAME>' --from-literal=MB_DB_PASS='<METABASE_DB_PASSWORD>' --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic metabase-ro-secret -n wonderland --from-literal=DB_USERNAME='<METABASE_READONLY_USERNAME>' --from-literal=DB_PASSWORD='<METABASE_READONLY_PASSWORD>' --dry-run=client -o yaml | kubectl apply -f -
```

The CloudFormation secrets have generated names. Use the secret ARNs in the CloudFormation
outputs to find them. The Kubernetes key names above are the names expected by the
manifests. Stack 02 outputs the service, Metabase and application secret ARNs. Stack 04
outputs `DbMasterSecretArn`. The database secrets contain `username` and `password`
fields; map them to the Kubernetes keys shown above. The application key and internal
token values are mapped to `APP_KEY` and `INTERNAL_TOKEN`. Do not paste actual values
into a terminal transcript or screenshot.

### 6. Render the EKS manifests

The EKS overlay uses `__ECR_REGISTRY__`, `__IMAGE_TAG__` and `__DB_HOST__` tokens. Copy
`k8s/` to `build/k8s/` and replace these tokens in the copied files only. The image tag
used by CI and CD is the short commit SHA.

```powershell
New-Item -ItemType Directory -Force -Path .\build | Out-Null
Copy-Item -Path .\k8s -Destination .\build\k8s -Recurse -Force
$EcrRegistry = aws cloudformation describe-stacks --stack-name wonderland-ecr --region $Region --query "Stacks[0].Outputs[?OutputKey=='RegistryUri'].OutputValue | [0]" --output text
$ImageTag = git rev-parse --short=7 HEAD
$DbHost = aws cloudformation describe-stacks --stack-name wonderland-database --region $Region --query "Stacks[0].Outputs[?OutputKey=='DbEndpoint'].OutputValue | [0]" --output text
Get-ChildItem .\build\k8s -Recurse -File | ForEach-Object {
    $content = [System.IO.File]::ReadAllText($_.FullName)
    $content = $content.Replace("__ECR_REGISTRY__", $EcrRegistry)
    $content = $content.Replace("__IMAGE_TAG__", $ImageTag)
    $content = $content.Replace("__DB_HOST__", $DbHost)
    [System.IO.File]::WriteAllText($_.FullName, $content, [System.Text.UTF8Encoding]::new($false))
}
```

The images for the selected commit must already be in ECR.

### 7. Run database setup and apply the EKS overlay

Apply the bootstrap Job first. It creates the service schemas and database users. Then
apply the EKS overlay, which includes one migration Job per service.

```powershell
kubectl apply -f .\build\k8s\jobs\db-bootstrap-job.yaml
kubectl wait --for=condition=complete job/db-bootstrap --namespace wonderland "--timeout=<TIMEOUT>"
kubectl apply -k .\build\k8s\overlays\eks
kubectl wait --for=condition=complete job/attraction-migrate --namespace wonderland "--timeout=<TIMEOUT>"
kubectl wait --for=condition=complete job/pricing-migrate --namespace wonderland "--timeout=<TIMEOUT>"
kubectl wait --for=condition=complete job/booking-migrate --namespace wonderland "--timeout=<TIMEOUT>"
kubectl wait --for=condition=complete job/payment-migrate --namespace wonderland "--timeout=<TIMEOUT>"
kubectl wait --for=condition=complete job/event-migrate --namespace wonderland "--timeout=<TIMEOUT>"
kubectl get pods --namespace wonderland
```

**TO CONFIRM:** choose a wait timeout long enough for the database and migrations. Check
that all five migration Jobs completed and that the service pods are Running before
continuing.

### 8. Get the NLB listener ARN and deploy stack 08

Read the load balancer hostname from the ingress service. Use it to find the NLB ARN
and its port 80 listener ARN.

```powershell
$NlbDns = kubectl get service ingress-nginx-controller --namespace ingress-nginx -o jsonpath='{.status.loadBalancer.ingress[0].hostname}'
$NlbArn = aws elbv2 describe-load-balancers --region $Region --query "LoadBalancers[?DNSName=='$NlbDns'].LoadBalancerArn | [0]" --output text
$ListenerArn = aws elbv2 describe-listeners --load-balancer-arn $NlbArn --region $Region --query 'Listeners[?Port==`80`].ListenerArn | [0]' --output text
aws cloudformation deploy --stack-name wonderland-api --template-file .\infrastructure\08-api.yaml --region $Region --parameter-overrides "ProjectName=$Project" "NlbListenerArn=$ListenerArn" "AllowedOrigins=http://localhost:5173"
```

### 9. Deploy Amplify and update API CORS

Stack 09 needs a GitHub token so Amplify can read the repository. Use a token with the
required repository access. Do not put the token in this file or a committed file.

```powershell
aws cloudformation deploy --stack-name wonderland-frontend --template-file .\infrastructure\09-amplify.yaml --region $Region --parameter-overrides "ProjectName=$Project" "GitHubRepoUrl=<GITHUB_REPO_URL>" "GitHubToken=<GITHUB_TOKEN>"
$AmplifyUrl = aws cloudformation describe-stacks --stack-name wonderland-frontend --region $Region --query "Stacks[0].Outputs[?OutputKey=='AmplifyUrl'].OutputValue | [0]" --output text
aws cloudformation deploy --stack-name wonderland-api --template-file .\infrastructure\08-api.yaml --region $Region --parameter-overrides "ProjectName=$Project" "NlbListenerArn=$ListenerArn" "AllowedOrigins=http://localhost:5173,$AmplifyUrl"
```

After the API stack update, the browser CORS preflight returned 204 for the configured
origins.

### 10. Verify Kubernetes and API access

```powershell
kubectl get pods,hpa,pdb,cronjobs,ingress --namespace wonderland
$ApiUrl = aws cloudformation describe-stacks --stack-name wonderland-api --region $Region --query "Stacks[0].Outputs[?OutputKey=='ApiUrl'].OutputValue | [0]" --output text
curl.exe -i "$ApiUrl/api/attractions"
curl.exe -i "$ApiUrl/api/bookings/me"
```

The API checks returned 200 with attraction data and 401 for `/api/bookings/me` without
a token.

### 11. Open Metabase

Metabase is a ClusterIP service. Run port-forward in a separate PowerShell window then
open `http://localhost:8088`. Windows reserves some ports near 3000 so the local port
8088 was used.

```powershell
kubectl port-forward --namespace wonderland service/metabase 8088:3000
```

### 12. Tear down

Remove ingress-nginx first and wait for its internal NLB to be deleted. Then delete the
stacks in this order. Wait for each stack to finish deleting before starting the next.
Keep stack 06 (`wonderland-cognito`).

```powershell
helm uninstall ingress-nginx --namespace ingress-nginx
aws cloudformation delete-stack --stack-name wonderland-frontend --region $Region
aws cloudformation wait stack-delete-complete --stack-name wonderland-frontend --region $Region
aws cloudformation delete-stack --stack-name wonderland-api --region $Region
aws cloudformation wait stack-delete-complete --stack-name wonderland-api --region $Region
aws cloudformation delete-stack --stack-name wonderland-cicd --region $Region
aws cloudformation wait stack-delete-complete --stack-name wonderland-cicd --region $Region
aws cloudformation delete-stack --stack-name wonderland-eks --region $Region
aws cloudformation wait stack-delete-complete --stack-name wonderland-eks --region $Region
aws cloudformation delete-stack --stack-name wonderland-database --region $Region
aws cloudformation wait stack-delete-complete --stack-name wonderland-database --region $Region
aws cloudformation delete-stack --stack-name wonderland-ecr --region $Region
aws cloudformation wait stack-delete-complete --stack-name wonderland-ecr --region $Region
aws cloudformation delete-stack --stack-name wonderland-security --region $Region
aws cloudformation wait stack-delete-complete --stack-name wonderland-security --region $Region
aws cloudformation delete-stack --stack-name wonderland-network --region $Region
aws cloudformation wait stack-delete-complete --stack-name wonderland-network --region $Region
```

If deleting stack 03 fails because an ECR repository is not empty, remove its images in
ECR and retry the stack deletion. Check that no project NLB, NAT gateway or EBS volume
remains. Stack 06 is kept and can be deleted separately when it is no longer needed.

These steps are manual. Deploy scripts are future work.

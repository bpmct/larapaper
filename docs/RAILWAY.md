# Deploying LaraPaper on Railway

[Railway](https://railway.com) is a managed hosting platform that can run LaraPaper from its Dockerfile with a PostgreSQL add-on. No local Docker knowledge needed.

## Prerequisites

- A [Railway](https://railway.com) account
- A fork of this repo (or direct access to push to your own copy)
- The [Railway CLI](https://docs.railway.com/guides/cli) (optional, for variable management)

## 1. Create the Project

1. Go to [railway.com/new](https://railway.com/new) and choose **Deploy from GitHub repo**
2. Select your fork of `larapaper`
3. Railway will detect the `Dockerfile` automatically and start a build

## 2. Add PostgreSQL

LaraPaper works with SQLite out of the box, but Railway's filesystem is ephemeral — a database resets on every deploy. Use Railway's managed Postgres instead:

1. In your project dashboard, click **+ New** → **Database** → **PostgreSQL**
2. Once provisioned, click the Postgres service → **Variables** tab and copy `DATABASE_URL`

> **Why not SQLite on Railway?** Railway volumes exist but require manual setup and a paid plan. Postgres is simpler and free on the Hobby plan.

## 3. Configure Networking

Railway needs to know which port your container listens on. The LaraPaper image defaults to `8080`, but Railway routes public traffic to `80`:

1. Go to your `larapaper` service → **Settings** → **Networking**
2. Under **Public Networking**, set **Port** to `80`

Or set these environment variables (Railway will detect `PORT` automatically in future deploys):

```
NGINX_HTTP_PORT=80
PORT=80
```

## 4. Set Environment Variables

In your service → **Variables** tab, set the following:

### Required

| Variable | Value | Notes |
|----------|-------|-------|
| `APP_KEY` | `base64:...` | Generate with `echo "base64:$(openssl rand -base64 32)"` |
| `APP_URL` | `https://your-app.up.railway.app` | Your Railway public URL (with `https://`) |
| `APP_ENV` | `production` | |
| `DB_CONNECTION` | `pgsql` | |
| `DB_URL` | *(paste DATABASE_URL from Postgres service)* | Railway injects this automatically if you link the services |
| `NGINX_HTTP_PORT` | `80` | Matches Railway's default routing port |
| `PORT` | `80` | Tells Railway's healthcheck which port to probe |

### Required for HTTPS (Railway terminates SSL)

| Variable | Value | Notes |
|----------|-------|-------|
| `FORCE_HTTPS` | `1` | Forces Laravel to generate `https://` URLs |
| `TRUSTED_PROXIES` | `*` | Trusts Railway's load balancer to forward the correct scheme |

> Without `FORCE_HTTPS=1` and `TRUSTED_PROXIES=*`, CSS/JS assets will load over `http://` and be blocked by the browser as mixed content.

### Optional but recommended

| Variable | Value | Notes |
|----------|-------|-------|
| `PHP_OPCACHE_ENABLE` | `1` | Improves PHP performance |
| `REGISTRATION_ENABLED` | `0` | Disable open registration once your account is created |

## 5. Link PostgreSQL (auto-inject)

Instead of manually copying `DATABASE_URL`, you can link the services:

1. Go to your `larapaper` service → **Variables**
2. Click **+ Reference Variable** → select the Postgres service → choose `DATABASE_URL`
3. Set the reference name to `DB_URL`

Railway will inject the correct connection string automatically, even when the database is restarted.

## 6. Deploy

Push to your branch (or trigger a manual deploy from the Railway dashboard). Railway builds from the `Dockerfile` and runs migrations automatically via the image's `AUTORUN_ENABLED=true` entrypoint.

Watch the build logs — a successful deploy ends with:

```
✅ Database connection successful
✅ NGINX + PHP-FPM is running correctly.
```

## 7. First Login

Navigate to your Railway public URL. Register an account — the first registered user becomes the admin.

To disable registration afterward:

```
REGISTRATION_ENABLED=0
```

## Updating

Railway automatically redeploys when you push to the linked branch. To update a production deployment manually:

1. Pull the latest upstream changes into your fork
2. Push to your Railway-linked branch

## Troubleshooting

### 502 Bad Gateway

The most common cause is a port mismatch. Verify:
- `NGINX_HTTP_PORT=80` is set
- The service's **Networking** port is set to `80` in the Railway dashboard

### Assets load unstyled (mixed content errors)

Verify both `FORCE_HTTPS=1` and `TRUSTED_PROXIES=*` are set. Without these, Laravel generates `http://` URLs for assets even though the page is served over `https://`.

### Database connection failed

Check that `DB_CONNECTION=pgsql` and `DB_URL` points to the Railway internal Postgres URL (`postgres.railway.internal`). The public URL won't work from inside the Railway network.

### Migrations didn't run

The image runs `php artisan migrate --force` on startup automatically. Check deploy logs for migration output. If they failed (e.g. bad DB credentials), fix the env vars and redeploy.

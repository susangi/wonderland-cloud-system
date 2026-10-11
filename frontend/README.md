# Wonderland frontend

Visitor and admin portals (React + Vite, plain CSS from the Wonderland design system in `../design`).

## Run locally

```
npm install
copy .env.example .env.local
npm run dev
```

Open http://localhost:5173. The five services must be running on ports 8001–8005 (`scripts\dev-start.bat`).
The Vite dev server proxies `/api/...` to the right service, like API Gateway does in the cloud (`vite.config.js`).

## Login modes (only env changes, no code changes)

| `VITE_AUTH_MODE` | Login page | API calls send |
|---|---|---|
| `local` | "Continue as demo visitor / admin" | `X-Local-User`, `X-Local-Groups` |
| `cognito` | Amplify Authenticator (sign up, verify code, log in, forgot password) | `Authorization: Bearer <access token>` |

For `cognito` also set `VITE_USER_POOL_ID` and `VITE_USER_POOL_CLIENT_ID`. In Amplify Hosting set `VITE_API_BASE_URL` to the API Gateway URL.

## Structure

```
src/api/         one file per service + client.js (the only place that calls fetch)
src/auth/        AuthContext, local and Cognito login, route guards
src/components/  design system components (TopNav, Ticket, Scanner, ChartCard ...)
src/pages/       visitor pages, booking steps, admin pages
src/utils/       config (env), format (money, dates in Asia/Colombo), status labels
src/styles/      tokens.css + wonderland.css (copied from ../design) + app.css (page layout)
```

## Build

```
npm run build
```

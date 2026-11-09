# Enable Google account sign-in

The integration is installed, migrated and tested. It stays disabled until real Google OAuth credentials are added. Do not put a Client Secret into frontend code or admin business settings.

## 1. Create a Google OAuth client

1. Open https://console.cloud.google.com/ and select or create a project for Arsh Perfume.
2. Open **Google Auth Platform**. Configure **Branding** (application name, support email and developer contact). Configure **Audience** for the intended users. For an external app in testing, add the Google accounts you will use as test users.
3. Open **Clients**, choose **Create client**, and select **Web application**.
4. Add an **Authorized redirect URI** matching the address you actually use to open the store, including its port and the complete callback path.

For the current preview server:

```text
http://127.0.0.1:8012/auth/google/callback
```

If you run the default development server and open the store at localhost:8000 instead:

```text
http://localhost:8000/auth/google/callback
```

For production, use your actual HTTPS domain, for example `https://your-domain.com/auth/google/callback`. Do not enter the example domain as your real callback. The address, scheme and port must match exactly; starting on `localhost` and returning to `127.0.0.1` loses the browser session used to verify sign-in.

5. Create the client and save its **Client ID** and **Client Secret** in the server's `.env` file. You do not need to share the secret in chat. Only the standard `openid`, `profile` and `email` scopes are requested.

## 2. Configure the application

The blank `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` entries have already been added to this workspace's `.env`. Fill them with your real Web application client values and add the redirect override appropriate for your host:

```dotenv
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8012/auth/google/callback
```

If `GOOGLE_REDIRECT_URI` is unset, the application defaults to `APP_URL` followed by `/auth/google/callback`. Do not set it to an empty string. For production, use the HTTPS callback registered above.

After saving:

```powershell
php artisan config:clear
```

Restart the development server if necessary. On deployments that use cached configuration, rebuild it with `php artisan config:cache` after setting the credentials.

**Admin > Settings** shows whether Google sign-in is configured and the configured callback URL. Credentials are not displayed there. The Continue with Google button enables automatically once configuration is complete.

## 3. Test sign-in

- New customers: select **Continue with Google** on Sign in or Create account, choose a configured test account, and grant consent. The store creates a customer account, refreshes the session, and preserves the guest cart and intended checkout page.
- Existing email/password customers: choose **Continue with Google** using the same verified email address. The existing account is connected and signed in automatically, preserving its password, orders, addresses and role. Matching is case-insensitive. Future sign-ins use the saved Google account identifier. My Account also retains the optional password-confirmed connection flow.
- Google-only customers can optionally set an email password from **My Account > Add email sign-in**, or use the email password-reset flow.
- Existing administrators can also sign in with their matching verified Google email; their existing administrator role is preserved. Public registration for a new Google identity always creates a customer and never grants admin access. Disabled accounts remain blocked, and a matching email cannot replace a different Google identity that is already connected.
- Cancelled, expired or mismatched-state attempts return a useful error without signing the customer in. Disabled accounts remain blocked.

Publish/configure the OAuth app's audience appropriately before opening Google sign-in to all customers. Google may require additional branding or domain verification depending on the app configuration. Keep the existing email sign-in available during setup.

## Validation already completed

The production frontend builds; the users migration is applied. Automated tests exercise the real Socialite state verification and profile mapping with mocked Google token/userinfo responses. Tests cover new and linked accounts, disabled accounts, unverified emails, existing-email collisions, explicit linking, role protection, password compatibility, session regeneration and guest-cart continuity. Browser checks cover login/registration layouts at 375px and 1440px.

A real Google consent round trip still needs to be tested after your OAuth client credentials are configured.

Official references:
- Google web-server OAuth setup: https://developers.google.com/identity/protocols/oauth2/web-server#creatingcred
- Google OpenID Connect: https://developers.google.com/identity/openid-connect/openid-connect
- Google button branding: https://developers.google.com/identity/branding-guidelines
- Laravel Socialite: https://laravel.com/docs/12.x/socialite

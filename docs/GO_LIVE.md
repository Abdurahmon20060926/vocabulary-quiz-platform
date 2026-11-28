# Go Live

1. Create an Ubuntu VPS, point your domain's A record to its public IP, and allow inbound TCP 22, 80, and 443 in the provider firewall.
2. Install Docker Engine and the Compose plugin from Docker's official Ubuntu apt repository. Create a non-root sudo user and add it to the `docker` group; reconnect after adding it.
3. Connect as that user and run:

   ```bash
   git clone https://github.com/Abdurahmon20060926/vocabulary-quiz-platform.git
   cd vocabulary-quiz-platform
   cp .env.production.example .env.production
   nano .env.production
   ```

4. Set `APP_DOMAIN` to your domain without `https://`. Set the Google credentials and make `APP_URL` and `GOOGLE_REDIRECT_URI` use that same domain. Leave `APP_KEY` empty and `DB_PASSWORD=CHANGE_ME`; the deploy script generates them safely.
5. In Google Cloud Console, configure the OAuth consent screen for an External audience and publish it to Production so any Google account can sign in. While it remains in Testing mode, only listed test users can sign in. Create a Web application OAuth client with:

   - Authorized JavaScript origin: `https://DOMAIN`
   - Authorized redirect URI: `https://DOMAIN/auth/google/callback`

   Replace `DOMAIN` with the exact hostname used for `APP_DOMAIN`.
6. Deploy. The demo seeder is idempotent and will be run automatically after migrations:

   ```bash
   chmod +x scripts/deploy.sh
   ./scripts/deploy.sh --seed
   ```

7. Verify `https://DOMAIN` opens with a trusted padlock and that HTTP redirects to HTTPS. Test guest play, Google sign-in, and creating, publishing, and playing a quiz.
8. For later updates, run:

   ```bash
   ./scripts/deploy.sh --pull
   ```

   View services with `docker compose -f docker-compose.prod.yml --env-file .env.production ps` and logs with `docker compose -f docker-compose.prod.yml --env-file .env.production logs -f`.
9. Keep `.env.production` private and never run `docker compose -f docker-compose.prod.yml down -v` on production.

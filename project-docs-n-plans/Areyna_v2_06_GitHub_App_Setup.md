# Areyna v2 — 06: Setting up the Areyna GitHub App

> **Who this is for:** the platform owner, once per environment (local, staging, production).
> **What it unlocks:** learners connecting GitHub, linking the repository they create from a project template, and submitting milestones that are checked by tests on GitHub (design doc v2-01).
> **Time:** about 15 minutes.

Everything below happens on github.com except section 4 (your `.env`) and section 6 (Areyna's admin panel).

---

## 1. Create the App

1. On GitHub, open **Settings → Developer settings → GitHub Apps → New GitHub App**.
   (To own the App with an organisation instead of your personal account, open the organisation's **Settings → Developer settings → GitHub Apps**.)
2. Fill in:

| Field | Value |
|---|---|
| **GitHub App name** | `Areyna` for production. App names are unique across GitHub, so for local use something like `Areyna Dev <your-name>`. |
| **Homepage URL** | Your Areyna address, e.g. `https://areyna.example.com` (local: `http://127.0.0.1:8000`). |
| **Callback URL** | `https://<your-areyna-host>/github/callback` (local: `http://127.0.0.1:8000/github/callback`). |
| **Expire user authorization tokens** | Leave ticked. Areyna uses the sign-in only to learn the learner's GitHub ID and then discards the token. |
| **Request user authorization (OAuth) during installation** | Leave **unticked**. "Connect GitHub" in Areyna does this separately. |
| **Setup URL** | `https://<your-areyna-host>/learn`, with **Redirect on update** ticked, so learners come back to Areyna after installing. |
| **Webhook → Active** | Ticked. |
| **Webhook URL** | `https://<your-areyna-host>/github/webhook`. For local development see section 5. |
| **Webhook secret** | A long random string. Generate one with `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` and keep it for section 4. |

3. **Permissions → Repository permissions:**

| Permission | Access | Why |
|---|---|---|
| Actions | Read-only | Find the test run for a commit and download its report |
| Checks | Read-only | Read run status |
| Contents | Read and write | Read the learner's code; *write* is used later for teammate pull requests (Work Experience) |
| Metadata | Read-only | Required by GitHub |
| Pull requests | Read and write | Work Experience: open teammate PRs and post the AI review |

   **Account permissions:** none.

4. **Subscribe to events:** tick **Pull request**, **Pull request review**, **Push** and **Workflow run**. (GitHub always sends installation events to Apps; there is no box for them.)
5. **Where can this GitHub App be installed?** → **Any account** (learners install it on their own accounts).
6. Click **Create GitHub App**.

## 2. Collect the App's credentials

On the App's settings page after creating it:

1. Note the **App ID** and the **Client ID** (top of the page).
2. Under **Client secrets**, click **Generate a new client secret** and copy it now (GitHub shows it once).
3. Under **Private keys**, click **Generate a private key**. A `.pem` file downloads.
4. Note the App's **public link** slug: it's the last part of `https://github.com/apps/<slug>`.

## 3. Put the private key in place

Move the downloaded file to `storage/app/github-app.pem` in the Areyna project:

```bash
mv ~/Downloads/<your-app>.*.private-key.pem storage/app/github-app.pem
chmod 600 storage/app/github-app.pem
```

`storage/app/` is git-ignored, so the key can't be committed by accident. **Never paste it into chat, an issue or a commit.** If it ever leaks, delete it on the App page and generate a new one.

## 4. Configure Areyna

Add to `.env` (the names are already listed in `.env.example`):

```dotenv
GITHUB_APP_ID=123456
GITHUB_APP_SLUG=areyna-dev-yourname
GITHUB_APP_CLIENT_ID=Iv23li...
GITHUB_APP_CLIENT_SECRET=...
GITHUB_APP_PRIVATE_KEY_PATH=storage/app/github-app.pem
GITHUB_WEBHOOK_SECRET=<the random string from section 1>
GITHUB_TEMPLATES_ORG=areyna-templates
```

Then run `php artisan config:clear`.

## 5. Webhooks during local development

GitHub can't reach `127.0.0.1`. You have two options:

- **No webhooks (simplest).** Areyna works without them. The **Check again** button links the repository, and the milestone page asks GitHub for test results each time it loads (it reloads itself while tests run). A scheduled sweep also picks up waiting submissions: run `php artisan schedule:work` in a terminal.
- **Forward webhooks.** Create a channel at <https://smee.io>, set it as the App's Webhook URL, and run:

  ```bash
  npx smee-client --url https://smee.io/<your-channel> --target http://127.0.0.1:8000/github/webhook
  ```

In production, make sure the scheduler runs (`* * * * * php /path/to/artisan schedule:run`). It also handles `submissions:resume-pending` every five minutes and `guide:check-resources` weekly.

## 6. Switch it on in Areyna

In **Admin → Feature Flags**, enable:

- `sourcecontrol.github`: Connect GitHub, linking, webhooks, milestone submissions;
- `tracks.build`: Build projects in the catalogue;
- `scenario.scripted_events`: story messages during projects.

Leave `tracks.work_experience`, `sourcecontrol.pr_review_comments` and `hosting.publish` off until those phases ship.

**Note:** with no Build project content yet, the Build section of the catalogue stays empty. The first Build project (Taskly) arrives in phase v2-2 together with its template repositories.

## 7. The templates organisation (needed from v2-2)

1. Create a free GitHub organisation, e.g. `areyna-templates` (**+ → New organization**).
2. Each project stack gets a **public** starter repository (e.g. `areyna-templates/taskly-express-ejs`). In its **Settings**, tick **Template repository**.
3. The starter contains `.github/workflows/areyna.yml`, which runs the acceptance tests and uploads `areyna-report.json`. Record its fingerprint on the stack variant so Areyna can tell when a learner has edited it:

   ```bash
   sha256sum .github/workflows/areyna.yml
   ```

   Put the hash in the variant's `workflow_sha256` (the seeder does this).
4. Private reference builds and the acceptance suite live in the same organisation (doc 04).

## 8. Check it works

1. As a learner, open a Build project, choose a stack, then on the build page click **Connect GitHub**. You should return with "GitHub connected."
2. Click **Create my repo**, create it on GitHub, then **Give Areyna access** and choose only that repository.
3. Back on the build page the repository shows as linked (or click **Check again**).
4. Push a commit, open the current milestone, choose the commit, explain it and submit. The attempt shows **Tests running…** until the workflow finishes, then the review.

If something doesn't work, check `storage/logs/laravel.log`. GitHub problems are logged as warnings that start with "GitHub".

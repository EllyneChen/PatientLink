# PatientLink — Patient Profile Module (FR-P06)

This package implements the first CRUD module: the Patient can **view** and
**update** their own profile (demographic + contact info). It drops into the
`patientlink` Laravel project from the Sprint 3 Step 1 skeleton.

## What's in here

```
app/Http/Controllers/Api/PatientController.php   ← new
routes/api.php                                    ← replaces the existing one
public/patient/login.html                         ← new
public/patient/profile.html                       ← new
public/patient/css/patient.css                    ← new
public/patient/js/api.js                          ← new (shared fetch helper)
public/patient/js/login.js                        ← new
public/patient/js/profile.js                      ← new
```

## 1. Copy the files in

Copy each file into the same relative path inside your `patientlink/`
project, overwriting `routes/api.php` (it's the same file as before with
the `PatientController` import and two new routes added — nothing else
changed, so it's safe to replace).

## 2. What the backend does

`PatientController` adds two endpoints, both scoped to **the logged-in
patient only** (no `{id}` in the URL — RBAC + "look up via the JWT" means a
patient literally cannot view or edit anyone else's record):

| Method | Endpoint              | Purpose                                  |
|--------|------------------------|-------------------------------------------|
| GET    | `/api/patient/profile` | Return the patient's own profile          |
| PUT    | `/api/patient/profile` | Update demographic/contact info (FR-P06)  |

Editable fields: `name`, `dob`, `phone`, `next_of_kin_name`,
`next_of_kin_phone`, `data_sharing_consent`. `nupi`, `email`, and `password`
are deliberately **not** editable here (NUPI is a fixed system ID; email/
password changes belong to a separate account-security flow you can build
later if a FR calls for it).

Every successful update writes an `UPDATE_PROFILE` row to `audit_logs`,
consistent with how `AuthController` already logs `LOGIN`/`LOGOUT`/`REGISTER`.

## 3. Run it

```bash
cd patientlink
php artisan serve
```

Then open **http://localhost:8000/patient/login.html** in your browser.

(If you'd rather serve it through Apache from `htdocs` like your old setup,
point your vhost/`public/` access at `htdocs/patientlink/public/` — the
pages use relative paths like `/api/login`, so they work unmodified either
way, no config changes needed in the HTML/JS.)

Log in with the seeded test account:

```
patient@patientlink.test / password
```

You should land on the profile page, see the seeded NUPI/phone/etc., be
able to open **Edit profile**, change a field, save, and see it persist
(refresh the page to prove it actually round-tripped to MySQL, not just
local state).

## 4. Test the API directly (Postman / curl), independent of the UI

```bash
# 1. Log in to get a token
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"patient@patientlink.test","password":"password"}'

# 2. Use the access_token from the response above
curl http://localhost:8000/api/patient/profile \
  -H "Authorization: Bearer <PASTE_TOKEN_HERE>"

# 3. Update a field
curl -X PUT http://localhost:8000/api/patient/profile \
  -H "Authorization: Bearer <PASTE_TOKEN_HERE>" \
  -H "Content-Type: application/json" \
  -d '{"phone":"0712345678"}'
```

**RBAC check worth screenshotting for your report:** log in as a different
role (e.g. `doctor@patientlink.test`) and hit `/api/patient/profile` with
*that* token — you should get a `403 Forbidden`, and a corresponding
`ACCESS_DENIED` row should appear in `audit_logs`. That's `RoleMiddleware`
+ NFR-07 working together, and it's good evidence for your testing chapter.

## 5. Known gaps / things to flag to your teammate or supervisor

- There's no dedicated "patient self-registration" page here — Chapter 4
  doesn't list a specific FR for it, and `POST /api/register` (built by
  your teammate) already supports creating a patient account end-to-end.
  If you want a public sign-up page for patients, say the word and we'll
  build one that just calls that existing endpoint.
- Email/password changes aren't covered by FR-P06 as written, so they're
  intentionally left out. Worth double-checking with your supervisor
  whether "account settings" is expected to live somewhere in the system.

# Plan 05: Auth & Profile Pages — Full Rewrite to DaisyUI + TypeScript + i18n

## Goal
Rewrite all authentication and profile pages from old Breeze scaffold style to the project's established stack: `<script setup lang="ts">`, DaisyUI classes, and vue-i18n translations. This is the largest plan — these pages currently use hardcoded English strings, old Breeze components (InputLabel, TextInput, PrimaryButton, etc.), and raw Tailwind instead of DaisyUI.

## Dependencies
- Best run after Plan 01 (types available).
- Independent of Plans 02, 03, 04.
- Should run BEFORE Plan 06 (dead code cleanup) since this plan removes imports of Breeze components.

## Scope

### Auth Pages to Rewrite (7 files)
Each must be converted to: `<script setup lang="ts">` + DaisyUI form/button classes + `t()` i18n calls.

#### `resources/js/Pages/Auth/Login.vue`
Current: Mixed — uses old Breeze components (Checkbox, InputError, InputLabel, PrimaryButton, TextInput) alongside some DaisyUI for the Google button. No i18n.

Rewrite to:
- Remove ALL Breeze component imports
- Use DaisyUI: `form-control`, `label`, `label-text`, `input input-bordered`, `input-error`, `checkbox`, `btn btn-primary`, `btn btn-outline`
- Add `lang="ts"` and type props: `{ canResetPassword: boolean; status?: string }`
- Replace ALL hardcoded strings with `t()` calls
- i18n keys to add under `auth.*` namespace:
  - `auth.login` = "Log in"
  - `auth.email` = "Email"
  - `auth.password` = "Password"
  - `auth.rememberMe` = "Remember me"
  - `auth.forgotPassword` = "Forgot your password?"
  - `auth.loginButton` = "Log in"
  - `auth.or` = "Or"
  - `auth.loginWithGoogle` = "Login with Google"

#### `resources/js/Pages/Auth/ForgotPassword.vue`
Current: Full Breeze scaffold, no i18n.

Rewrite to:
- Remove Breeze imports, use DaisyUI form classes
- Add `lang="ts"` and type props: `{ status?: string }`
- i18n keys:
  - `auth.forgotPasswordTitle` = "Forgot Password"
  - `auth.forgotPasswordDescription` = "Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one."
  - `auth.emailResetLink` = "Email Password Reset Link"

#### `resources/js/Pages/Auth/ResetPassword.vue`
Current: Full Breeze scaffold, no i18n.

Rewrite to:
- Remove Breeze imports, use DaisyUI form classes
- Add `lang="ts"` and type props: `{ email: string; token: string }`
- i18n keys:
  - `auth.resetPassword` = "Reset Password"
  - `auth.confirmPassword` = "Confirm Password"
  - `auth.resetPasswordButton` = "Reset Password"

#### `resources/js/Pages/Auth/VerifyEmail.vue`
Current: Breeze scaffold, no i18n.

Rewrite to:
- Remove PrimaryButton import, use DaisyUI `btn btn-primary`
- Add `lang="ts"` and type props: `{ status?: string }`
- i18n keys:
  - `auth.verifyEmailTitle` = "Verify Email"
  - `auth.verifyEmailDescription` = "Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another."
  - `auth.verificationSent` = "A new verification link has been sent to the email address you provided during registration."
  - `auth.resendVerification` = "Resend Verification Email"
  - `auth.logout` = "Log Out"

#### `resources/js/Pages/Auth/ConfirmPassword.vue`
Current: Breeze scaffold, no i18n.

Rewrite to:
- Remove Breeze imports, use DaisyUI form classes
- Add `lang="ts"`
- i18n keys:
  - `auth.confirmPasswordTitle` = "Confirm Password"
  - `auth.confirmPasswordDescription` = "This is a secure area of the application. Please confirm your password before continuing."
  - `auth.confirm` = "Confirm"

#### `resources/js/Pages/Auth/AcceptInvitation.vue`
Current: Already uses DaisyUI classes but has NO i18n. No TypeScript.

Changes:
- Add `lang="ts"` and type props:
  ```typescript
  interface InvitationData {
      email: string;
      token: string;
  }
  interface Props {
      invitation: InvitationData;
  }
  ```
- Replace ALL hardcoded strings with `t()` calls
- i18n keys:
  - `auth.acceptInvitation` = "Accept Invitation"
  - `auth.welcome` = "Welcome!"
  - `auth.completeRegistration` = "Complete your registration for"
  - `auth.name` = "Name"
  - `auth.creatingAccount` = "Creating account..."
  - `auth.createAccount` = "Create Account"

#### `resources/js/Pages/Auth/InvitationExpired.vue`
Current: Partial DaisyUI, no i18n, no TS.

Changes:
- Add `lang="ts"`
- Replace hardcoded strings with `t()` calls
- i18n keys:
  - `auth.invitationExpired` = "Invitation Expired"
  - `auth.invitationExpiredDescription` = "This invitation link has expired or has already been used. Please contact your administrator for a new invitation."
  - `auth.goToLogin` = "Go to Login"

### Profile Pages to Rewrite (4 files)

#### `resources/js/Pages/Profile/Edit.vue`
Current: Uses `bg-white` and raw Tailwind, no i18n, uses `#header` slot that doesn't exist on AuthenticatedLayout.

Rewrite to:
- Add `lang="ts"` and type props: `{ mustVerifyEmail: boolean; status?: string }`
- Use DaisyUI: `card bg-base-100` for section wrappers
- Remove the non-existent `#header` slot usage
- i18n keys:
  - `profile.title` = "Profile"

#### `resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue`
Current: Full Breeze scaffold, no i18n.

Rewrite to:
- Remove Breeze imports, use DaisyUI form classes
- Add `lang="ts"` and type props: `{ mustVerifyEmail: boolean; status?: string }`
- i18n keys:
  - `profile.information` = "Profile Information"
  - `profile.informationDescription` = "Update your account's profile information and email address."
  - `profile.name` = "Name"
  - `profile.emailUnverified` = "Your email address is unverified."
  - `profile.resendVerification` = "Click here to re-send the verification email."
  - `profile.verificationSent` = "A new verification link has been sent to your email address."
  - `profile.saved` = "Saved."

#### `resources/js/Pages/Profile/Partials/UpdatePasswordForm.vue`
Current: Full Breeze scaffold, no i18n.

Rewrite to:
- Remove Breeze imports, use DaisyUI form classes
- Add `lang="ts"`
- i18n keys:
  - `profile.updatePassword` = "Update Password"
  - `profile.updatePasswordDescription` = "Ensure your account is using a long, random password to stay secure."
  - `profile.currentPassword` = "Current Password"
  - `profile.newPassword` = "New Password"

#### `resources/js/Pages/Profile/Partials/DeleteUserForm.vue`
Current: Uses Breeze Modal, DangerButton, SecondaryButton, InputError, InputLabel, TextInput. No i18n.

Rewrite to:
- Remove ALL Breeze imports
- Use native `<dialog>` with DaisyUI `modal modal-box` pattern (consistent with EditTimeEntryModal)
- Use `btn btn-error` instead of DangerButton, `btn btn-ghost` instead of SecondaryButton
- Add `lang="ts"`
- i18n keys:
  - `profile.deleteAccount` = "Delete Account"
  - `profile.deleteAccountDescription` = "Once your account is deleted, all of its resources and data will be permanently deleted."
  - `profile.deleteAccountConfirm` = "Are you sure you want to delete your account?"
  - `profile.deleteAccountConfirmDescription` = "Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account."
  - `profile.deleteAccountButton` = "Delete Account"

## i18n File Updates

### `resources/js/lang/en.json`
Add all the `auth.*` and `profile.*` keys listed above to the English translation file.

### `resources/js/lang/nl.json`
Add Dutch translations for all the same keys. Key translations:
- auth.login = "Inloggen"
- auth.email = "E-mail"
- auth.password = "Wachtwoord"
- auth.rememberMe = "Onthoud mij"
- auth.forgotPassword = "Wachtwoord vergeten?"
- auth.loginButton = "Inloggen"
- auth.or = "Of"
- auth.loginWithGoogle = "Inloggen met Google"
- auth.forgotPasswordTitle = "Wachtwoord vergeten"
- auth.forgotPasswordDescription = "Wachtwoord vergeten? Geen probleem. Vul je e-mailadres in en we sturen je een link om je wachtwoord te resetten."
- auth.emailResetLink = "Resetlink versturen"
- auth.resetPassword = "Wachtwoord resetten"
- auth.confirmPassword = "Wachtwoord bevestigen"
- auth.resetPasswordButton = "Wachtwoord resetten"
- auth.verifyEmailTitle = "E-mail verifiëren"
- auth.verifyEmailDescription = "Bedankt voor je registratie! Verifieer je e-mailadres door op de link te klikken die we je hebben gestuurd. Als je de e-mail niet hebt ontvangen, sturen we je graag een nieuwe."
- auth.verificationSent = "Er is een nieuwe verificatielink verstuurd naar het e-mailadres dat je bij registratie hebt opgegeven."
- auth.resendVerification = "Verificatie-e-mail opnieuw versturen"
- auth.logout = "Uitloggen"
- auth.confirmPasswordTitle = "Wachtwoord bevestigen"
- auth.confirmPasswordDescription = "Dit is een beveiligd gedeelte van de applicatie. Bevestig je wachtwoord om door te gaan."
- auth.confirm = "Bevestigen"
- auth.acceptInvitation = "Uitnodiging accepteren"
- auth.welcome = "Welkom!"
- auth.completeRegistration = "Voltooi je registratie voor"
- auth.name = "Naam"
- auth.creatingAccount = "Account aanmaken..."
- auth.createAccount = "Account aanmaken"
- auth.invitationExpired = "Uitnodiging verlopen"
- auth.invitationExpiredDescription = "Deze uitnodigingslink is verlopen of is al gebruikt. Neem contact op met je beheerder voor een nieuwe uitnodiging."
- auth.goToLogin = "Naar inloggen"
- profile.title = "Profiel"
- profile.information = "Profielinformatie"
- profile.informationDescription = "Werk je profielinformatie en e-mailadres bij."
- profile.name = "Naam"
- profile.emailUnverified = "Je e-mailadres is niet geverifieerd."
- profile.resendVerification = "Klik hier om de verificatie-e-mail opnieuw te versturen."
- profile.verificationSent = "Er is een nieuwe verificatielink verstuurd naar je e-mailadres."
- profile.saved = "Opgeslagen."
- profile.updatePassword = "Wachtwoord wijzigen"
- profile.updatePasswordDescription = "Gebruik een lang, willekeurig wachtwoord om je account veilig te houden."
- profile.currentPassword = "Huidig wachtwoord"
- profile.newPassword = "Nieuw wachtwoord"
- profile.deleteAccount = "Account verwijderen"
- profile.deleteAccountDescription = "Zodra je account is verwijderd, worden alle gegevens permanent verwijderd."
- profile.deleteAccountConfirm = "Weet je zeker dat je je account wilt verwijderen?"
- profile.deleteAccountConfirmDescription = "Zodra je account is verwijderd, worden alle gegevens permanent verwijderd. Voer je wachtwoord in om te bevestigen."
- profile.deleteAccountButton = "Account verwijderen"

## DaisyUI Patterns to Follow
For every form field, use this exact pattern (matching existing app components):

```html
<div class="form-control">
    <label class="label">
        <span class="label-text">{{ t('auth.email') }}</span>
    </label>
    <input
        type="email"
        v-model="form.email"
        class="input input-bordered"
        :class="{ 'input-error': form.errors.email }"
    />
    <label v-if="form.errors.email" class="label">
        <span class="label-text-alt text-error">{{ form.errors.email }}</span>
    </label>
</div>
```

For buttons:
- Primary action: `btn btn-primary`
- Cancel/secondary: `btn btn-ghost`
- Danger action: `btn btn-error`
- Link-style: `btn btn-link`

## Verification
- `ddev npm run build` should succeed
- Test login flow at `/login`
- Test forgot/reset password flow
- Test profile editing at `/profile`
- Test invitation acceptance at `/invitation/{token}`
- Verify both light and dark themes render correctly
- Switch language to Dutch and verify all strings are translated
